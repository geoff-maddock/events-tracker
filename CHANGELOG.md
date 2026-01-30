# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Frontend & UI
- Complete UI refresh with Tailwind CSS across all modules (#1613)
- Migrated build pipeline from Laravel Mix/webpack to Vite (#1706)
- Upgraded to Tailwind CSS v4 (#1704)
- Default theme set to dark
- "Display Type" dropdown filter for Events, Calendar, and Entities views (#1805)
- "Next edition" bar on series cards (#1766)
- "Frequently Performs With" and "Frequently Performs At" sections on entity pages (#1755)
- Entity aliases displayed on entity cards
- Flyer image used as default og:image for social sharing (#1769)
- Friday added to weekend highlight on calendar
- Top pagination added to all list views (#1672)
- "Your Events" added to sidebar (#1665)
- Password visibility toggle on password fields (#1690)
- Warn users of conflicting existing events when selecting a start date (#1743)
- Back links improved across entity and event pages (#1827)
- Filter Reset button kept accessible outside collapsed filter panels (#1813)
- About page mobile layout with sidebar link (#1793)

#### Events & Calendar
- AI-powered event creation from flyer image (#1790)
- Click-tracking redirect layer for Event and Series ticket links via `/go/evt-{id}` and `/go/ser-{id}` routes (#1710)
- User tracking and bot filtering for click tracking (#1717)
- Multi-tag OR filtering for events (#1708)
- Shareable/bookmarkable URLs for filtered event views (#1727)
- Shareable filter URLs for `/events/grid` with filtered subroutes (#1819, #1821)
- Attendance filter for events (#1795)
- Export iCal button on calendar page (#1798)
- Show cancelled events with minimal info in daily/weekly notification emails (#1764)
- Calendar shows "Ends at [time]" for multi-day event continuation segments (#1759)
- Event duplication action (#1679)
- Show all events on tag pages (not just future ones) (#1776)
- Event timing validation to prevent invalid event creation (#1513)
- `do_not_repost` flag on events to suppress automatic social media posting (#1607)
- Related threads displayed on event show pages (#1747)

#### Series
- Daily cron task to auto-generate next edition for each active series (#1728)
- Series links on entity pages now use slugs with ID fallback (#1800)

#### Entities
- Active range filter for entities by event association timeframe (#1700)
- Admin "Send Update Summary" email action on entity show page (#1829)
- Monthly entity outreach scheduled command (#1823)
- Admin ability to edit and create links and contacts on entities
- Entity auto-relate action restored on search page (#1667)
- Entity embed refresh action
- JSON-LD / schema.org structured data on entity, event, and series pages (#1739, #1811, #1817)
- Improved SEO metadata on entity pages (#1738)

#### Social & Instagram
- Automated Instagram posting command for events (#1595)
- Instagram carousel posting with batch status checking (#1562)
- Configurable Instagram handles and hashtags via environment variable (#1583)
- Authorization checks on Instagram post API endpoint (#1556)
- Instagram follow link added to entity reminder emails
- EventShare logging when Instagram posts are made via web or API (#1609)

#### Users & Auth
- User slugs and slug-based user show routes (#1789, #1837)
- User data export with background processing and automatic cleanup (#1702)
- User follow counts displayed (#1748)
- Show follow/attend buttons to unauthenticated users with login redirect (#1720)
- Admin password reset action in user menu (#1629)
- Limit remember-me sessions to 1 year (#1741)
- `hasPermission` helper added to User model

#### Roles & Permissions
- Roles admin module with CRUD operations (#1723)
- Semantic alternate routes for entity roles (#1618)
- Related events on show-by-role routes

#### Reports
- Reports module added (#1730)

#### Forums & Threads
- Forum layout and info improvements (#1746)
- Improved thread loading performance (#1750)

#### API
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

#### Blogs
- Photo support for blog posts (#1833)

#### Infrastructure
- Admin activity summary email command with configurable time range (#1599)
- Sitemap improvements and bug fixes (#1597, #1560)
- Click tracking URLs excluded from sitemap (#1725)
- OembedExtractor service for embed extraction via oEmbed APIs (#1540)
- CORS preflight caching for 1 hour
- `title` attributes added to iframes for accessibility (#1598)

### Changed
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
- Improved entity page layout and cleaned up empty sections

### Fixed
- Fixed bug in email templates where ticket links used `$link` instead of `$ticket`
- Fixed entity photo display issue (#1831)
- Fixed multi-tag OR filtering adding an empty tag (#1733)
- Fixed series edit form not displaying existing tags and entities (#1767)
- Fixed color bugs and normalized date pickers in forms (#1768)
- Fixed event filter options (#1729)
- Fixed weekly notification email bug (#1729)
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
## [2026.02.01] - 2026-02-01
### Fixed
- Fixed Instagram post failure email to use event slug instead of ID in URLs for better accessibility and identification ([#1648](https://github.com/geoff-maddock/events-tracker/pull/1648))


## [0.1.0] - 2017-08-03
### Added
- All base functionality for event repository - pieces that have been in development for a while, but not released.
- Laravel v5.3 framework base
- Events - shows and club nights object - CRUD, listings, calendar, notifications
- Entities - individuals, groups, locations that are artists, venues, etc - CRUD, listings
- Series - shows and club nights that repeat on a schedule - CRUD, listings, calendar
- Tags - keywords that describe objects
- Forum - message board feature with threads and posts 
- Search - search function takes keyword and returns matching events, series, tags, users
- Configuration files that make use of environment vars for population
- Added CHANGELOG file to track the changes as this project evolves.

### Changed
### Removed

[Unreleased]: https://github.com/geoff-maddock/events-tracker/compare/v2026.02.01...HEAD
[2026.02.01]: https://github.com/geoff-maddock/events-tracker/compare/v2025.01.01...v2026.02.01
[0.1.0]: https://github.com/geoff-maddock/events-tracker/releases/tag/v0.1.0

