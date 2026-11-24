### API docs match the API routes (#2405)

The API docs at `/api/docs` are written by hand and had fallen behind: 36 operations the API serves were not in them, and two entries described URLs that don't exist.

Added to the docs: the typeahead search (`GET /api/search`), `GET /api/series/{id}/photos`, `GET /api/tokens/invalidate`, `DELETE /api/activities/{id}`, `PUT` and `PATCH` on a post, `PUT` on a menu, and `PATCH` on a link, tag and user.

Corrected: the series photo lists return a plain array, not a paged one; updating a tag or a user changes only the fields sent (the docs said it replaced everything); recommended events are at `/api/events/recommended` (the docs said `/api/events-recommended`) and are for the signed-in user, not a `userId` parameter. The entry for a single post inside a thread is removed; that URL never existed.

Left out on purpose: the `/filter` routes of eleven resources (each returns the same list as the resource's documented list endpoint), the email verification link, the docs page itself and the feed of the site's own tag calendar.

A test now compares the routes with the docs, so a new API route fails CI until it is documented or listed as left out with a reason. No API behaviour changes.
