# Discord Integration

Auto-posts events to Discord channels. Introduced in [#2058](https://github.com/geoff-maddock/events-tracker/issues/2058).

## Overview

A **Discord target** is one webhook pointing at one channel, plus the rules for what
gets posted there and when. Targets are managed in the admin UI at `/discord-targets`;
webhook URLs are stored encrypted in the database, never in config or `.env`.

Four posting modes, each opted into per target:

| Mode | Column | Trigger |
|---|---|---|
| Announce | `post_on_create` | An event gains a flyer; posts after a settle delay |
| Reminder | `post_reminders` | Daily sweep for events starting N days out |
| Digest | `post_digest` | Weekly roundup on the target's configured day and hour |
| Manual | `allow_manual` | Admin presses "Post to Discord" on an event |

Every mode is inert unless `DISCORD_ENABLED=true`. That is the master switch: no
listener queues a post, no scheduled command sends anything, and the poster refuses
even a manual push.

## How each mode works

### Announce (on create)

`QueueDiscordEventPost` listens for the event gaining a flyer and queues
`PostEventToDiscord` after `DISCORD_CREATE_DELAY_MINUTES` (default 30). The delay
exists because admins routinely fix the title, venue or image within a few minutes
of creating an event — posting instantly broadcasts the typos.

Targets with `requires_photo` set stay quiet until a flyer exists.

### Reminder

```bash
php artisan discord:reminders
```

Runs once daily at 11:00 (America/New_York). Catches events created months in
advance, whose announcement scrolled out of the channel long before anyone could act
on it.

Offsets are days before `start_at`, taken from the target's `reminder_offsets`, or
falling back to `discord.default_reminder_offsets` (`[7, 1]`). The command queues
jobs; it never posts inline.

Two safeguards:

- **A whole-day window against a once-daily run.** An event can neither slip between
  two runs nor be caught by both.
- **`reminder_min_gap_hours`** (default 36) suppresses a reminder that would land on
  top of a recent post for the same event and target — otherwise an event added at
  T-8d is announced, then reminded the next day.

The `discord_posts` ledger has a unique index on `(target, event, 'reminder', offset)`,
so a re-run within the same day is a no-op.

### Digest

```bash
php artisan discord:digest
```

Scheduled **hourly**, but each run serves only the targets whose `digest_day` and
`digest_hour` match the current hour. That gives every channel its own schedule from
a single cron entry — adding a channel never means editing `app/Console/Kernel.php`.

The ISO year-week key (`o-\WW`) makes a second run in the same week a no-op, which is
what makes the hourly schedule safe.

Unlike the reminder command this posts inline rather than queueing: a digest is one
message per target per week, so the volume is trivial, and building it inline keeps
the roundup consistent with the moment its event list was assembled. A failing channel
is logged and skipped so one bad webhook cannot stop the rest of the week's digests.

Window is the target's `digest_window_days` (default 7), capped at
`discord.digest.max_events` (default 25) events.

## Enabling it

Designed to be turned on gradually. Work through these in order.

### 1. Migrate

```bash
php artisan migrate
```

Creates `discord_targets`, `discord_target_criteria`, `discord_posts`.

### 2. Deploy with the feature off

`DISCORD_ENABLED` defaults to `false`, so a deploy posts nothing. No action needed —
this step is just a reminder not to skip ahead.

### 3. Confirm the scheduler is running

Both commands need `schedule:run`. See
[Scheduled tasks](deployment_notes.md#scheduled-tasks) — without that cron entry,
reminders and digests silently never fire.

```bash
php artisan schedule:list   # discord:reminders and discord:digest should appear
```

The queue worker must also be running, since reminder and announce posts are queued.

### 4. Create a target against a private channel

In Discord: **Server Settings → Integrations → Webhooks → New Webhook**. Point it at a
private test channel and copy the URL.

Then `/discord-targets` → **Create**:

| Field | Notes |
|---|---|
| `name` | Display name, used in command output and logs |
| `webhook_url` | Stored encrypted |
| `is_enabled` | Master switch for this one target |
| `post_on_create` | Announce once a flyer exists |
| `post_reminders` | T-7d / T-1d, or override with `reminder_offsets` |
| `post_digest` | Weekly roundup |
| `allow_manual` | Enables the per-event "Post to Discord" button |
| `requires_photo` | Suppress until a flyer exists |
| `digest_day` | Carbon `dayOfWeek` — 0 = Sunday, 4 = Thursday |
| `digest_hour` | 0–23, in the app timezone |
| `digest_window_days` | How far ahead the roundup looks (default 7) |
| `match_all` | Post every event. Leave off and add criteria to filter. |

With `match_all` off, add **criteria** to filter by tag, entity, venue or series. A
target with no criteria and `match_all` off matches nothing — that is deliberate, so a
half-configured channel stays silent rather than firehosing.

### 5. Verify before going live

```bash
# Send a test message to confirm the webhook
# (also a button on the target's show page)
POST /discord-targets/{id}/test

# Show what would be sent, without sending it
php artisan discord:reminders --dry-run
php artisan discord:digest --dry-run --force

# Restrict to the staging target
php artisan discord:digest --target=1 --force
```

`--force` ignores the configured day and hour, so you need not wait for the slot.

### 6. Flip it on

```dotenv
DISCORD_ENABLED=true
```

```bash
php artisan config:cache
```

Production caches config, and `env()` returns `null` outside config files — always read
these values through `config()`.

### 7. Widen

Add real channels as further targets. Start each new one with `requires_photo` and
narrow criteria, then loosen.

## Configuration

`config/discord.php`, overridable via `.env`:

| Setting | Env | Default | Purpose |
|---|---|---|---|
| `enabled` | `DISCORD_ENABLED` | `false` | Master switch |
| `create_delay_minutes` | `DISCORD_CREATE_DELAY_MINUTES` | 30 | Settle delay after a flyer appears |
| `default_reminder_offsets` | — | `[7, 1]` | Days before start, when a target sets none |
| `reminder_min_gap_hours` | — | 36 | Suppress a reminder stacked on a recent post |
| `digest.default_day` | — | 4 (Thu) | Default digest day |
| `digest.default_hour` | — | 10 | Default digest hour |
| `digest.window_days` | — | 7 | Default lookahead |
| `digest.max_events` | — | 25 | Cap on one roundup |
| `timeout` | `DISCORD_TIMEOUT` | 10 | Webhook HTTP timeout |
| `embed_color` | — | `0x7B2FF7` | Embed accent |
| `username` | `DISCORD_WEBHOOK_USERNAME` | — | Override webhook display name |
| `avatar_url` | `DISCORD_WEBHOOK_AVATAR_URL` | — | Override webhook avatar |
| `auto_disable_after_failures` | `DISCORD_AUTO_DISABLE_AFTER_FAILURES` | 5 | Permanent failures before a target disables |
| `notify_admin_on_failure` | `DISCORD_NOTIFY_ADMIN_ON_FAILURE` | `true` | Email admins on failure |

Per-channel webhook URLs are **not** configured here — they are secrets, managed in the
admin UI and stored encrypted.

## Failure handling

Only **permanent** failures count toward `auto_disable_after_failures`. Counting
transient errors would disable every channel at once during a Discord outage. A deleted
webhook (401/403/404) disables the target immediately, regardless of the threshold.

Rate limits (429) are retried in process up to `max_retry_sleep_seconds` (10). Past
that the client throws retryable and lets the queue back off — a worker must never be
parked for a minute waiting on rate limits.

Failure emails never include the webhook URL or its token.

Embed strings are clamped against Discord's documented limits (`discord.limits`), since
Discord answers an over-long or empty field with an opaque 400 rather than a useful
message.

## Times

Every instant in an embed — the `timestamp` field and every `<t:unix:F>` tag — comes
from `App\Services\EventTime`, never from `$event->start_at` directly.

`events.start_at` is stored as a naive wall-clock value. The model casts it using
`config('app.timezone')`, which is `'EST'` — and PHP treats `EST` as a **fixed UTC−5
offset that never observes daylight saving**, while Pittsburgh is UTC−4 from March to
November. Formatting that value for display is harmless, because the offset never comes
up. Deriving an *absolute instant* from it is not: Discord resolves `<t:unix>` against
each viewer's own clock, so an instant computed in `EST` renders an hour late for most
of the year.

`EventTime` reinterprets the stored wall time in `America/New_York`. `ICalBuilder` and
`App\Enums\EventTimeWindow` follow the same rule, so the Discord embed, the `.ics`
download, and the site's time-window pages all agree.

Changing `config/app.php`'s `'EST'` is the real fix, but it touches every date in the
application. Known debt; `EventTime` is the local remedy. **Anything new that emits an
absolute instant must go through it.**

## Commands

```bash
php artisan discord:reminders [--dry-run] [--target=ID]
php artisan discord:digest    [--dry-run] [--target=ID] [--force]
```

| Flag | Effect |
|---|---|
| `--dry-run` | List what would be sent; queue and send nothing |
| `--target=ID` | Restrict to a single target |
| `--force` | Digest only: ignore the configured day and hour |

## Rollback

```dotenv
DISCORD_ENABLED=false
```

```bash
php artisan config:cache
```

Everything stops — listener, both commands, manual push — without a deploy. To silence
a single channel instead, toggle `is_enabled` on that target
(`POST /discord-targets/{id}/toggle`).

## Testing

```bash
php artisan test --filter Discord
```

Covers the poster, target matcher, embed builder, webhook client, admin UI, auto-post
listener, manual post, and both scheduled commands.

## Logging

Cron discards the scheduled commands' stdout, so both commands and the poster write
what they did to the application log. Every line is prefixed `Discord`:

```bash
grep -i discord storage/logs/laravel-$(date +%F).log
```

| Line | Level | Meaning |
|---|---|---|
| `Discord reminders: run started` / `run finished` | info | The daily sweep fired. `targets_opted_in` is how many targets have `post_reminders` on; `queued` is how many jobs it dispatched. |
| `Discord reminders: window scanned` | debug | Per target and offset, how many events the criteria matched — separates "matched nothing" from "matched, then suppressed". |
| `Discord reminders: reminder suppressed, posted recently` | info | `reminder_min_gap_hours` swallowed one. |
| `Discord digest: run started` | debug / **info** | The hourly run fired. Logged at debug when no target is due for this hour (23 hours out of 24), info when one is. |
| `Discord digest: target processed` | info | Carries `events` and the ledger `status` — `skipped` means an empty window, `sent` means a real roundup. |
| `Discord digest: run finished` | info | Summary; only written when a target was actually due. |
| `Discord post sent` | info | One message delivered, with its `message_id`. |
| `Discord: post skipped` | info | The integration or the target is disabled. |
| `Discord: already sent, no-op` | debug | The claim-first ledger stopped a duplicate. This is the dedupe working. |
| `Discord post failed` | error | Delivery failed; carries the HTTP status and Discord error code. |

Webhook URLs are never logged — failure lines carry `maskedUrl()` only.

The `discord_posts` table is the authoritative ledger and outlives the 7-day log
rotation:

```sql
SELECT discord_target_id, event_id, reason, reason_key, status, error, posted_at
FROM discord_posts ORDER BY id DESC LIMIT 20;
```

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Nothing posts at all | `DISCORD_ENABLED` false, or config cached before it was set |
| Nothing has posted since enabling it | Most likely nothing is *due* yet. The digest fires only on the target's `digest_day`/`digest_hour`; announce needs a **new** event to gain a flyer (it does not backfill); reminders need `post_reminders` on. Confirm with the dry runs below. |
| Reminders/digests never fire, manual works | `schedule:run` not in cron — see [Scheduled tasks](deployment_notes.md#scheduled-tasks) |
| Announce/reminders never fire, digest works | Queue worker not running (digest posts inline, the others queue) |
| A target went quiet on its own | Auto-disabled after repeated permanent failures, or the webhook was deleted in Discord — check `is_enabled` and the failure email |
| Digest posted twice in a week | Should be impossible; the ISO week key dedupes. Check for two targets on the same channel. |
| A target posts nothing but is enabled | `match_all` off with no criteria matches nothing by design |

### Checking a live install

Read-only, safe to run on production at any time:

```bash
# Is the feature actually on in the CACHED config? (env() alone will lie)
php artisan tinker --execute="var_dump(config('discord.enabled'));"

# Is the scheduler registered, and when does each command run next?
php artisan schedule:list

# What would each command send right now?
php artisan discord:reminders --dry-run
php artisan discord:digest --dry-run --force
```

`--dry-run` sends nothing. Dropping `--dry-run` from the digest posts for real — use
`--target=ID` to keep that to one channel.

Reminders reporting `0` while the digest dry run lists events means the target has
`post_reminders` off; `forMode()` filters it out before any event is considered.
