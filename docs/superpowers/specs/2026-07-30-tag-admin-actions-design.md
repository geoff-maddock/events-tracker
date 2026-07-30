# Tag show page: admin action menu + guarded edit/delete

**Date:** 2026-07-30
**Status:** Implemented (revised during implementation — see Revisions)

## Problem

On the tag show page (`resources/views/tags/show-tw.blade.php`), the Edit/Delete
action menu was gated on `$user->id === $tagObject->created_by`. During
implementation we discovered the `tags` table has **no `created_by` column** —
the check was always false, so the menu never rendered for anyone.

The server side enforced nothing:

- `TagsController::destroy` had no auth or ownership check — any request to
  `DELETE /tags/{tag}` deleted the tag, even from a guest.
- `edit` / `update` only required a verified login; any verified user could
  edit any tag.

The menu's edit/delete URLs were also built from `$tagObject->id`, but tag
routes bind by slug (`getRouteKeyName`), so they would have 404'd had the menu
ever rendered.

## Goal

- Track tag ownership going forward (`created_by`).
- Show the tag action menu to the creator **and** to admins (`admin` or
  `super_admin` group).
- Edit: creator or admin. **Delete: admin only** (creators may not delete
  their own tags).
- Enforce the same rules server-side on `destroy`, `edit`, and `update`.

## Design

### Schema

Migration `2026_07_30_000000_add_created_by_to_tags_table` adds a nullable,
indexed `created_by` (unsigned int, matching `users.id`) to `tags`. Existing
tags keep `null` — they are admin-manageable only.

`Tag::booted()` registers a `creating` hook that stamps
`created_by = Auth::id()` when a user is signed in. This covers the ~15 inline
`new Tag()` creation sites across web/API controllers without touching them.
`created_by` is deliberately **not** in `$fillable`, so it cannot be spoofed
via mass assignment.

### TagPolicy (single source of truth)

`app/Policies/TagPolicy.php`, registered in `AuthServiceProvider::$policies`:

- `update(User $user, Tag $tag)` — `$user->hasGroup('super_admin') || ($tag->created_by && $user->id === $tag->created_by)`
- `delete(User $user, Tag $tag)` — `$user->hasGroup('super_admin')`

The `admin` group passes both via the existing `Gate::before`.

### Controller

`TagsController`:

- `destroy`: `$this->authorize('delete', $tag)`
- `edit` / `update`: `$this->authorize('update', $tag)`

Unauthenticated or unauthorized requests get 403.

### View

`show-tw.blade.php`: menu wrapped in `@can('update', $tagObject)`; the delete
form additionally wrapped in `@can('delete', $tagObject)`. Route calls pass the
model (slug binding) instead of `->id`. Follow star unchanged.

## Testing

`tests/Feature/TagsPermissionsTest.php` (11 tests, green):

1. admin / super_admin can delete a tag they did not create
2. creator **cannot** delete their own tag (403)
3. non-owner cannot delete (403); guest cannot delete (403); tag persists
4. creator and admin can access edit page; non-owner gets 403
5. show page: admin sees Edit + Delete; creator sees Edit but not Delete;
   unrelated user sees neither

## Revisions

- **created_by column added**: original design assumed the column existed; it
  did not, so a migration + creating-hook were added (user-approved).
- **Delete narrowed to admin-only**: original design allowed creators to
  delete their own tags; user revised mid-implementation to admin-only.

## Out of scope

- API tag endpoints (`app/Http/Controllers/Api`) — already admin-gated
  separately.
- `store`/`create` authorization (unchanged: any verified user may create tags).
- Backfilling `created_by` for existing tags.
