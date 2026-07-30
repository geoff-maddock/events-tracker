# Tag show page: admin action menu + guarded edit/delete

**Date:** 2026-07-30
**Status:** Approved

## Problem

On the tag show page (`resources/views/tags/show-tw.blade.php`), the Edit/Delete
action menu is only rendered when the signed-in user is the tag's creator
(`$user->id === $tagObject->created_by`). Admins cannot see or use the menu on
tags they didn't create.

Worse, the server side does not enforce anything:

- `TagsController::destroy` has no auth or ownership check — any request to
  `DELETE /tags/{tag}` deletes the tag, even from a guest.
- `edit` / `update` only require a verified login (`verified` middleware); any
  verified user can edit any tag.

## Goal

- Show the tag action menu to the creator **and** to admins (`admin` or
  `super_admin` group).
- Allow admins to delete (and edit) tags they didn't create.
- Enforce the same rule server-side: `destroy`, `edit`, and `update` require
  creator-or-admin.

## Design

### TagPolicy (single source of truth)

New `app/Policies/TagPolicy.php`:

- `update(User $user, Tag $tag): bool` — `$user->id === $tag->created_by || $user->hasGroup('super_admin')`
- `delete(User $user, Tag $tag): bool` — same rule.

The `admin` group is granted everything by the existing `Gate::before` in
`AuthServiceProvider`, so the policy only needs to handle owner + `super_admin`.

Register the policy in `AuthServiceProvider::$policies` alongside the existing
`PostPolicy` and `ThreadPolicy` entries.

### Controller

In `TagsController`:

- `destroy`: `$this->authorize('delete', $tag);` before deleting.
- `edit` and `update`: `$this->authorize('update', $tag);` at the top.

Unauthenticated or unauthorized requests get 403 via the framework.

### View

`resources/views/tags/show-tw.blade.php` line ~67: replace

```blade
@if ($tagObject->created_by && $user->id === $tagObject->created_by)
```

with

```blade
@can('update', $tagObject)
```

The follow/unfollow star and the rest of the page are unchanged.

### Edge cases

- Tags with `created_by = null` (legacy data): only admins can manage them.
- Guests: never see the menu (outer `$signedIn` check unchanged); direct
  requests are denied by `authorize`.

## Testing

Feature tests (follow suite conventions: `$seed = true`,
`withExceptionHandling()`):

1. Admin can delete a tag they did not create (tag gone, redirect to `/tags`).
2. Creator can delete their own tag.
3. Non-owner regular user gets 403 on `DELETE /tags/{tag}` and `GET /tags/{tag}/edit`.
4. Guest `DELETE /tags/{tag}` is denied (403/redirect; tag still exists).
5. Show page: action menu markup visible to admin, absent for an unrelated
   signed-in user.

## Out of scope

- API tag endpoints (`app/Http/Controllers/Api`) — separate surface, not
  touched here.
- `store`/`create` authorization (unchanged: any verified user may create tags).
