# Feature Notes

A more detailed description of new features and changes to the application.

## 2026.09.08

### Duplicate-entity warning on the entity form
The entity create and edit forms now warn, as you type, when another entity of the
**same type** already has that name or lists it as an alias, so aliases stop turning
into separate concrete entities. The same name across different types (a band and the
venue named after it) does not warn. Matches link to the existing entity and say which
alias matched; the form can still be submitted. `GET /entities/quick-check` gained
optional `entity_type_id` and `exclude_id` filters and an `alias` field per match.

### Attach photos by URL (#2123)
`POST /api/{events,entities,series}/{id}/photos/from-url` with `{ "url": "https://…" }`
attaches a photo the server downloads itself, so importers and browser automation that
cannot hold the image bytes (CORS, sandboxes, native file dialogs) can still add flyer
art. It behaves exactly like the multipart upload — same ownership check, first photo
becomes primary, follower notification on an event's first photo — and only differs in
where the bytes come from. The download lives in `RemoteImageFetcher` and is written as
an SSRF sink: https only, host resolved and rejected for private / reserved address
space on every redirect hop (max 3) with the connection pinned to the validated IP,
5 MB cap enforced mid-transfer, bytes sniffed for jpg/png/gif/webp, timeouts, and a
tighter per-user rate limit. The three `addPhoto` methods were refactored so the upload
and URL paths share one tail; the entity upload no longer stores the file twice.

## 2026.09.04

### Tag list de-duplication (#2120)
Every create/update path that accepts a `tag_list` now resolves entries through
`Tag::resolveList()`, which matches existing tags by id **or slug** and only creates
a tag when nothing matches. Previously any non-id value created a new tag, so an API
client sending `["diy"]` produced a duplicate `Diy` tag on every request. A migration
merges any remaining duplicate tags (by slug, onto the lowest id, re-pointing pivot
rows and follows) and adds a unique index on `tags.slug`.

## 2026.08.08

### Discord Auto-Repost
Events can now be auto-posted to Discord channels. A **target** is one webhook pointing
at one channel plus the rules for what gets posted there, managed at `/discord-targets`.
Webhook URLs are stored encrypted.

Four posting modes, each opted into per target:

- **Announce** — posts once an event has a flyer, after a settle delay (default 30
  minutes) so quick title and image fixes are not broadcast
- **Reminder** — a daily sweep for events starting N days out (default T-7d and T-1d)
- **Digest** — a weekly roundup on the target's own configured day and hour
- **Manual** — a "Post to Discord" button on the event page

Targets can match all events or filter by tag, entity, venue or series, and can be held
back until a flyer exists.

The whole feature is inert unless `DISCORD_ENABLED=true`, so it can be deployed off,
pointed at a private channel to verify, then switched on. Reminders and digests require
the [scheduler](deployment_notes.md#scheduled-tasks); announce and reminder posts are
queued, so they also need the queue worker.

See [Discord Integration](discord-integration.md) for setup, configuration and
troubleshooting.

## 2026.02.12

### Tailwind CSS 4 Upgrade (Mix-first)
The frontend build now uses Tailwind CSS v4 while staying on Laravel Mix for this phase.

**Build/config updates:**
- `tailwindcss` upgraded to `^4.1.12`
- `@tailwindcss/postcss` added and used in `postcss.config.js`
- `webpack.mix.js` now relies on PostCSS config for Tailwind processing
- `resources/css/tailwind.css` now uses:
	- `@config '../../tailwind.config.js';`
	- `@import 'tailwindcss';`

**Compatibility adjustment:**
- Replaced `@apply` composition of custom classes (`card-tw`, `card-hover-tw`) with direct utility lists for Tailwind v4 compatibility.

**Template cleanup (unused legacy files removed):**
- `resources/views/app-old.blade.php`
- `resources/views/pages/about-old.blade.php`
- `resources/views/pages/home-old.blade.php`
- `resources/views/pages/search-tw.blade.php`
- `resources/views/users/index-old.blade.php`
- `resources/views/threads/index-old.blade.php`
- `resources/views/events/home-tw.blade.php`

**Notes:**
- Active search remains `resources/views/pages/search.blade.php`.
- A Vite migration is intentionally deferred; this change keeps the existing Mix pipeline stable.

## 2025.01.15

### Semantic Entity Routes
You can now access entities using cleaner, semantic URLs based on their role.

**Index Routes** - List all entities of a specific type:
- `/venue` - List all venues
- `/artist` - List all artists  
- `/dj` - List all DJs
- `/producer` - List all music producers
- `/promoter` - List all promoters
- `/shop` - List all shops
- `/band` - List all bands

**Detail Routes** - View a specific entity by role and slug:
- `/venue/{slug}` - View a specific venue (e.g., `/venue/brillobox`)
- `/artist/{slug}` - View a specific artist (e.g., `/artist/andy-warhol`)
- `/dj/{slug}` - View a specific DJ (e.g., `/dj/cutups`)
- Similar patterns for producer, promoter, shop, and band

These routes work alongside the existing `/entities/role/{role}` and `/entities/{slug}` routes.

## 2024.12.26

### ICAL Calendar Integration
There are now a few different ical feeds that you can use to embed events in your own calendars such as google calendar or outlook.

Want to add all events to your calendar?
Use https://your-domain.com/events/ical

Want to just add events you are attending?
Use https://your-domain.com/users/{your-user-id}/attending-ical

Want to add events you are attending or are related to tags or entities you follow?
Use https://your-domain.com/users/{your-user-id}/interested-ical
