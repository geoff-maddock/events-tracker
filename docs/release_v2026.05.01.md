# Release Notes v2026.05.01

**Release Date:** May 1, 2026
**Previous Version:** v2025.01.01

This is a major feature release representing a large body of work accumulated since v2025.01.01, including a complete frontend redesign, build pipeline modernization, a significantly expanded API, AI-powered features, and many improvements across events, entities, series, users, and platform infrastructure.

---

## Upgrade Notes

**This release requires action before deploying to an existing installation.** See the full upgrade steps in [deployment_notes.md](deployment_notes.md#upgrading-to-v202605).

- **PHP 8.4+ required** (up from 8.1+)
- **Node 24+ required** for asset builds
- **5 new database migrations** must be run via `php artisan migrate`
- **Build pipeline changed** from Laravel Mix/webpack to Vite — run `npm install && npm run build`

---

## What's Changed

### Frontend & UI

- Complete UI refresh with Tailwind CSS across all modules (#1613)
- Build pipeline migrated from Laravel Mix/webpack to **Vite** (#1706)
- Upgraded to **Tailwind CSS v4** (#1704)
- Default theme set to **dark**
- "Display Type" dropdown filter for Events, Calendar, and Entities views (#1805)
- "Next edition" bar on series cards (#1766)
- "Frequently Performs With" and "Frequently Performs At" sections on entity pages (#1755)
- Entity aliases displayed on entity cards
- Flyer image used as default `og:image` for social sharing (#1769)
- Friday added to weekend highlight on calendar
- Top pagination added to all list views (#1672)
- "Your Events" added to sidebar (#1665)
- Password visibility toggle on password fields (#1690)
- Conflict warning when selecting a start date that overlaps existing events (#1743)
- Improved back links across entity and event pages (#1827)
- Filter Reset button kept accessible outside collapsed filter panels (#1813)
- About page mobile layout with sidebar link (#1793)

### Events & Calendar

- **AI-powered event creation from flyer image** (#1790)
- Click-tracking redirect layer for Event and Series ticket links via `/go/evt-{id}` and `/go/ser-{id}` (#1710)
- User tracking and bot filtering for click tracking (#1717)
- Multi-tag OR filtering for events (#1708)
- Shareable/bookmarkable URLs for filtered event views (#1727)
- Shareable filter URLs for `/events/grid` with filtered subroutes (#1819, #1821)
- Attendance filter for events (#1795)
- Export iCal button on calendar page (#1798)
- Show cancelled events with minimal info in daily/weekly notification emails (#1764)
- Calendar shows "Ends at [time]" for multi-day event continuation segments (#1759)
- Event duplication action (#1679)
- Show all events on tag pages, not just future ones (#1776)
- Event timing validation to prevent invalid event creation (#1513)
- `do_not_repost` flag on events to suppress automatic social media posting (#1607)
- Related threads displayed on event show pages (#1747)

### Series

- Daily cron task to auto-generate next edition for each active series (#1728)
- Series links on entity pages now use slugs with ID fallback (#1800)

### Entities

- Active range filter for entities by event association timeframe (#1700)
- Admin "Send Update Summary" email action on entity show page (#1829)
- Monthly entity outreach scheduled command (#1823)
- Admin ability to edit and create links and contacts on entities
- Entity auto-relate action restored on search page (#1667)
- Entity embed refresh action
- JSON-LD / schema.org structured data on entity, event, and series pages (#1739, #1811, #1817)
- Improved SEO metadata on entity pages (#1738)

### Social & Instagram

- Automated Instagram posting command for events (#1595)
- Instagram carousel posting with batch status checking (#1562)
- Configurable Instagram handles and hashtags via environment variable (#1583)
- Authorization checks on Instagram post API endpoint (#1556)
- Instagram follow link added to entity reminder emails
- EventShare logging when Instagram posts are made via web or API (#1609)

### Users & Auth

- User slugs and slug-based user show routes (#1789, #1837)
- User data export with background processing and automatic cleanup (#1702)
- User follow counts displayed (#1748)
- Follow/attend buttons shown to unauthenticated users with login redirect (#1720)
- Admin password reset action in user menu (#1629)
- Remember-me sessions limited to 1 year (#1741)
- `hasPermission` helper added to User model

### Roles & Permissions

- Roles admin module with CRUD operations (#1723)
- Semantic alternate routes for entity roles (#1618)
- Related events on show-by-role routes

### Reports

- Reports module added (#1730)

### Forums & Threads

- Forum layout and info improvements (#1746)
- Improved thread loading performance (#1750)

### Blogs

- Photo support for blog posts (#1833)

### API

- Register New User endpoint with OpenAPI documentation (#1519)
- Email verification endpoint (`/api/email/verify`) (#1546)
- Optional `frontend_url` parameter on `/api/register` (#1549)
- Popular endpoints for events, entities, series, and tags (#1509)
- GET `/api/entities/following` for user-followed entities (#1559)
- GET `/api/tags/{slug}/related-tags` endpoint (#1538)
- Instagram post endpoint for events (#1512)
- Permissions and roles included in `/auth/me` response (#1586)
- Location search and filter enhancements (#1574, #1590)
- `is_benefit`, `min_age`, and `door_price` filters for events (#1527, #1528)
- Description filters for events (#1511)
- `created_at` range filters (#1517)
- Multiple entity IDs and tag IDs supported in filter queries
- Filtering entities by name or exact alias (#1516)
- Unique tag slug validation on create (#1510)
- Activity log entries on password reset (#1532)
- Promoter resource and primary link added to venue responses (#1534)
- Slugs added for tags and entities
- OpenAPI spec with links (`api-with-links.yml`) for ChatGPT custom GPT (#1688)
- `age_format` accessor on Event and Series models (#1602)

### Infrastructure

- Admin activity summary email command with configurable time range (#1599)
- Sitemap improvements and bug fixes (#1597, #1560)
- Click tracking URLs excluded from sitemap (#1725)
- OembedExtractor service for embed extraction via oEmbed APIs (#1540)
- CORS preflight caching for 1 hour
- `title` attributes added to iframes for accessibility (#1598)

---

## Changed

- Refactored event queries and API routes to eliminate N+1 problems (#1600, #1605, #1749, #1839)
- Normalized tag filters and sorting (#1744)
- Filter toggle state decoupled from filter activation (#1752)
- Entity main image uses full resolution instead of thumbnail
- Photo gallery controls respect user permissions (#1732)
- Search results re-ordered by relevance
- Instagram posting frequency changed to 3 posts or 1/12th of eligible total every 30 minutes
- Instagram reshare logic updated to allow multiple reshares in the final month before an event
- Default events grid set to 100 results sorted ascending by start date from today
- Breadcrumbs updated to handle entity types and roles (#1685)
- Improved entity JSON output for embedded events (#1826)
- Improved entity page layout with empty sections cleaned up
- User routes refactored to use slugs (#1837)

---

## Fixed

- Fixed bug in email templates where ticket links used `$link` instead of `$ticket`
- Fixed entity photo display issue (#1831)
- Fixed multi-tag OR filtering adding an empty tag (#1733)
- Fixed series edit form not displaying existing tags and entities (#1767)
- Fixed color bugs and normalized date pickers in forms (#1768)
- Fixed event filter options and weekly notification email bug (#1729)
- Fixed password reset link and error handling (#1530, #1676)
- Fixed user delete confirmation dialog auto-submitting before confirmation (#1524)
- Fixed email verification 403 error (#1522, #1552, #1553)
- Fixed null pointer error in CheckBanned middleware (#1596)
- Fixed bug loading authenticated user in API (#1591)
- Fixed hydration issue with related tag count (#1611)
- Fixed location edit authorization bug
- Fixed Instagram post failure email URL (#1648)
- Fixed AutomatedInstagramPosts command to post carousels with multiple images (#1620)
- Fixed event creation from flyer to set relations and tags consistently (#1792)
- Fixed profile settings (#1701)
- Fixed API PUT/PATCH handling (#1835)
- Fixed Activities API endpoint missing show method (#1567)
- Fixed min_age filter direction (#1527)
- Fixed filtering query parameters with value 0 (#1527)

---

## Database Migrations

The following migrations are new in this release and must be applied with `php artisan migrate`:

| Migration | Description |
|-----------|-------------|
| `add_description_to_tags_table` | Adds a description column to tags |
| `create_event_shares_table` | Tracks event share activity |
| `create_click_tracks_table` | Stores click-tracking data for ticket links |
| `add_user_id_to_click_tracks_table` | Associates click tracks with users |
| `create_blog_photo_table` | Supports photo attachments on blog posts |

---

## Technical Stack

| Component | Version |
|-----------|---------|
| PHP | 8.4+ |
| Framework | Laravel 11 |
| Database | MySQL 8 |
| Frontend CSS | Tailwind CSS v4 |
| Build Tool | Vite |
| Node.js | 24+ |
| Server | NGINX (recommended) |
| SSL | Let's Encrypt (recommended) |

---

## Installation & Upgrade

### For New Installations

Follow the standard installation process in [deployment_notes.md](deployment_notes.md):

```bash
git clone git@github.com:geoff-maddock/events-tracker.git
cd events-tracker
composer install
npm install
cp .env.example .env
# Configure .env with your settings
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ProdExtraDatabaseSeeder
npm run build
```

### For Existing Installations (Upgrading from v2025.01.01)

See the full checklist in [deployment_notes.md](deployment_notes.md#upgrading-to-v202605). Summary:

```bash
php artisan down
git pull origin master
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

---

## Known Issues

- No known critical issues in this release
- See the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) for open feature requests and minor bugs

---

## Roadmap

- Entity-to-entity relations: enhanced relationship mapping between entities
- Lightweight API-driven frontend
- Additional API endpoints for external integrations

---

## Contributing

1. Check the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) for open issues
2. Comment on the issue or contact the author before creating a PR
3. Follow the guidelines in [CONTRIBUTING.md](../CONTRIBUTING.md)

---

## Support

- **Bug Reports**: [Issue tracker](https://github.com/geoff-maddock/events-tracker/issues)
- **Questions**: geoff.maddock@gmail.com
- **Documentation**: See [docs/](.) for detailed documentation

---

## License

Events Tracker is open-source software licensed under the [MIT license](../LICENSE).

---

**Full Changelog**: https://github.com/geoff-maddock/events-tracker/compare/v2025.01.01...v2026.05.01
