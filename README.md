<div align="center">

<img src="public/img/logo.svg" alt="HandwerkCloud" width="88" height="88">

# HandwerkCloud

**Auftragsverwaltung für Handwerksbetriebe** — a field-service management app that takes a
trade business from customer record to quote to scheduled job, with site photos and
touch-drawn sketches attached along the way.

![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-777bb4)
![Symfony](https://img.shields.io/badge/Symfony-6.2-000000)
![Doctrine](https://img.shields.io/badge/Doctrine%20ORM-2.x-fc6a31)
![Tests](https://img.shields.io/badge/tests-23%20passing-3fb950)
![License](https://img.shields.io/badge/license-MIT-blue)

</div>

---

## About

Installers and fitters do their paperwork on a tablet in a van, not at a desk. HandwerkCloud
models that reality: a **customer** gets an **offer** (Angebot); once accepted, the offer is
turned into a **task** (Auftrag) with one click, carrying over the customer, dates, photos and
sketches. On site the fitter photographs the location and sketches the installation directly on
the screen with a finger or stylus.

The interface is entirely in German, because its users are.

> **Note on provenance.** This is a sanitised, self-contained extract of an application I built
> for a client in the solar-carport trade. Client branding, production hostnames and real data
> have been removed and replaced with a neutral identity and generated demo data. The
> architecture, domain model and code are the real thing.

## Screenshots

|  |  |
|:--:|:--:|
| ![Login](docs/screenshots/login.png) | ![Dashboard](docs/screenshots/dashboard.png) |
| **Login** | **Dashboard** — scoped counts per user |
| ![Customers](docs/screenshots/customers.png) | ![Offer](docs/screenshots/offer-edit.png) |
| **Kunden** — customer list | **Angebot** — quote with media and one-click job creation |

<div align="center">
<img src="docs/screenshots/drawing.png" alt="Canvas sketch pad" width="70%">
<br><em><strong>Zeichnung</strong> — pressure-free canvas sketch pad, usable with finger or stylus</em>
</div>

## Features

- **Customers → Offers → Tasks** with a one-click `Angebot → Auftrag` conversion
- **Photo upload** with MIME validation, attached to an offer and its job simultaneously
- **Canvas sketching** — mouse *and* multi-touch, colour and line-width picker, fullscreen mode
- **Per-record authorization** — users see and touch only what they created; admins see everything
- **Global search** across customers, offers and tasks by name, postcode or city
- **Zip export** of every photo and sketch belonging to a job — what the fitters take to site
- **User administration** with role assignment, behind an admin-only firewall

## Tech stack

| | |
|---|---|
| Runtime | PHP 8.1 / 8.2 |
| Framework | Symfony 6.2 (Twig, Forms, Security, Validator, Messenger) |
| Persistence | Doctrine ORM 2.x, MySQL 8 |
| Admin | EasyAdmin 4 |
| Frontend | Twig + SB Admin 2 (Bootstrap 4), vanilla JS canvas — no build step |
| Tests | PHPUnit 9, Symfony BrowserKit |
| CI | GitHub Actions — lint, container/Twig/YAML validation, tests on 8.1 + 8.2 |

## Domain model

```
User ──creates──> Customer ──> Offer ──1:1──> Task
                                 │              │
                                 ├── TaskImage ─┤   uploaded site photos
                                 └── TaskDraw  ─┘   canvas sketches
```

`TaskImage` and `TaskDraw` each carry **both** an `offer` and a `task` relation. When an offer
becomes a job, the same media rows are linked to both sides, so the fitter sees on the job
exactly what was attached to the quote — without duplicating files on disk.

## Points of interest

If you are reading the code to judge it, these are the parts worth your time:

- **[`EntityOwnerVoter`](src/Security/Voter/EntityOwnerVoter.php)** — record-level authorization.
  List pages filter at the repository level; single-record actions go through the voter, so an
  ID in the URL is not enough to reach someone else's data. Images and sketches inherit ownership
  from the offer or task they hang off.
- **[`GlobalSearchController`](src/Controller/GlobalSearchController.php)** — the customer-match
  conditions are grouped into a single `orX` before the ownership restriction is applied. Flat
  `orWhere` chaining here is a classic way to leak other users' rows.
- **[`TaskDrawRenderer`](src/Service/TaskDrawRenderer.php)** — decodes the canvas data URL,
  validates it is really an image, and writes a PNG. Rejects malformed payloads rather than
  handing them to GD.
- **[`TaskImageUploader`](src/Service/TaskImageUploader.php)** — keeps the offer/task media
  relations in sync, and is the only place upload naming and MIME checks happen.
- **[`public/js/TaskDraw.js`](public/js/TaskDraw.js)** — the sketch pad; separate mouse and
  `touchstart`/`touchmove` paths so a stylus behaves the same as a mouse.

## Quick start

Requires PHP ≥ 8.1 with `gd`, `zip`, `intl` and `pdo_mysql`, plus Composer and Docker.

```bash
git clone <this-repo> && cd handwerk-cloud
composer install

docker compose up -d                      # MySQL 8 on port 13306
bin/console doctrine:migrations:migrate -n
bin/console doctrine:fixtures:load -n     # demo customers, offers and jobs

symfony server:start                      # or: php -S 127.0.0.1:8000 -t public
```

Then sign in at <http://127.0.0.1:8000/login>:

| Role | E-Mail | Password |
|---|---|---|
| Administrator | `admin@handwerkcloud.test` | `demo1234` |
| Fitter (own records only) | `monteur@handwerkcloud.test` | `demo1234` |

Log in as the fitter to see ownership filtering in action — the demo data is split between the
two accounts.

## Tests

```bash
bin/console doctrine:database:create --env=test
bin/console doctrine:schema:create --env=test

vendor/bin/phpunit                  # 23 tests
vendor/bin/phpunit --testsuite unit # voter + canvas rendering, no database
```

Functional tests rebuild the schema per test and drive the app through real requests, covering
the authorization rules end to end — including the negative cases (a user requesting another
user's record gets a 403, not their data).

## Project layout

```
src/
├── Controller/         thin controllers, one per aggregate
│   └── Admin/          EasyAdmin dashboard + user CRUD
├── Entity/             Doctrine entities with validation constraints
├── Repository/         queries, incl. findByOwner() scoping
├── Form/               Symfony form types (German labels)
├── Security/           form login authenticator + EntityOwnerVoter
├── Service/            upload handling, canvas rendering, zip export
└── DataFixtures/       demo data
tests/
├── Unit/               voter and renderer, no I/O
└── Functional/         real requests against a real database
```

## Notes

The stack is deliberately period-accurate to when the application was written (Symfony 6.2,
Doctrine ORM 2.x). Were this going back into production it would want a Symfony LTS upgrade,
`sensio/framework-extra-bundle` removed (abandoned upstream), and the SB Admin 2 theme replaced
with an asset pipeline. Those are upgrade decisions for a maintained deployment rather than
changes to what this repository demonstrates.

## License

[MIT](LICENSE)
