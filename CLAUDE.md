# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

HandwerkCloud — a Symfony 6.2 field-service app (German UI) for trade businesses: **Kunden**
(customers) → **Angebote** (offers) → **Aufträge** (tasks), with photo uploads and touch/stylus
sketches drawn on an HTML canvas.

It is a sanitised public extract of a client project, maintained as a portfolio reference. Keep it
that way: no client names, no production hostnames, no real data. See `README.md` for the
public-facing description.

## Commands

```bash
composer install
docker compose up -d                          # MySQL 8 on host port 13306
bin/console doctrine:migrations:migrate -n
bin/console doctrine:fixtures:load -n         # demo data; admin@handwerkcloud.test / demo1234

symfony server:start                          # or: php -S 127.0.0.1:8000 -t public
vendor/bin/phpunit                            # 23 tests
vendor/bin/phpunit --testsuite unit           # no database needed
```

Functional tests need the test database once: `bin/console doctrine:database:create --env=test`
then `doctrine:schema:create --env=test`. Doctrine appends `_test` to the database name, so tests
never touch dev data.

### PHP 8.3+ note

The pinned stack is Symfony 6.2 (EOL). On PHP 8.4/8.5 it boots and renders fine but floods stdout
with `E_DEPRECATED`. Silence it for readable output:

```bash
php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" -d display_errors=0 bin/console <cmd>
```

CI pins 8.1/8.2, where this is not needed. Do not "fix" these deprecations by patching vendor code
or bumping Symfony piecemeal — the version pin is deliberate (see README *Notes*).

Also note PHP's built-in server needs a router script to serve static files alongside Symfony
routes; `symfony server:start` handles this properly, plain `php -S ... public/index.php` does not.

## Architecture

Standard Symfony layout. Static assets are committed directly under `public/` (SB Admin 2
Bootstrap theme, jQuery, FontAwesome vendored in `public/vendor/`) — there is no build step, so
edit CSS/JS in place.

### Domain model

```
User ──creates──> Customer ──> Offer ──1:1──> Task
                                 │              │
                                 ├── TaskImage ─┤   uploaded photos
                                 └── TaskDraw  ─┘   canvas sketches
```

- `Offer` is the entry point; `Task` is generated from it by `OfferController::generateTask`.
- `TaskImage`/`TaskDraw` carry **both** an `offer` and a `task` relation. The same row is attached
  to both sides so an offer and its generated task show identical media. `TaskImageUploader::link()`
  is what keeps them in sync — go through the service rather than setting relations directly.
- Every user-owned entity has a `createdBy`; controllers set it in `new()`.

### Authorization — two layers, both required

1. **Collections** filter at the repository level: `$this->isGranted('ROLE_ADMIN') ? findAll() :
   findByOwner($user)`.
2. **Single records** go through `EntityOwnerVoter` via `denyAccessUnlessGranted(...)`. Without it
   an ID in the URL reaches anyone's data.

When adding a `show`/`edit`/`delete` action, add the voter call. When adding an index action, use
`findByOwner`. Route-level rules in `config/packages/security.yaml` are coarse only — the `^/`
catch-all must stay last, since Symfony applies the first matching rule.

### Services

`src/Service/` holds the logic that used to be static helpers. All are autowired; upload paths are
bound in `config/services.yaml` as `$taskImagesDir` / `$taskDrawingsDir` (parameters
`app.task_images_dir` / `app.task_drawings_dir`).

| Service | Responsibility |
|---|---|
| `TaskImageUploader` | validates + stores uploads, links offer/task media |
| `TaskDrawRenderer` | canvas data URL → validated PNG on disk |
| `TaskArchiver` | zips a task's media, returns a `BinaryFileResponse` |

Entities store only the bare filename in `path`; templates join it with the Twig globals
`taskimages_dir` / `taskdrawings_dir` (web-relative, set in `config/packages/twig.yaml`).
`public/img/drawings/` and `public/img/images/` are tracked via `.gitkeep`, contents gitignored.

### Canvas drawing flow

`public/js/TaskDraw.js` handles mouse *and* touch, and on `save()` writes `canvas.toDataURL()` into
the hidden `task_draw_base64Data` input. `TaskDrawType` submits it as `TaskDraw::base64Data` (TEXT);
`TaskDrawRenderer` decodes and writes the PNG. Both the base64 blob and the PNG are kept.

### Repository save/remove

Repositories carry MakerBundle-style `save(Entity $e, bool $flush = false)` / `remove(...)`;
controllers call these instead of injecting `EntityManagerInterface`. Services that persist many
rows at once (the uploader) use the entity manager directly. Follow whichever fits.

## Conventions

- **UI language is German.** Labels, buttons, flash messages and `confirm()` strings are German
  string literals in form types and Twig. `translations/` is empty — nothing goes through the
  translator. Keep new user-facing text German and inline.
- Route names `app_<entity>_<action>`; controllers prefixed with `#[Route('/offer')]` at class level.
- Deletes are POST-only, CSRF-guarded with token `'delete' . $entity->getId()`.
- Entities carry validation constraints (`#[Assert\...]`); add them there, not only in form types.
- Flash messages use `success` / `info` / `danger`; `base.html.twig` renders them.

## Testing

- `tests/Unit/` — no I/O, no kernel. Voter and renderer logic.
- `tests/Functional/` — extend `DatabaseTestCase`, which rebuilds the schema per test and provides
  `createUser()` / `createCustomer()`. Drive the app through real requests.
- Authorization changes must come with a negative test (stranger requesting another user's record
  gets 403). That is the property most likely to regress silently.
