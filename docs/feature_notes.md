# Feature Notes

A more detailed description of new features and changes to the application.

## Unreleased

### One filter panel component for every list page (#2410)
- The filter section of the 16 list pages (button, active-filter badges, the panel with its
  form, Apply and Reset) is now the `<x-filter-panel>` Blade component, with `<x-filter-badge>`
  for each badge. About 600 lines of repeated markup are gone.
- The pages now look the same. Groups, blogs, categories and a user's attending list had their
  filters inside a card with a solid Apply button; they take the button-and-panel look of the
  other twelve. Activity, photos, reviews and threads said "Clear All" where the others say
  "Reset". The submit button is "Apply" everywhere.
- Fixed: on an events list reached through another page (a tag's or venue's events), the Reset
  link beside the badges went back to the main events list. It now returns to the list you
  were on, as the Reset inside the panel already did.

### One "Show Filters" toggle for every list page (#2410)
- The 16 list pages each carried their own copy of the filter toggle script, and the copies had
  drifted. They now share `resources/assets/js/filter-panel.js`, in the site bundle.
- Every list page now remembers whether you left its filters open or closed. Nine pages
  (activity, photos, reviews, threads, tags, categories, the events grid, the calendar and a
  user's attending list) did not before.
- A page that has a filter applied and no badges to show it always opens its filters.
- The toggle button says whether it is expanded (`aria-expanded`, `aria-controls`).
- Removed `partials/filter-js.blade.php`, which 11 views included and which acted on elements
  that no longer exist.
- Deploy: this changes the JavaScript bundle, so run `npm run build`.

### Fewer inline group checks (#2407)
- The role admin pages check for an admin once, as `can:admin` middleware on the controller,
  instead of at the top of each of nine actions. A non-admin is now refused before the form is
  validated.
- `User::isSuperAdmin()` joins `isAdmin()`. Controllers and routes use the two instead of
  spelling out the group names, and the Instagram actions for super admins share one refusal.
- Fixed: the "Add Role" page failed with a server error, because a role that isn't saved yet has
  no name for the name accessor to format.

### Entity quick-add, duplicate check and update summary moved out of the controller (#2407)
- The event form's inline "add a venue / promoter" creates its entity through
  `EntityWriter::quickCreate()`, the same write path as the entity form and the API.
- The duplicate-name check behind that modal and the entity form is
  `App\Services\EntityDuplicateFinder`.
- "Send update summary" on an entity page is `App\Services\EntityUpdateSummarySender`.
- No change in behaviour, apart from one fix: quick-adding a second entity whose name has no
  letters or digits (so nothing to build a slug from) gave it the slug `-1`. It now gets
  `entity-1`.

### User account actions moved out of the controller (#2407)
- Activating, suspending, deleting and purging users, and the signup review's approve and ban,
  are in `App\Services\UserAccounts`. "Send Reminder" and "Send Weekly Update" on a profile
  are in `App\Services\DigestSender`. The controllers keep the authorization and the messages.
- The API's user delete uses the same delete path as the two web ones.
- Behaviour is the same, with two small differences: the list of purged users no longer ends
  with a stray comma, and deleting a user from the admin action no longer writes a "deleted"
  status to the row just before removing it.

### One home for the cached option lists (#2407)
- The lists behind the form selects and list filters (tags, venues, promoters, entities, event
  types, users, series and the small lookup tables) come from `App\Services\OptionLists`. It
  replaces the inline cache calls in the controllers and the key lists kept on four models, and
  it alone names the cache keys. The keys themselves are unchanged.
- The series, thread, forum, contact and group pages built the same lists with a query each on
  every request; they now use the cached ones.
- On a forum's page, the tag and series filters did not match anything: their options were
  keyed by name and id, and the thread filters match by slug. They now offer the same options
  as the thread list, and only public series.
- The visibility select is in name order on the blog and forum forms too, as on the event form.

### A cancelled series no longer shows as upcoming (#2458)
- A public series with a cancellation date kept appearing on its next scheduled day on the
  home page, the upcoming, day and week views and the calendars. It is now left out from the
  day it was cancelled; a series set to end on a later date still shows until then.
- `series:create-events` no longer creates an event for a cancelled series.
- The rule is one method, `Series::isCancelledBy()`, used by `nextOccurrenceDate()`.

### One write path for entities (#2407)
- Creating, updating and patching an entity is one implementation, `App\Services\EntityWriter`,
  called by the web form and the API. The two controllers keep authorization, the request and
  the response; the owners panel of the web form stays in its controller.
- An entity now records who last updated it (`updated_by`). Both controllers tried to and the
  value was dropped.
- A change of roles through the API drops the cached venue and promoter option lists, as the
  web form already did, so a new venue made through the API shows in the event form at once.

### One write path for events (#2407)
- Creating, updating and patching an event is one implementation, `App\Services\EventWriter`,
  called by the web form and the API. The two controllers keep authorization, the request and
  the response.
- An event's venue and promoter stay among its related entities when the event is updated.
  Before, an update removed them unless they were also sent in the related list, though a
  create added them.
- An event now records who last updated it (`updated_by`). Both controllers tried to and the
  value was dropped.

### One write path for series (#2407)
- Creating, updating and patching a series is one implementation, `App\Services\SeriesWriter`,
  called by the web form and the API. The two controllers keep authorization, the request and
  the response. Events and entities follow in later changes.
- One change in behaviour: a series now records who last updated it (`updated_by`). The API
  tried to and the value was dropped; the web form never set it.

### Aliases and API creates match the web form (#2398)
- Entity aliases are resolved in one place, `Alias::resolveList()`, for the web form and the
  API. A name that starts with digits ("808 State") is a name, not an alias id; the API update
  used to treat it as the id. A name some alias already has, in any letter case, reuses that
  alias instead of adding a duplicate row.
- A migration merges the alias rows that share a name: the earliest is kept, entities linked to
  a later copy are linked to it instead, the copies are deleted, and the name gets a unique
  index. No entity loses or gains an alias name. Run `php artisan migrate` on deploy.
- Creating an event through the API relates its venue and promoter to the event, as the web
  form does.
- Creating a series through the API from an event (`eventLinkId`) copies the event's photos to
  the series, as the web form does.

### Small fixes (#2398)
- An event card showed "Buy Tickets" twice; it now shows it once, beside the price.
- New pages for 403 (not allowed), 429 (too many requests) and 500 (server error), in place of
  the framework's plain ones. The 500 page uses no layout, database or built assets, so it still
  renders when one of those is what failed.
- The warning email after repeated failed logins is queued instead of sent during the login
  request.
- Three error messages were shown as green "success" notices (purging users with none to
  purge, inviting an address that already has an account, opening an embed loader directly).
- "calender" is now "calendar" in the default page description and site subtitle.
- `Post::getPrimaryPhoto()` is declared nullable, which it always was.

### Likes count each person once (#2398)
- Liking a thread or post twice no longer adds a second like or raises the count; unliking
  lowers it only when a like was there. The like actions use the same access rule as the
  thread page.
- A migration removes the existing duplicate likes, adds a unique index on
  (`user_id`, `object_type`, `object_id`) and sets the `likes` counters on threads and posts
  from the rows. Run `php artisan migrate` on deploy. Some like counts will change to the
  true number.
- Follows get the same rule: a second migration removes duplicate follows and makes the index
  on (`user_id`, `object_type`, `object_id`) unique. Every follow action now creates the row
  only if it is not there, so a double click cannot fail on the index.

### Pruning: Twitter posting and the predis package removed (#2412)
- Twitter posting is gone: the `dailyTweet` command and its schedule entry, the
  `events/{id}/tweet` and `entities/{id}/tweet` routes (no page linked to them), the
  `EventPublished` notification, the tweet text builders on `Event` and `Entity`, the Twitter
  config and the `laravel-notification-channels/twitter` package. Creating an event as a super
  admin no longer attempts a tweet. The `TWITTER_*` settings in `.env` are no longer read and
  can be deleted.
- Twitter usernames on entities, series and profiles, and the footer link (`SOCIAL_TWITTER`),
  are unchanged.
- `predis/predis` is removed. No cache, session or queue driver uses Redis. The Redis
  connection setting now names the phpredis extension (`REDIS_CLIENT`, default `phpredis`), for
  if Redis is ever switched on.
- The deploy needs `composer install` (five packages removed).
### Pruning: leftover public files (#2412)
- `/api/docs` loaded a committed 11.7 MB development build of Swagger UI. It now loads the
  standard Swagger UI build (pinned version, integrity-checked, from the CDN the layout already
  uses), about 1.7 MB before compression. The page shows the same spec. Swagger UI 5.35.1 was
  tried and stopped rendering after the first tag with this spec, so 5.30.0 is pinned.
- Removed files nothing loads: `public/mix-manifest.json`, `public/ui-preview.html`,
  `public/css/select2.css`, `public/css/select2-bootstrap.min.css`.
### Pruning: dead code (#2412)
- Removed code nothing calls: the old `EmbedExtractor` service with its test and its package
  (`jamband/ripple`; `OembedExtractor` is the one in use), the `UserBanned` event and its
  listener, the `AccessType` model, `OwnableInterface`, three unused API resources, the empty
  `app/Handlers` folders, two unused email views, and unreferenced methods on `Entity`,
  `Event`, `Series`, `Thread`, `User`, `Group`, `Permission`, `Menu` and `Photo`.
- Removed unused session helpers from `TagsController` and `PagesController`, and
  `$this->middleware('auth')` calls made inside actions, where they do nothing. Each of those
  actions is already guarded in its controller's constructor.
- The two extra `OembedExtractor` docs are gone; `docs/OembedExtractor.md` is the one kept.
- No change in behaviour. The deploy needs `composer install` (one package removed).

### One visibility rule for events, series and threads (#2408)
- `Event`, `Series` and `Thread` each wrote out the same list scope and single-record check.
  They now share the `HasVisibility` trait (`app/Models/Concerns`): `visible($user)`,
  `visibleTo($user)`, `isVisibleTo($user)` and a new `public()` scope for public-only output
  (sitemap, Instagram and Discord posts, counts, cached option lists).
- No change in what anyone sees. The thread rule now names its table, so it can be used on a
  query that joins another table with a `visibility_id`.
- The day views no longer query from Blade: they get their scheduled series from
  `Series::byDay()` in the controller, as they get their cards from `Event::cardsByDay()`.
- Blogs, forums and posts keep their own, simpler rule (public, or your own).

### Day views list what the event list does (#2408)
- The home page, `/events/upcoming`, `/events/day/{day}`, the "Load Next Events" fragment
  and `/events/week` had their own rule: public events, plus anything the viewer created.
  They now use `visible()`, the rule `/events` uses.
- What a signed-in member will notice: guarded events now appear on these pages (before,
  only on `/events`), and an event they created and then cancelled no longer does (it is
  not on `/events` either; its page still opens). Guests see what they saw before.
- The queries moved out of the Blade views into `Event::cardsByDay()`, called from the
  controllers. `Event::starting()` and `startingBetween()` are gone. The ajax form of the
  home and upcoming pages now loads its four days in one query, not one per day.

### One implementation of the embed loader routes (#2406)
- Events, entities and series each had the same four actions (`loadEmbeds`,
  `loadMinimalEmbeds` and the two `BySlug` forms), twelve methods in all. They are now the
  `LoadsEmbeds` trait (`app/Http/Controllers/Concerns`); each controller only says which
  model it serves in `embedQuery()`. About 340 lines removed; routes and responses unchanged.
- Opening one of these routes directly (not as the page's ajax call) no longer looks the
  embeds up before sending the visitor back.
- `tests/Feature/EmbedLoaderRoutesTest.php` pins all twelve routes; it passes against the
  old code too.

### Remaining event list actions moved onto the shared list-page scaffold (#2406)
- The iCal feed, the text feed, the brief list, the two exports and the attending iCal feed
  now start their lists the way every other event list does; the reset links use
  `clearList()`; the two "apply filter from a link" actions share one method.
- `/events/upcoming` and `/events/add` set up a list they never ran. That is gone, and the
  date checks and day window they both computed are one method.
- `EventsController` goes from 2,683 to 2,450 lines and no longer sets up the session store
  or list builder by hand anywhere.
- One change in behaviour: those feeds and exports now sort only by the columns the event
  list offers (name, start date, type, updated). Another column in `?sort=` falls back to the
  default, where before any event column was accepted. Otherwise a snapshot of 126 requests
  is identical before and after.

### Content lists moved onto the shared list-page scaffold (#2406)
- Categories, forums (and a forum's own thread list), blogs, photos, posts, users and the
  activity log now use `BuildsListPages`; the seven controllers lose about 700 lines.
  Where `filter` was a copy of `index` it calls it; photos, posts and users each get one
  private method their list actions share.
- Kept as they were: the photo index defaults to event photos and its filter page does not;
  the user index lists newest first and its filter page by name.
- Two small differences, found by a snapshot of 330 requests before and after:
  - the forum filter page loads the thread counts with the list, as the index does, instead
    of one query per forum;
  - a photo tag page no longer blanks the saved "index tab" link (nothing reads it).
- As with the thread lists, a request for JSON to the forum and post lists gets the normal
  page.

### Six admin lists moved onto the shared list-page scaffold (#2406)
- Roles, menus, groups, permissions, entity types and reviews: their `index`, `filter`,
  `reset` and `rppReset` actions now use `BuildsListPages`, and where `filter` was a copy of
  `index` it calls it. About 480 lines removed across the six controllers.
- No change in behaviour: a snapshot of 180 requests is identical before and after.
### Entity lists moved onto the shared list-page scaffold (#2406)
- The seven entity list actions and the two reset links now use `BuildsListPages`;
  `EntitiesController` goes from 1,745 to about 1,500 lines. The trait gained sort aliases
  (the entity lists accept `popularity` for `popularity_score`).
- `/entities` and the following list no longer run a follower-count subquery per row whose
  result nothing displayed.
- No change in behaviour: a snapshot of 78 list requests is identical before and after.

### Thread lists moved onto the shared list-page scaffold (#2406)
- The eight thread list actions and the two reset links now use `BuildsListPages`;
  `ThreadsController` goes from 984 to 670 lines.
- Fixed along the way, because every list now shares one query:
  - `/threads` shows each thread's reply count (only the main list was missing it);
  - sorting by User on the tag, category, series and related-to lists no longer fails with a
    server error;
  - a request for JSON gets the normal page instead of an empty response.

### Shared list-page scaffold, series lists moved onto it (#2406)
- The set-up and view data that every filterable list page repeats now live in one trait,
  `App\Http\Controllers\Concerns\BuildsListPages` (`startList()`, `finishList()`,
  `clearList()`). The event lists use it through their existing helpers and the eight series
  lists were rewritten on it; `SeriesController` is about 240 lines shorter.
- No change in behaviour: a snapshot of 114 list requests is identical before and after.

### Nightly cleanup of expired cache files, old clicks, failed jobs and expired tokens (#2392)
- `php artisan cache:prune-expired` deletes expired files from the file cache. The file store
  only removes an entry when it is read again, and page fragments keyed on a content
  fingerprint never are, so production had built up about 700 MB of them.
- `php artisan clicks:prune` deletes ticket-link clicks older than 365 days. The dashboard
  totals come from the daily rollup and are unaffected; re-running `entities:rollup-stats` for
  a day older than that rebuilds follows and responses and keeps the stored click total.
- `queue:prune-failed` (30 days) and `sanctum:prune-expired` are scheduled too. All four run
  nightly between 04:15 and 04:45.
- New migration: an index on `click_tracks.clicked_at` (production already had one).
- Nothing changes for visitors. See "Nightly housekeeping" in `docs/deployment_notes.md`.

### `.env.example` lists every setting; a sync queue in production is reported (#2403)
- `.env.example` was missing 49 of the settings `config/` reads, including the Spaces keys photo
  uploads need, the Instagram tokens, the Sentry DSN, the session settings and the captcha keys
  registration requires. It now lists all of them, grouped, with optional ones commented out
  beside their default. `SHIELD_USER` / `SHIELD_PASSWORD`, which nothing reads, are gone.
- The example's `MAIL_MAILER` is now `log`, so a new install writes mail to the log until a real
  mailer is configured, instead of failing against placeholder SES credentials.
- A test fails when `config/` reads a setting the example lacks, when the example has one nothing
  reads, or when `env()` is called outside `config/`.
- `monitor:check` reports when the queue driver is `sync` in production, which is what a lost
  `QUEUE_DRIVER` falls back to.
### One schema dump, and a command to compare a database with a fresh install (#2402)
- `database/schema/` held two dumps and Laravel only ever loaded the older one, which had been
  edited by hand. There is now one, `mysql-schema.sql`, generated from a database built the old
  way (old dump plus every migration), so a fresh install has exactly the structure it had before.
- `database/initialize/INIT-schema-only-prod-events_tracker.sql`, a third and older snapshot that
  nothing used, is removed.
- `php artisan db:schema-diff` compares a database's tables, columns, indexes and foreign keys
  with a fresh build and lists every difference. See `docs/deployment_notes.md`, "Database schema".
### One deploy script, checked at the end; Sentry releases (#2401)
- `bin/deploy` on the production server runs every deploy step in order (pull, composer install,
  migrate, asset build, config/route/view caches, worker restart) and stops at the first failure.
  The GitHub deploy workflow runs the same script. It refuses to run outside production.
- It ends by requesting `/up` and the home page and fails unless both answer 200.
- Errors in Sentry are tagged with the deployed commit (`SENTRY_RELEASE`, set by the script).
- Query values are no longer sent to Sentry with errors. Set `SENTRY_SQL_BINDINGS=true` to turn
  them back on.

### CI: audits in their own daily job, production caches built in CI (#2400)
- `composer audit` and the npm audit moved out of the test job into `.github/workflows/audit.yml`,
  which runs daily and on pull requests that change a dependency file. A newly published advisory
  no longer stops the tests or blocks a deploy; the failed daily run is the notice.
- CI builds `config:cache`, `route:cache` and `view:cache` before the tests, so a config or
  Blade file that only breaks under the production caches fails in the pull request.
- A new push to a pull request cancels the run it replaces.
- CI and `.nvmrc` use Node 24, the version production runs.
- Coverage is no longer collected on pushes to `main`; it was never uploaded.

### Monitoring for the scheduler, the queue and the Instagram token (#2399)
Nothing reported a stopped scheduler or queue worker; digests and posts could stop for days.
- `GET /up` answers 200 when the app and database are up and 503 otherwise, for an external
  uptime check. It starts no session.
- A new `monitor:check` command runs every 15 minutes and reports to Sentry when a queued job
  has waited 45 minutes (the worker is stopped or stuck), or when the Facebook/Instagram page
  token is invalid or within 14 days of expiring. Each problem is reported at most every 6 hours.
- That command checks in to one Sentry cron monitor, `scheduler-heartbeat`, so Sentry raises
  an issue when cron or the scheduler stops.
- A scheduled command that exits with an error code is reported to Sentry.
- Set-up steps and the full list are in `docs/deployment_notes.md`, "Monitoring".

### Fewer embed requests on list pages (#2389)
List cards used to make one request each, at page load, to look for a player.
- A card leaves the player placeholder out, and makes no request, when nothing on the event,
  series or entity could become a player: no SoundCloud or Bandcamp URL in its description or
  among its entities' links. This is worked out from the record on each render, so a link
  added later brings the placeholder back at once. Lists that do not load the links keep the
  placeholder.
- The remaining cards ask only when they are scrolled near.
- An empty answer is remembered for an hour, by the server and the browser, not a week: it
  can be a provider that did not respond.
- Cached answers are keyed on a version that changes when the record is saved, when an entity
  is added to or removed from it, and when a link on one of its entities is added, changed or
  removed (a link now touches its entities' `updated_at`).

### One edit rule for events and series (#2395)
Editing an event or series is allowed for its owner and for admins (the `admin` and
`super_admin` groups), through `EventPolicy::update` and `SeriesPolicy::update`.
- The API's `PUT` and `PATCH` for events and series now let admins edit other users' records,
  as the web forms already did. They used to return 403 for anyone but the owner.
- Edit links, the event actions menu and the photo upload box on event and series pages are
  now shown to `admin` users too; they were only shown to the owner and `super_admin`.
### Session messages shown on every page, readable error text (#2396)
- A message a redirect leaves in the session (`->with('error', ...)` or `->with('status', ...)`),
  such as "Your session expired. Please try again.", is now shown at the top of whichever page
  the user lands on. It was only shown on the sign-in pages. The markup is
  `partials/session-messages`.
- Elements marked `x-cloak` (the search dropdown, some menus and modals) stay hidden until
  Alpine starts, on every page; the rule is now in the global stylesheet.
- `text-destructive` uses its own colour token (`--destructive-text`), so error text and the
  "Cancelled" badge meet 4.5:1 contrast in both themes. Buttons keep the `--destructive` fill.
  `text-success` is new, for status messages.
### Faster series next dates (#2388)
A series' next scheduled date is now worked out without stepping through every week or month
since it was founded, and once per series per request. The dates are unchanged. In the API, a
series with no upcoming event now has `next_start_at: null` when it has no schedule or is
cancelled; it used to get a worked-out date.

### Unlisted entities are admin-only everywhere
The Unlisted entity status is now applied in one place, for every page, list, feed and API
response: only admins (the `admin` and `super_admin` groups) see an unlisted entity.
- Its page, role page (`/venue/{slug}` and so on), API endpoints, locations, links and contacts
  return 404 to everyone else, and it is left out of lists whatever filter is used.
- An event or series that has it as venue or promoter still shows the name, as plain text with
  no link, map or address. The API returns `null` for that `venue` / `promoter`.
- Someone who cannot see it cannot remove it by editing the event or series: an empty venue or
  promoter keeps the existing one, and it stays in the related entities. Choosing a different
  venue or promoter replaces it.
- Only admins can set the status to Unlisted.
- Non-admins cannot attach an unlisted entity to an event or series by id; the id is rejected as
  invalid, as if it did not exist.
- Email and Discord posts never link an unlisted venue, whoever's request sends them.
- Entity option lists in forms and filters are refreshed whenever an entity is saved or deleted,
  including through the API (#2394).

### Faster image URLs (#2387)
Public URLs for files on Spaces are now built directly instead of through the AWS SDK, which took
about a millisecond per image; a list page asks for dozens. The URLs are unchanged, including the
encoding of spaces and brackets in older file names. Set `DIGITALOCEAN_SPACES_URL` to serve files
from the Spaces CDN endpoint or a custom domain; left unset, the bucket's own host is used.

### Pusher broadcasting removed (#2412)
Creating or updating an event no longer broadcasts over Pusher, and the browser no longer loads
Echo or the Pusher client. The feature was switched off on production and its only effect was an
"Event Updated" popup. `EventCreated` and `EventUpdated` are still dispatched for their listeners
(Discord posts and so on). The `PUSHER_*` and `BROADCAST_DRIVER` lines can be deleted from `.env`.
A replacement, if one is wanted, is tracked in #2417.

### Events with multiple show dates (#2375)
A short-run event, such as a play, can carry every date it plays instead of being entered once per
date. **Start At** stays the first showing; the event form's **Additional Showings** adds the
others, each with its own start and optional end time (up to 60).
- The event page lists every showing, with the next one marked and past ones struck through.
- API: events return `showings`, `last_start_at` and `next_start_at`, and accept `showings` on
  create and update. A PUT without `showings` removes them; a PATCH without it leaves them alone.
- **Lists:** the event stays in upcoming lists until its last showing, placed and dated by its next
  one, with a "+ more dates" link. It matches a date filter, a by-date page, tonight / this weekend
  and an entity's or tag's upcoming events on any of its dates, and moves to past lists only once
  the run is over.
- **Calendars and day views:** one calendar item per showing; the home page's day columns and the
  week view list it under each day it plays, with that day's time.
- API: `filters[start_at]` and `/api/events/by-date` match on any showing; `start_at` in the
  response is still the first date.
- **Social posts:** a run is promoted in one post that leads with its next date and lists the
  rest (tweets, Instagram captions, Discord embeds; a Discord roundup gives it one line with its
  dates that week). It is announced on Discord while any showing is still to come, gets a Discord
  reminder for each date (spaced by `reminder_min_gap_hours`), and Instagram auto-posting picks it
  up again once a showing has played, at most every 2 days. The weekly carousel, weekend and today
  stories, tag posts and entity reminder emails include it on any of its dates.
- **Emails:** daily and weekly digests, entity reminders and invites show the next date; digest
  entries list the remaining showings.
- **Feedback:** attendees are asked for feedback after the last showing, not the first.
- **Outbound data:** an event page's JSON-LD spans the first showing to the end of the last and
  lists up to 10 upcoming showings as `subEvent`s. iCal exports carry one VEVENT per showing (the
  first date keeps the event's UID). The RSS item shows the next date and the remaining showings.
  The page title gives the first and last dates, and each upcoming showing on the event page has its
  own Google Calendar link.

### Audio player (#2366)
A pop-out player at `/player` plays music from the artists on upcoming shows, using the SoundCloud
and Bandcamp links on their entity pages. It opens in its own small window ("arcane-player"), so
it keeps playing while you browse the site.
- **Where to start it:** "Listen" in the sidebar (upcoming shows), "Play my radar" on `/radar`,
  "Play this tag" on tag pages, "Play this list" on event lists (it plays what the list shows: its
  filters, date range, venue, or the shows you're attending; the past-events archive has none),
  "Play this list" on entity lists (`/entities`, role, type and tag lists, and who you follow: it
  plays the list's playable artists with its filters and sort, each with their next show; the
  button is hidden when nothing on the list is playable), and the headphones icon ("Add to player") on event, artist
  and series pages. When the player is already open, the icon adds to its queue rather than
  replacing it.
- **The queue** has one item per artist, in event order, from upcoming events the viewer can see,
  up to 200, loaded 20 at a time: more load when you scroll to the bottom of the list or play past
  the last artist. The radar queue follows the radar page: shows you're attending first, then
  recommended ones. A filtered queue (tag page, filtered event list) mirrors its list, which
  includes past shows: upcoming matches first, then past ones, most recent first ("Played …").
  Venue-only entities are skipped. Each artist's tracks are looked up only when the player reaches
  them, and cached per link for a week (an hour if nothing was found).
- **Playback** uses the SoundCloud and Bandcamp players' own controls; previous/next (and ←/→)
  move between artists. SoundCloud items move on to the next artist when a track ends. Bandcamp's
  embed has no API, so Bandcamp items only move on with next/previous; an artist with both plays
  SoundCloud first. Space and OS media keys play/pause SoundCloud items.
- **Star** follows the artist (signed-in users). A reload resumes the same queue and artist.
- Endpoints: `GET /player/queue?source=upcoming|radar|attending|venue|event|series|entity|entities&filters[...]&offset=` (`entities` also takes `sort` and `direction`) (each queue is built once and cached for 10 minutes per viewer) and
  `GET /player/entities/{slug}/sources`. The CSP allows `https://w.soundcloud.com` scripts for the
  SoundCloud Widget API.

### Changing your email needs a new verification (#2321)
A user who changes their own email on the web profile form or with `PUT /api/users/{user}` is
unverified until they click the link sent to the new address, so they can't add or edit content
in the meantime. If the old address was verified, it gets a notice that the email was changed, in case someone
else made the change. Re-saving the same address (or changing only its case) keeps verification,
and so does any email change made by an admin, including to their own; an admin correcting the
email of an unverified account sends a new link to the corrected address. A new address gets the
same disposable-domain check as registration. Users can change their email 3 times an hour, and
10 times an hour per IP. The link for a changed email verifies it without signing in whoever opens
it (the new address may be mistyped). An account that is unverified only because of an email change
is kept by the daily `userCleanup` command and by Admin Tools' "Purge Users"
(`users.email_change_pending_at` marks it), and gets one reminder after 3 days.
On the web, the "Check your email" prompt shows after the change. The API response shows
`email_verified_at: null`, and the update accepts an optional `frontend-url` for the link, as
registration does. Needs `php artisan migrate`.

### Spam signup defenses (#2312)
Captcha and email verification didn't stop people, or captcha farms, with a working inbox, and
those accounts used their profile bio for spam.
- **At signup:** the register form has a hidden honeypot field and an encrypted render time, and
  refuses a submission posted under 3 seconds after loading, or one that never loaded the form.
  Disposable email domains (`resources/data/disposable_email_domains.txt`, CC0) are refused on the
  web and the API. Registration is capped at 10 per IP per hour, with web and API sharing the budget.
- **Before an account is trusted:** only the account and admins see its bio and social handles
  (profile page, user cards, API). Its bio, display name, alias and location can't hold links, and
  neither can a display name at signup. An account is trusted once it's
  active and verified, and then either approved by an admin or at least 7 days old with something
  done on the site (an event, entity, comment, response or follow). Bios are capped at 2000
  characters. Comments, posts, reviews and entity edits now need a verified email.
- **Risk score:** each signup is scored on links in the profile, spam keywords in the bio, a
  generated-looking name (a capitalised single word of 9+ letters; all-lowercase or all-caps
  aliases don't count), an unusual email TLD, a burst of signups from one IP and, optionally, StopForumSpam. A score
  of 3 or more keeps the account Pending after it verifies. A new account is rescored when it edits
  its profile. Admins review signups at `/users/signups` (linked from Admin Tools), where they can
  approve accounts, or ban them as spam, which also clears their bio and social handles. Admin
  Tools' "Purge Users" now removes only Pending accounts that never verified, so flagged accounts
  waiting for review are kept.
- **Settings:** in `config/signup.php`. The StopForumSpam lookup is off until
  `STOPFORUMSPAM_ENABLED=true`, because it sends an MD5 hash of the email and the signup IP to
  stopforumspam.org.

### Analytics loads after the page settles (#2302)
Google Analytics (`gtag.js`) cost over a second of main-thread time on a mid-range phone during
page load. It now loads on the visitor's first interaction (pointer, key, mouse wheel or touch) or 5
seconds after the page loads, whichever comes first; the page view is queued and still sent.
Visits that leave within those seconds without interacting aren't counted. The analytics
snippet is now included when `GOOGLE_ANALYTICS` is set (it was gated on `GOOGLE_TAGS`).

### Card grids skip off-screen layout (#2302)
Event, series and entity cards use `content-visibility: auto`, so the browser skips style and
layout for cards until they near the viewport. On a throttled phone this cut style and layout
time by about 30% on long entity pages and the events listing.

### SweetAlert2 loads on demand (#2302)
SweetAlert2 was a third of the main JavaScript bundle but is only used for dialogs. `window.Swal`
now starts as a stand-in whose `fire()` loads the library and passes the call on, returning the
same result promise, so existing `Swal.fire(...)` calls are unchanged. Loading starts on the first
pointer, key or touch event, or 5 seconds after page load. The main bundle went from 218 KB to
141 KB (50 KB gzipped). Only `fire()` is provided before the library loads; use `fire()` rather
than other Swal methods in page scripts. If the chunk can't be fetched, dialogs fall back to the
browser's own `confirm()` or `alert()` until the page is reloaded.

### Hero images at display size (#1932)
Entity and event heroes now load a 1200px WebP variant through `srcset`, falling back to the
full image on wide high-density screens and for photos without a variant yet. The event hero
also gets `fetchpriority="high"`, as the entity hero already had. The lightbox and the OG
image still use the full image. `photos:generate-variants` backfills the variant for existing
hero photos and, with `--thumbnails`, rebuilds heavy legacy thumbnails (see
`docs/deployment_notes.md`).

### Essential Events in the weekly email (#2102)
Most weekly-email subscribers follow nothing, so their personal digest was always empty and
nothing was sent. Now, when an engaged subscriber's weekly digest would be empty, they get an
"Essential events this week" email instead: up to 10 of the coming week's most popular events
they can see (by attendees, then date; cancelled events left out), plus popular tags and
artists or venues they don't follow yet, and a **Pick what to follow** button that opens the
onboarding picker (`/onboarding`). It has its own subject line, the weekly unsubscribe link and
header, and email click tracking, and counts toward event digest reach. Subscribers paused for
inactivity (#2083) don't get it, and the daily digest stays personal. `notifyWeekly --dry-run`
reports how many would go out. The popularity ranking now lives in `PopularContent`, shared
with onboarding; onboarding's events are now actually ranked by attendees (they were sorted by
date) and leave out cancelled events.

### Fewer emails to dormant accounts (#2083)
The daily and weekly digests now skip anyone with no activity in the last 90 days. Activity
means logging in or doing something on the site (following, attending, creating or editing),
using an API token, clicking a link to the site in one of our emails, or a new account. The
digests' own log entries, failed logins and password-reset requests don't count. Links to the
site in member emails go through `/email/click`, which notes the click and redirects, so
people who read the email but never log in aren't treated as dormant. The first time a
dormant member would have received a digest, they get one "we've paused your email updates"
notice with a signed link that turns them back on. That click, or saving the email
preference page with a digest on, is a lasting "keep sending": they won't be paused for
inactivity again. Logging in also resumes them. `notify` and `notifyWeekly` take `--dry-run` to
report how many digests and notices would go out.

The weekly digest only counts a followed series if it next occurs in the coming week, so
following an active series no longer sends an email every week. New signups now start with
only the weekly digest on, web signups get a profile at registration, and `adminTest` runs
weekly (Mondays at noon) instead of daily.

### One-click unsubscribe and SES bounce handling (#2103)
Every digest and alert email now has an unsubscribe link that works without logging in,
plus `List-Unsubscribe` / one-click headers, which Gmail and Yahoo expect from bulk senders.
The header link turns off just that email; the footer link opens a preference page where
the recipient picks which emails to keep, or unsubscribes from all. Entity contact
addresses (the monthly entity reminder and the update summary) get their own opt-out,
which stops entity mail only. SES bounce and complaint notifications delivered by SNS to
`/webhooks/ses` now add the address to the suppression list automatically. Setup is in
`docs/deployment_notes.md`.

## 2026.09.17

### Entity owner stats (#2149, #2146)
Owners and admins get a stats page for each entity at `/entities/{slug}/stats`. It's linked
as **View Stats** in the entity's actions menu and from **Pages you manage** on the owner's
profile. For the last 30 or 90 days, compared with the period before, it shows: page views,
new followers (plus the total), ticket-link clicks and RSVPs on the entity's events, a daily
chart, how many weekly-email inboxes, Instagram posts and Discord posts included the
entity's events, and what's coming up.

Page views are recorded live from the entity page, not counting bots, prefetches, owners or
admins, into `entity_stats_daily`. The weekly email records which events it included in
`event_reach_daily`. `entities:rollup-stats` runs nightly at 02:30 to rebuild follows,
clicks and responses per entity. Clicks and responses count for the venue, the promoter and
every entity billed on the event. Use `--date` and `--days` to backfill. Page views and email
reach only exist from deploy onward, and the page says when counting started.

### Claim an entity page (#2148, #2147)
Logged-in users with a verified email can ask to take over an artist, venue or promoter page
with **Claim this page** in the entity's actions menu (`/entities/{slug}/claim`). They say how
they're connected and can add a link as proof. Admins review claims at `/entity-claims`
(listed in All Modules, with a count of pending claims) and approve or deny with an optional
note. The claimant is emailed either way.

**Approval fully transfers ownership.** The claimant becomes the only owner, everyone who
owned the page before (including whoever created it) loses edit and delete access and gets
an email, and any other pending claims on that page are denied.

Ownership now lives in a new `entity_owners` table rather than `entities.created_by`, which is
kept only as a record of who added the entity. The migration copies every `created_by` into
it, and new entities start owned by their creator. Admins with `grant_entity_ownership` can
also add or remove owners directly with the **Owners** field on the entity form, which
replaces the old "Owner" dropdown. Owners can edit and delete the entity and manage its
links, locations, contacts and photos, on the web and through the API.

## 2026.09.16

### API rate limits enabled
The `api` rate limiter was defined but its middleware was commented out, so nothing
limited the API. It's now on, keyed so one client can't use up the budget for everyone:
**240 requests/minute per authenticated user** and **120 requests/minute per IP** for
anonymous calls. Over the limit returns `429` with `Retry-After`. The old definition
(300/minute with no key) would have been one bucket shared by every caller.

Failed HTTP basic auth is also throttled: after **20 failures per minute from one IP**, basic
auth from that IP gets `429` until the window resets, even with the right password.
Every basic auth request runs a password hash check, so before this the API was an
unmetered password-guessing endpoint. `AuthenticateEither` and a new
`App\Http\Middleware\AuthenticateWithBasicAuth` (now the `auth.basic` alias) share the
`ThrottlesFailedBasicAuth` trait. See `docs/api_notes.md` for the details.

### "Using the API" guide page (#2128)
New public page at `/api-guide`, linked from Help's "For developers" section and quick
links, and listed in the sitemap. It explains the API for site users rather than
developers: your site account is your API account, with the same permissions; basic auth
vs. bearer tokens; paging, sorting, and filtering; writes with `tag_list` and photos; and
rate limits (the ones enabled in #2144). `/api/docs` stays the endpoint reference. The Help page, `docs/api_notes.md`,
and the agent docs pointed at a `POST /api/auth/token` route that doesn't exist. They now
point at the real `POST /api/tokens/create`.

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
