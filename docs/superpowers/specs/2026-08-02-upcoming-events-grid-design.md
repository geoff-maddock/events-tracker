# Upcoming Events grid + date filter removal (entity show page)

**Date:** 2026-08-02
**Status:** Approved

## Problem

On entity show pages, the Upcoming Events section has two issues:

1. As the viewport narrows, event cards get cramped before the grid drops to
   fewer columns — the 2-column breakpoint (768px) kicks in too late.
2. The section header carries a "From date:" input with Filter/Reset buttons.
   Since past events already appear at the bottom of the page, filtering
   upcoming events by start date is unnecessary UI.

## Changes

### 1. Grid breakpoints

`resources/views/entities/show-tw.blade.php` (Upcoming Events grid, ~line 177):

- Before: `grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4`
- After: `grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4`

Only the 1→2 column step moves (768px → 1024px). Resulting layout:

| Viewport | Columns |
|----------|---------|
| <1024px | 1 |
| 1024–1279px | 2 |
| 1280–1599px | 3 |
| ≥1600px | 4 |

(This project overrides Tailwind's default `2xl` breakpoint to 1600px in
`tailwind.config.js`.)

### 2. Remove the date filter UI

In the same file, delete the "From date" `<form>` (input, Filter button,
conditional Reset link) from the section header. Simplify the header wrapper —
with nothing to the right of the "Upcoming Events" heading, the responsive
flex/justify-between layout is no longer needed.

### 3. Backend cleanup

`app/Http/Controllers/EntitiesController.php`:

- `show()`: remove the `?start_at` request-parsing block; filter related
  events with `Carbon::today()->startOfDay()` directly and stop passing
  `filterStartAt` to the view.
- `indexSlug()` and `showByRoleAndSlug()`: remove the now-unused
  `->with(['filterStartAt' => Carbon::today()])` calls.

Old bookmarked `?start_at=...` URLs continue to load; the parameter is simply
ignored (upcoming events always start from today).

## Out of scope

- Past Events section layout (unchanged).
- Any other grids on the page (Frequently Performs With/At, etc.).

## Testing

- `./vendor/bin/phpunit tests/Feature/VenueEntityPageTest.php tests/Feature/SemanticEntityRoutesTest.php tests/Feature/EntitiesTest.php`
  (no pre-existing tests referenced `start_at` or `filterStartAt` on entity
  show — verified).
- `composer phpstan`.
- Visual check of the grid at ~900px, ~1100px, ~1400px, ~1600px widths.
