# User status dot on users index — design

**Date:** 2026-07-29
**Status:** Approved (brainstormed with Geoff Maddock)

## Summary

On the users index page, each user card's avatar gets a small colored dot on its
bottom-right edge (presence-indicator style) reflecting the user's `UserStatus`.
Visible **to all viewers** (the index is a public directory). Hover shows the
status name via `title`.

> Revised 2026-07-29: originally admin-only; Geoff requested status be visible
> to any user on the list page.

## Color mapping

| Status (`user_status_id`) | Color class |
|---|---|
| Active (2) | `bg-green-500` |
| Pending (1) | `bg-yellow-400` |
| Suspended (3) | `bg-orange-500` |
| Banned (4) | `bg-red-500` |
| Deleted (5) | `bg-gray-400` |
| null / unknown | no dot rendered |

## Implementation

- New partial `resources/views/users/status-dot.blade.php`:
  - Full-form `@php ... @endphp` block (the `@php(...)` paren form breaks in this
    app) with a `match` on `$user->user_status_id` returning **literal** Tailwind
    class strings — literal because Tailwind only scans `resources/views` and JS,
    so classes composed in `app/` PHP would be purged.
  - Renders an absolutely-positioned `w-4 h-4 rounded-full border-2 border-card`
    circle with `title="{{ $user->status->name }}"`.
- `resources/views/users/card-tw.blade.php`:
  - Avatar wrapper gets `relative`.
  - Include the partial unconditionally (all viewers see it).
  - **Remove the dead status badge block** — it checks `$user->user_status`, a
    property that doesn't exist (the relation is `status`), so it has never rendered.
- No controller changes: `UsersController@index` already eager-loads `status` and
  `user_status_id` is on the row.

## Rejected alternatives

- Color map on the `UserStatus` model: requires a Tailwind safelist; two places to maintain.
- Inline in `card-tw.blade.php`: card is already busy; partial is reusable on the show page later.

## Testing

Feature test on the users index (suite conventions: `$seed = true`,
`withExceptionHandling()`):

- As anonymous visitor: response contains the dot markup (correct color class and
  `title`) for a user with a known status.
- A user with no status renders no dot.
