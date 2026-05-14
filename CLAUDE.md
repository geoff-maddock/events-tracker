# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Events Tracker — Laravel 10 CMS for tracking events, series, venues, artists, promoters, and related entities for music/arts communities. PHP 8.1+ on the local sandbox (CI uses PHP 8.4), MySQL 8, Vite + Tailwind 4 + Vue 3 / Alpine / jQuery on the frontend.

Default branch for PRs is `main` 

## Common commands

```bash
# Static analysis (Larastan, level 3)
composer phpstan
./vendor/bin/phpstan analyse

# Full test pipeline (migrate + seed + phpunit) — what CI runs
composer tests

# PHPUnit directly
./vendor/bin/phpunit tests
./vendor/bin/phpunit tests/Feature/EventsControllerTest.php           # single file
./vendor/bin/phpunit --filter testEventCreation tests/Feature/...     # single test
php artisan test                                                       # Laravel wrapper

# Frontend
npm run dev        # Vite dev server
npm run build      # production build (alias: npm run prod)
npm run lint       # eslint resources/assets/js
npm run format     # prettier on resources/**

# Laravel
php artisan migrate:fresh
php artisan db:seed --class=ProdBasicDatabaseSeeder   # or ProdExtra / ProdPittsburgh
php artisan serve
```

`composer.json` scripts call `php-latest` (a system alias). Plain `php artisan ...` works fine in dev.

PHPUnit env (`phpunit.xml`) forces `APP_ENV=testing`, `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `QUEUE_DRIVER=sync`. Tests run against a real MySQL database — `composer tests` runs `migrate` + `db:seed` first, so a working DB connection is required.

PHPStan has a `phpstan-baseline.neon` — don't try to fix baseline errors as part of unrelated work; add new errors to the baseline only if intentional.

## Architecture notes worth knowing up front

**Filters.** `app/Filters/QueryFilter.php` is the base class; each model has a sibling `*Filters.php` (e.g. `EventFilters`, `EntityFilters`). Controllers/API endpoints pipe request input through these to apply `filters[field]=value`, `filters[tag]=…`, `sort`, `direction`. When adding a filterable field, extend the relevant `*Filters` class — don't add ad-hoc `where` clauses in controllers.

**Entity is polymorphic-ish.** A single `Entity` model represents venues, artists, promoters, DJs, producers, etc., differentiated by `EntityType`. `VenuesController` exists but most "venue" logic flows through `EntitiesController` filtered by type. Don't introduce per-subtype models.

**Events ↔ Entities ↔ Series ↔ Tags** are many-to-many with pivot tables, plus polymorphic `Photo`, `Tag`, `Comment`, `Like` relations attached to multiple parent types. Check existing relations on the model before adding new ones; duplication here is easy.

**Visibility + soft deletes.** Most content models have a `visibility_id` (public/private/etc.) and soft deletes. Public listing queries should respect both — use existing scopes like `Event::future()`, `Event::visible($user)` rather than rolling your own.

**User attribution.** `created_by` / `updated_by` are populated on most models — trait-driven, generally automatic, but verify when adding a new model.

**Auth.** Web uses session auth; API supports both Laravel Shield basic auth and Sanctum tokens (acquire via `POST /api/auth/token`). API routes live in `routes/api.php` and `app/Http/Controllers/Api/`.

**Frontend bundling.** Vite (not Mix, despite older docs). Entry config in `vite.config.mjs`, Tailwind 4 via `@tailwindcss/postcss`. Vue 3 SFCs live under `resources/assets/js`; Blade views consume compiled bundles.

**Services.** Non-trivial integrations (Instagram, oEmbed embeds, calendar export, flyer analysis, RSS, image handling) live under `app/Services/`. Prefer extending a service over adding logic to controllers.

## Things to avoid

- Don't edit existing migrations — always add a new one.
- Don't modify the `Prod*DatabaseSeeder` files casually; they're used for fresh production installs.
- `agents.md` in the repo root is older AI-agent notes. Treat it as background, not authority — some details (default branch, version, build tooling: it still says Laravel Mix/Bootstrap 5) are out of date. This file is the source of truth.
- `app/Http/helpers.php` and `app/Http/Flash.php` are autoloaded as files (see composer.json) — global functions live there.

## Docs to consult when relevant

- `docs/deployment_notes.md` — production deploy
- `docs/api_notes.md` — API examples
- `docs/feature_notes.md` — changelog/features
- `CONTRIBUTING.md`, `SECURITY.md`
