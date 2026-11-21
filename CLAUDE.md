# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Events Tracker — Laravel 12 CMS for tracking events, series, venues, artists, promoters, and related entities for music/arts communities. PHP 8.2+ (sandbox and CI run PHP 8.4), MySQL 8. The frontend is Blade views built with Vite and Tailwind 4, plus jQuery and plain JS. Legacy app skeleton (Kernels/Handler/providers) retained by choice.

Default branch for PRs is `main` 

## Common commands

```bash
# Static analysis (Larastan, level 5)
composer phpstan
./vendor/bin/phpstan analyse

# Full test pipeline (fresh migrate + seed on the testing DB, then phpunit)
composer tests

# Parallel (what CI runs; each process gets its own <db>_test_N database, so the DB user needs CREATE/DROP on those)
php artisan test --parallel --processes=4

# PHPUnit directly
./vendor/bin/phpunit tests
./vendor/bin/phpunit tests/Feature/EventsControllerTest.php           # single file
./vendor/bin/phpunit --filter testEventCreation tests/Feature/...     # single test
php artisan test                                                       # Laravel wrapper

# Frontend
npm run dev        # Vite dev server
npm run build      # production build (alias: npm run prod)
npm run lint       # eslint resources/assets/js and public/js
npm run format     # prettier on resources/**/*.{js,css} (not Blade)

# Laravel
php artisan migrate:fresh
php artisan db:seed --class=ProdBasicDatabaseSeeder   # or ProdExtra / ProdPittsburgh
php artisan serve
```

`composer.json` scripts call `php-latest` (a system alias). Plain `php artisan ...` works fine in dev.

PHPUnit env (`phpunit.xml`) forces `APP_ENV=testing`, `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `QUEUE_DRIVER=sync`, `BCRYPT_ROUNDS=4`. CI runs the suite in parallel, without coverage, after building and clearing the production caches (`config:cache`, `route:cache`, `view:cache`); the dependency audits run in their own daily workflow (`audit.yml`). Tests run against a real MySQL database (`.env.testing`, the stage DB) — `composer tests` clears any cached config and runs `migrate:fresh --seed --env=testing` first, so a working DB connection is required.

A cached config (`bootstrap/cache/config.php`) makes Laravel ignore `phpunit.xml` and `.env.testing`, which would point `RefreshDatabase` at the dev DB. `tests/CreatesApplication.php` refuses to run in that case (or when `APP_ENV` isn't `testing`, or the DB is a live one); run `php artisan config:clear` and retry.

Mark tests and data providers with PHPUnit attributes (`#[Test]`, `#[DataProvider('name')]`) or a `test` method prefix; the `@test` / `@dataProvider` docblock annotations are deprecated and PHPUnit 12 ignores them.

PHPStan has a `phpstan-baseline.neon`. Don't try to fix baseline errors as part of unrelated work, and don't grow it: fix new errors in code. Both `app/` and `tests/` are analysed.

## Architecture notes worth knowing up front

**Filters.** `app/Filters/QueryFilter.php` is the base class; each model has a sibling `*Filters.php` (e.g. `EventFilters`, `EntityFilters`). Controllers/API endpoints pipe request input through these to apply `filters[field]=value`, `filters[tag]=…`, `sort`, `direction`. When adding a filterable field, extend the relevant `*Filters` class — don't add ad-hoc `where` clauses in controllers. Filter names and values come straight from the query string: only public methods declared on the concrete class are callable, and a method typed `?string` gets a repeated parameter joined into a comma list (type it `mixed` if it should take arrays). Keep filter parameters `?string` or `mixed` and validate or cast inside: a typed `?int`/`?float` parameter throws on non-numeric input.

**List pages.** A filterable, sortable list action is `startList()`, its own query tail (eager loads, `paginate`), then `finishList()` for the view data, from the `BuildsListPages` trait (`app/Http/Controllers/Concerns`); `clearList()` backs the reset links. `EventsController`, `SeriesController`, `ThreadsController` and `EntitiesController` each wrap these in their own `start…List()` helper. Don't hand-roll the session store and list builder set-up in a new action; the other controllers still do and are being moved over (#2406).

