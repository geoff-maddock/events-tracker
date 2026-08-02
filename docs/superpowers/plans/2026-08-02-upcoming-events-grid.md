# Upcoming Events Grid + Date Filter Removal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** On entity show pages, drop the Upcoming Events grid to fewer columns at wider viewports (1→2 at 1024px instead of 768px) and remove the "From date" filter UI plus its backend `?start_at` handling.

**Architecture:** Two-file change: the Blade view `resources/views/entities/show-tw.blade.php` (grid classes + delete the filter form) and `app/Http/Controllers/EntitiesController.php` (remove `?start_at` parsing and the `filterStartAt` view variable from three methods). Feature tests live in the existing `tests/Feature/VenueEntityPageTest.php`, which already has helpers for building a venue entity with events.

**Tech Stack:** Laravel 12 Blade, Tailwind 4 utility classes, PHPUnit feature tests (real MySQL, seeded), Larastan.

**Spec:** `docs/superpowers/specs/2026-08-02-upcoming-events-grid-design.md`

## Global Constraints

- Grid classes after change: `grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 mb-6` (exact — only `md:` → `lg:` changes).
- Old `?start_at=...` URLs must still return 200; the parameter is simply ignored.
- Do not touch the Past Events section or any other grid on the page (e.g. the `md:grid-cols-2` Frequently Performs grid).
- No Tailwind rebuild needed: `lg:grid-cols-2` already appears in other views, so it's in the compiled bundle.
- Test conventions (this repo): feature tests use `RefreshDatabase`, `protected $seed = true;`, and `$this->withExceptionHandling()` in `setUp()` — `VenueEntityPageTest` already does all three.
- Run tests with `./vendor/bin/phpunit` (needs working MySQL connection). If routes 404 unexpectedly in tests, run `php artisan route:clear` first.

---

### Task 1: Blade — shift grid breakpoints and remove the filter form

**Files:**
- Modify: `resources/views/entities/show-tw.blade.php:147-189` (Upcoming Events section)
- Test: `tests/Feature/VenueEntityPageTest.php` (append two tests)

**Interfaces:**
- Consumes: existing `makeVenue()` helper and imports (`Event`, `Carbon`, `Visibility`) already present in `VenueEntityPageTest`.
- Produces: nothing consumed by Task 2; Task 2 relies only on the form being gone from the view (no more `$filterStartAt` reads in Blade).

- [ ] **Step 1: Write the failing tests**

Append inside the `VenueEntityPageTest` class (all imports already exist at the top of the file):

```php
public function test_upcoming_events_grid_breaks_to_one_column_below_lg(): void
{
    $venue = $this->makeVenue(['name' => 'Grid Test Hall']);

    $event = Event::factory()->create([
        'name' => 'ZZGRID-' . uniqid(),
        'venue_id' => $venue->id,
        'start_at' => Carbon::now()->addDays(3),
        'visibility_id' => Visibility::VISIBILITY_PUBLIC,
    ]);
    $event->entities()->attach($venue->id);

    $response = $this->get('/entities/' . $venue->slug);

    $response->assertOk();
    $response->assertSee('grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4', false);
}

public function test_upcoming_events_date_filter_is_removed(): void
{
    $venue = $this->makeVenue(['name' => 'No Filter Hall']);

    $response = $this->get('/entities/' . $venue->slug);

    $response->assertOk();
    $response->assertDontSee('From date:', false);
    $response->assertDontSee('name="start_at"', false);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit --filter "test_upcoming_events_grid_breaks_to_one_column_below_lg|test_upcoming_events_date_filter_is_removed" tests/Feature/VenueEntityPageTest.php`
Expected: both FAIL — the first because the grid still says `md:grid-cols-2`, the second because the form renders "From date:".

- [ ] **Step 3: Edit the Blade view**

In `resources/views/entities/show-tw.blade.php`, replace the section header block (currently lines 149–169 — the `flex flex-col sm:flex-row ...` wrapper containing the `<h2>` and the whole `<form>` through its `</form>` closing tag):

```blade
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
    <h2 class="text-xl font-semibold flex items-center gap-2">
        <i class="bi bi-calendar-event"></i>
        Upcoming Events
    </h2>
    <!-- Date Filter -->
    <form method="GET" action="{{ route('entities.show', $entity->slug) }}" class="flex items-center gap-2">
        ...entire form...
    </form>
</div>
```

with just the heading (note `mb-4` moves onto the `<h2>`):

```blade
<h2 class="text-xl font-semibold flex items-center gap-2 mb-4">
    <i class="bi bi-calendar-event"></i>
    Upcoming Events
</h2>
```

Then change the grid line (currently line 177):

```blade
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 mb-6">
```

to:

```blade
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 mb-6">
```

