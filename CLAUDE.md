# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

HandwerkCloud — a Symfony 7.4 field-service app (German UI) for trade businesses: **Kunden**
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
vendor/bin/phpunit                            # 25 tests
vendor/bin/phpunit --testsuite unit           # no database needed
```

Functional tests need the test database once: `bin/console doctrine:database:create --env=test`
then `doctrine:schema:create --env=test`. Doctrine appends `_test` to the database name, so tests
never touch dev data.

### Stack

Symfony 7.4 / Doctrine ORM 3 / PHP 8.2+ (8.4 locally). The suite runs clean — **no deprecation
notices**. If any appear, treat them as a regression rather than noise:

- `enable_native_lazy_objects: true` in `doctrine.yaml` replaces generated proxies.
- `imagedestroy()` is a no-op since PHP 8.0 and deprecated in 8.5 — do not reintroduce it.

Note PHP's built-in server needs a router script to serve static files alongside Symfony routes;
`symfony server:start` handles this, plain `php -S ... public/index.php` does not.

### CSRF

`config/packages/csrf.yaml` deliberately pins **session-backed** tokens. Symfony 7.2+ defaults to
stateless (double-submit cookie) tokens, which need JavaScript to swap a placeholder into the
form — that silently breaks the hand-written login form, which has no JS. There is a functional
test driving the real login form to catch this.

## Architecture

Standard Symfony layout. **No frontend framework and no build step**: `public/css/app.css` is a
hand-written design system (CSS custom properties for the palette, then components), and
`public/js/app.js` holds the few behaviours needed. Icons are inline SVG via the `icon()` macro in
`templates/_icons.html.twig` — add new ones to the `paths` map there rather than pulling in an
icon font. Forms render through `templates/form/theme.html.twig`, which emits the `.field`
markup the stylesheet expects.

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

- **UI language is German.** Labels, buttons, flash messages and confirm prompts are German
  string literals in form types and Twig. `translations/` is empty, but `default_locale: de` is
  set so framework-supplied messages (security errors, validation constraints) come out German
  too. Keep new user-facing text German and inline.
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
