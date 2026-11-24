### One entity per slug, and an index for the activity log (#2413)

An entity page is found by its slug, but nothing stopped two entities having the same one; the second could then never be opened. A migration gives any repeated slug a number and makes the index unique. The entity people could already open keeps the slug: the earliest one that is not unlisted.

In production one slug was repeated: `karma`, held by two venues. The active one (id 291), which is the one `/entities/karma` already showed, keeps it. The inactive one (id 296) becomes `karma-2` and can be opened again.

The entity form and the API now check a slug in the form it will be stored: `-karma-` or `karma--bar` used to pass the "already taken" check and then be saved as `karma` or `karma-bar`.

`activities` gets an index on `created_at`. The activity log, the activity graph and the admin activity summary sort or select by date and were reading the whole table (120,000 rows in production).

**Deploy:** run `php artisan migrate`.