Leave everything else in the section (cache-fragment loop, empty-state `<p>`) untouched.

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/phpunit --filter "test_upcoming_events_grid_breaks_to_one_column_below_lg|test_upcoming_events_date_filter_is_removed" tests/Feature/VenueEntityPageTest.php`
Expected: both PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/entities/show-tw.blade.php tests/Feature/VenueEntityPageTest.php
git commit -m "Shift upcoming-events grid to 1 col below lg; remove date filter UI

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Controller — remove `?start_at` handling and `filterStartAt` variables

**Files:**
- Modify: `app/Http/Controllers/EntitiesController.php` — `show()` (~lines 920, 943–953, 986, 1012), `indexSlug()` (~line 665), `showByRoleAndSlug()` (~line 710)
- Test: `tests/Feature/VenueEntityPageTest.php` (append one test)

**Interfaces:**
- Consumes: Task 1's view no longer reads `$filterStartAt` (the form that used it is gone).
- Produces: `show()` signature becomes `show(Entity $entity, OembedExtractor $embedExtractor): View` — the `Request $request` parameter is dropped because `start_at` was its only use in the method.

- [ ] **Step 1: Write the failing test**

Append inside the `VenueEntityPageTest` class:

```php
public function test_start_at_query_param_is_ignored(): void
{
    $venue = $this->makeVenue(['name' => 'Param Ignored Hall']);

    $event = Event::factory()->create([
        'name' => 'ZZIGNORED-' . uniqid(),
        'venue_id' => $venue->id,
        'start_at' => Carbon::now()->addDays(3),
        'visibility_id' => Visibility::VISIBILITY_PUBLIC,
    ]);
    $event->entities()->attach($venue->id);

    // A far-future start_at used to filter this event out; now it must be ignored.
    $response = $this->get('/entities/' . $venue->slug . '?start_at=' . Carbon::now()->addYear()->format('Y-m-d'));

    $response->assertOk();
    $response->assertSee($event->name, false);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit --filter test_start_at_query_param_is_ignored tests/Feature/VenueEntityPageTest.php`
Expected: FAIL — the controller still honors `?start_at`, so the +3-day event is filtered out by the +1-year date and `assertSee` misses it.

- [ ] **Step 3: Edit the controller**

In `app/Http/Controllers/EntitiesController.php`:

a) `show()` signature (~line 920) — drop the now-unused `Request $request` parameter:

```php
public function show(Entity $entity, OembedExtractor $embedExtractor): View
```

b) Delete the whole date-filter block (~lines 943–953), including its comment:

```php
// determine the start date filter for related events (default: today)
$filterStartAt = null;
if ($request->filled('start_at')) {
    try {
        $filterStartAt = Carbon::parse($request->input('start_at'))->startOfDay();
    } catch (\Exception $e) {
        $filterStartAt = Carbon::today()->startOfDay();
    }
} else {
    $filterStartAt = Carbon::today()->startOfDay();
}
```

c) In the related-events query (~line 986), replace:

```php
->where('start_at', '>=', $filterStartAt)
```

with:

```php
->where('start_at', '>=', Carbon::today()->startOfDay())
```

and update the comment above the query from "sorted by date ascending from the filter date" to "sorted by date ascending from today". Also update the stale comment above the frequently-performs block (~line 1001) from "regardless of the date filter applied above" to "regardless of the upcoming-events window above".

d) In `show()`'s return (~line 1012), remove `'filterStartAt'` from the `compact()` list:

```php
return view('entities.show-tw', compact('entity', 'threads', 'embeds', 'tracks', 'relatedEvents', 'pastEvents', 'frequentlyPerformsWith', 'frequentlyPerformsAt'));
```

e) In `indexSlug()` (~line 665) and `showByRoleAndSlug()` (~line 710), delete the line:

```php
->with(['filterStartAt' => Carbon::today()])
```

(both methods keep their other `->with(...)` calls unchanged).

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/phpunit --filter test_start_at_query_param_is_ignored tests/Feature/VenueEntityPageTest.php`
Expected: PASS.

- [ ] **Step 5: Run the surrounding suites and static analysis**

Run: `./vendor/bin/phpunit tests/Feature/VenueEntityPageTest.php tests/Feature/SemanticEntityRoutesTest.php tests/Feature/EntitiesTest.php`
Expected: all PASS (SemanticEntityRoutesTest covers `showByRoleAndSlug()`, EntitiesTest covers general entity pages).

Run: `composer phpstan`
Expected: no NEW errors (baseline errors are pre-existing; do not add to the baseline).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EntitiesController.php tests/Feature/VenueEntityPageTest.php
git commit -m "Remove start_at date-filter backend from entity show pages

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Visual verification

**Files:** none modified.

**Interfaces:** n/a — manual/browser verification of Tasks 1–2.

- [ ] **Step 1: Load an entity page in the browser at four widths**

Using the local dev site (or `php artisan serve` + Playwright browser tools), open an entity with several upcoming events and confirm:

- ~900px wide → 1 column, no "From date:" input anywhere in the Upcoming Events header
- ~1100px → 2 columns
- ~1400px → 3 columns
- ~1600px → 4 columns

Expected: column counts match the table in the spec; header shows only the "Upcoming Events" heading.

- [ ] **Step 2: Confirm a legacy filter URL still loads**

Visit `/entities/<slug>?start_at=2030-01-01` — page returns 200 and shows the same upcoming events as without the parameter.
