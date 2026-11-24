### Five unused routes removed (#2412)

Removed after checking a week of web server logs (no request to any of them) and the code (no link, script or test relies on them):

- `GET /api/calendar-events`: the calendar pages moved to the ranged feed at `/calendar`; `/api/tag-calendar-events` stays.
- `GET /api/tokens/test`, `GET /api/tokens/profile` and `GET /tokens/test`: placeholder replies left from setting up API tokens. `/api/tokens/validate` and `/api/tokens/invalidate` stay.
- `GET /entities/slug/{slug}`: an older way to open an entity page; `/entities/{slug}` and the role URLs (`/venue/{slug}` and so on) cover it.