**Entity is polymorphic-ish.** A single `Entity` model represents venues, artists, promoters, DJs, producers, etc. What it *does* is a `Role` (venue, artist, dj, promoter, ...; many-to-many). What it *is* is an `EntityType` (Space, Group, Individual, Interest). There's no venue controller: `/venue`, `/venue/{slug}` and the other role routes go to `EntitiesController` (`indexRoles`, `showByRoleAndSlug`). Don't introduce per-subtype models.

**Events ↔ Entities ↔ Series ↔ Tags** are many-to-many with pivot tables, plus polymorphic `Photo`, `Tag`, `Comment`, `Like` relations attached to multiple parent types. Check existing relations on the model before adding new ones; duplication here is easy.

**Visibility and deletes.** Most content models have a `visibility_id` (public/private/etc.). Any query that lists content must apply it through the existing scopes and helpers rather than its own `where`: `Event::visible($user)`, `Event::visibleTo(?User)`, `Series::visible($user)`, `Event::future()`. A default "public" list filter can be overridden from the query string, so it isn't access control. Deletes are hard deletes; only `DiscordTarget` uses soft deletes.

**Unlisted entities.** An entity with status Unlisted is for admins only. That is a global scope on `Entity` (`App\Models\Scopes\UnlistedEntityScope`), so every query, relation and route binding already leaves them out for everyone else, including queue jobs and the scheduler; don't add your own status `where`. `Entity::withUnlisted()` opts out for maintenance code. An event or series whose venue or promoter is unlisted has a null `venue` / `promoter` for non-admins: guard on the relation (not `venue_id`) and use `venueLabel()` / `promoterLabel()` to show the name as plain text. Updates go through `keepHiddenEntityRefs()` and `syncEntities()` so someone who can't see such an entity doesn't remove it. Validate entity ids from requests with `App\Rules\VisibleEntity`, not `exists:entities,id`, and use `publicVenue()` for output that leaves the site (email, Discord). Raw `DB::table('entities')` queries and joins bypass the scope. Cached entity option lists use `Entity::optionCacheKey()`.

**User attribution.** `created_by` / `updated_by` are populated on most models — trait-driven, generally automatic, but verify when adding a new model.

**Auth.** Web uses session auth; API supports both HTTP basic auth (via the `auth.either` middleware, `App\Http\Middleware\AuthenticateEither`) and Sanctum tokens (acquire via `POST /api/tokens/create`). API routes live in `routes/api.php` and `app/Http/Controllers/Api/`.

**Frontend bundling.** Vite, configured in `vite.config.mjs`, with Tailwind 4 via `@tailwindcss/postcss`. The bundle entry is `resources/assets/js/app.js` (axios, SweetAlert2). The hand-written jQuery scripts in `public/js` are loaded directly by the layout, and Alpine.js comes from a pinned CDN script there (used for small `x-data` menus and modals). There's no Vue. Confirmations use the one `data-confirm` handler in `resources/assets/js/bootstrap.js` (on a form, submit button or link). `npm run lint` covers both `resources/assets/js` and `public/js`.

**Services.** Non-trivial integrations (Instagram, oEmbed embeds, calendar export, flyer analysis, RSS, image handling) live under `app/Services/`. Prefer extending a service over adding logic to controllers.

## Things to avoid

- Don't edit existing migrations — always add a new one.
- Don't edit `database/schema/mysql-schema.sql` by hand. It is the one schema dump a fresh install loads before running the migrations, written by `php artisan schema:dump`; a column added there and not in a migration never reaches an existing database. Every schema change is a migration. `php artisan db:schema-diff` compares a database with a fresh build.
- Don't modify the `Prod*DatabaseSeeder` files casually; they're used for fresh production installs.
- Don't add guidance to `agents.md`; it only points other agents here. This file is the source of truth.
- `app/Http/helpers.php` (now just `flash()`) and `app/Http/Flash.php` are autoloaded as files (see composer.json). Write markup in Blade rather than adding HTML-building helpers.

## Docs to consult when relevant

- `docs/deployment_notes.md` — production deploy, queue worker, scheduler
- `docs/api_notes.md` — API examples
- `docs/feature_notes.md` — changelog/features
- `docs/discord-integration.md` — Discord auto-repost: targets, modes, rollout
- `CONTRIBUTING.md`, `SECURITY.md`
