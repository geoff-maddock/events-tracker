# Queued Instagram Weekend Preview — Design

**Date:** 2026-07-29
**Status:** Approved

## Problem

`GET /events/instagram-weekend-preview` (`EventInstagramController::postWeekendPreviewToInstagram`, routed at `routes/web.php:291`) selects up to 10 weekend events and posts each as an Instagram Story synchronously inside the HTTP request. With up to 10 stories, each requiring an upload, a status-poll loop, and a publish call against the Instagram API, the request blocks for a long time and can hit web-server timeouts.

Every sibling action (`postToInstagram`, `postCarouselToInstagram`, `postStoryToInstagram`) has already been converted to a queued job under `app/Jobs/Instagram/` backed by the `InstagramEventPoster` service, with `TracksJobStatus` providing a `JobStatus` row and a `JobCompleted` notification. The weekend preview is the last synchronous holdout.

## Goal

The URL keeps triggering the weekend preview, but the request only validates and dispatches; all Instagram work runs on the queue (`QUEUE_DRIVER=database`). The admin is notified when the run finishes.

## Design

### 1. Service: `InstagramEventPoster::postWeekendPreview(?int $userId): array`

Move the body of the controller method into `app/Services/Integrations/InstagramEventPoster.php`, verbatim in behavior:

- `assertCredentials()` first (existing private helper).
- Weekend window calculation (Fri 00:00 → Sun 23:59; Mon–Thu look forward to the coming Friday, Fri–Sun use the current weekend).
- Selection rules, unchanged: all events if ≤ 10; otherwise top 10 by `response_count` when there is a clear cutoff between 10th and 11th; on a tie, fall back to 5 Friday + 5 Saturday events. Public visibility only, cancelled excluded.
- Story-posting loop, unchanged skip-and-continue semantics: an event with no photo, no image URL, a failed upload, a failed status check, or a failed publish is logged and counted as skipped; the loop continues.
- Per posted event, the existing `recordShare()` logs `Activity` (action 16) and `EventShare` — replacing the controller's inline `Activity::log` + `logEventShare` calls with identical effect.

Returns `['posted' => int, 'skipped' => int, 'total' => int]`.

Throws `RuntimeException` with a user-facing message for terminal cases:

- Credentials missing (via `assertCredentials()`).
- "No events found for the upcoming weekend."
- `posted === 0` after the loop: "No stories could be posted. Ensure the selected events have photos." (matches current behavior where zero posts is an error).

### 2. Job: `app/Jobs/Instagram/PostWeekendPreviewToInstagram`

Mirror of `PostEventStoryToInstagram`:

- `ShouldQueue` + `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`, `TracksJobStatus`.
- Constructor takes `public ?int $userId` only. `initJobStatus('instagram_weekend_preview', 'Instagram weekend preview', null, $userId)` — no subject model since the run spans many events; event selection happens at run time inside the service.
- `public int $timeout = 1200;` — up to 10 stories, each with upload/status-poll/publish (single-story job allows 600).
- `public int $tries = 1;` — deliberately no retries: a retry after partial success would double-post stories that already published. The sibling jobs retry because they are all-or-nothing.
- `handle(InstagramEventPoster $poster)`: `markRunning()`, call `postWeekendPreview($this->userId)`, then `markSucceeded()` with the current success wording, e.g. "Weekend preview posted: 8 stories published, 2 skipped (no photo)." and the counts array as result payload.
- `failed(Throwable $e)`: `markFailed($e->getMessage())`.

### 3. Controller: `postWeekendPreviewToInstagram()` shrinks to dispatch-only

- Admin guard unchanged (`super_admin` group, error flash + `back()` otherwise).
- Fast pre-flight via existing `instagramCredentialError($instagram)` so a missing link flashes immediately with nothing queued. The job re-checks via `assertCredentials()` in case tokens expire between click and run.
- `PostWeekendPreviewToInstagram::dispatch($this->user?->id);`
- Flash "Queued — the weekend preview is being posted to Instagram in the background. You will be notified when it finishes." and `return back()`.
- Route and route name unchanged.

## Error handling summary

| Failure | Where | User sees |
|---|---|---|
| Not super_admin | Controller | Immediate error flash, nothing queued |
| Instagram not linked | Controller | Immediate error flash, nothing queued |
| Credentials expired by run time | Job (service) | Failed JobStatus + failure notification |
| No weekend events | Job (service) | Failed JobStatus + failure notification |
| Some events lack photos / individual post fails | Job (service) | Skip-and-continue; skips reported in success message |
| Zero stories posted | Job (service) | Failed JobStatus + failure notification |
| Timeout / crash | Queue worker | `failed()` → failure notification |

## Testing

No existing tests cover this route; the feature test is new coverage. In `tests/Feature/` following suite conventions (`$seed = true`, `withExceptionHandling()`):

- Guest / non-admin hitting the route: no job dispatched (assert with `Queue::fake()` / `Bus::fake()`), error flash, redirect.
- Super-admin with Instagram credential pre-flight passing: job dispatched once, success flash, redirect. (Credential pre-flight may need the `Instagram` service faked/bound to pass in the test environment.)

Selection-rule logic moves verbatim and is exercised through the service; no behavior change means no new unit tests for it in this PR.

## Out of scope

- Scheduling the preview via cron (URL-triggered only, as today).
- Changing selection rules, captions, or story formatting.
- Retrying partially-failed runs.
