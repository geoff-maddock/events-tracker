# Queued Instagram Weekend Preview Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert `GET /events/instagram-weekend-preview` from a synchronous Instagram-posting loop into a dispatch-only endpoint backed by a queued job.

**Architecture:** Move the weekend-window/selection/posting logic from `EventInstagramController::postWeekendPreviewToInstagram` into a new `InstagramEventPoster::postWeekendPreview()` service method (the per-event story loop delegates to the existing `postStory()`); wrap it in a new `PostWeekendPreviewToInstagram` job using the `TracksJobStatus` trait; shrink the controller to guard + credential pre-flight + dispatch. This mirrors the already-converted `postStoryToInstagram` → `PostEventStoryToInstagram` pattern exactly.

**Tech Stack:** Laravel 12, PHP 8.2+ (sandbox runs 8.4), database queue driver, PHPUnit feature tests against real MySQL, Mockery.

**Spec:** `docs/superpowers/specs/2026-07-29-queued-instagram-weekend-preview-design.md`

## Global Constraints

- Branch: `queued-instagram-weekend-preview` (already created; spec committed on it).
- Route and route name (`events.instagramWeekendPreview`, `routes/web.php:291`) stay unchanged.
- Selection rules move **verbatim**: ≤10 events → all; >10 with clear response-count cutoff between 10th/11th → top 10; tie → 5 Friday + 5 Saturday. Public visibility only, cancelled excluded, ranked by `eventResponses` count desc then `start_at` asc.
- Job: `timeout = 1200`, `tries = 1` (no retries — a retry after partial success would double-post already-published stories).
- Success message wording stays exactly as today: `"Weekend preview posted: {N} stor(y|ies) published"` + (`", {M} skipped (no photo)."` if skips, else `"."`).
- Test conventions: feature tests use `protected bool $seed = true;` and `RefreshDatabase`.
- Run single-file PHPUnit (`./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`), not `composer tests` (full pipeline reseeds the dev DB).
- PHPStan level 3 must stay clean: `composer phpstan`. Don't touch `phpstan-baseline.neon`.
- No new migrations, no changes to existing migrations or `Prod*` seeders.

## File Structure

- **Modify** `app/Services/Integrations/InstagramEventPoster.php` — add `postWeekendPreview()`.
- **Create** `app/Jobs/Instagram/PostWeekendPreviewToInstagram.php` — queued job, mirrors `PostEventStoryToInstagram`.
- **Modify** `app/Http/Controllers/Api/EventInstagramController.php` — replace method body with dispatch; drop now-unused imports.
- **Create** `tests/Feature/QueuedWeekendPreviewTest.php` — all new coverage (service, job, route), modeled on `tests/Feature/QueuedInstagramPostTest.php`.

---

### Task 1: Service method `InstagramEventPoster::postWeekendPreview()`

**Files:**
- Modify: `app/Services/Integrations/InstagramEventPoster.php` (add method at end of class, before closing brace)
- Test: `tests/Feature/QueuedWeekendPreviewTest.php` (create)

**Interfaces:**
- Consumes: existing private helpers `assertCredentials()`, and public `postStory(Event $event, ?int $userId): int` (throws `RuntimeException` on any per-event failure; records `Activity` + `EventShare` on success).
- Produces: `public function postWeekendPreview(?int $userId): array` returning `['posted' => int, 'skipped' => int, 'total' => int]`; throws `RuntimeException` when credentials are missing, when no weekend events exist, or when zero stories post. Task 2's job calls this.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/QueuedWeekendPreviewTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class QueuedWeekendPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Start of the weekend window the service will compute when it runs
     * "today" — same Fri/Sat/Sun logic so tests pass on any weekday.
     */
    private function weekendFriday(): Carbon
    {
        $today = Carbon::today();

        if ($today->isFriday()) {
            return $today->startOfDay();
        }
        if ($today->isSaturday() || $today->isSunday()) {
            return $today->previous(Carbon::FRIDAY)->startOfDay();
        }

        return $today->next(Carbon::FRIDAY)->startOfDay();
    }

    private function weekendEvent(User $user, bool $withPhoto = true, int $hour = 20): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
            'start_at' => $this->weekendFriday()->copy()->addHours($hour),
            'cancelled_at' => null,
        ]);

        if ($withPhoto) {
            $photo = Photo::factory()->create([
                'is_primary' => 1,
                'path' => 'test.jpg',
                'thumbnail' => 'test_thumb.jpg',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $event->photos()->attach($photo->id);
        }

        return $event;
    }

    private function mockStoryPostingInstagram(): Instagram|Mockery\MockInterface
    {
        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $instagram->shouldReceive('uploadStoryPhoto')->andReturn(111)->byDefault();
        $instagram->shouldReceive('checkStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('publishStoryMedia')->andReturn(555)->byDefault();

        return $instagram;
    }

    public function test_service_throws_when_no_weekend_events_exist(): void
    {
        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No events found for the upcoming weekend.');

        $poster->postWeekendPreview(null);
    }

    public function test_service_posts_stories_and_skips_events_without_photos(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $withPhoto = $this->weekendEvent($user, true, 20);
        $this->weekendEvent($user, false, 22); // no photo -> skipped

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postWeekendPreview($user->id);

        $this->assertSame(['posted' => 1, 'skipped' => 1, 'total' => 2], $result);
        $this->assertDatabaseHas('event_shares', [
            'event_id' => $withPhoto->id,
            'platform' => 'instagram',
            'platform_id' => '555',
        ]);
    }

    public function test_service_throws_when_zero_stories_post(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->weekendEvent($user, false, 20); // only event has no photo

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stories could be posted. Ensure the selected events have photos.');

        $poster->postWeekendPreview($user->id);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: 3 FAILURES/ERRORS with `Call to undefined method ...InstagramEventPoster::postWeekendPreview()`

- [ ] **Step 3: Implement the service method**

In `app/Services/Integrations/InstagramEventPoster.php`, add to the existing imports:

```php
use App\Models\Visibility;
use RuntimeException;
```

(`Carbon`, `Event`, `Exception`, `Log` are already imported.) Then add this method to the class, after `postStory()`:

```php
    /**
     * Post a weekend preview to Instagram Stories: the top events for the
     * upcoming weekend (Fri–Sun), ranked by attending-response count.
     *
     * Selection rules:
     *  - If <= 10 weekend events: post all of them.
     *  - If > 10: rank by response count descending and take top 10.
     *  - If the 10th and 11th events are tied (no clear cutoff): fall back to
     *    5 from Friday + 5 from Saturday, each sorted by response count.
     *
     * Per-event failures (no photo, upload/status/publish errors) are logged
     * and skipped; the loop continues. Throws only for terminal cases.
     *
     * @return array{posted: int, skipped: int, total: int}
     */
    public function postWeekendPreview(?int $userId): array
    {
        $this->assertCredentials();

        // Determine the upcoming weekend window (Friday 00:00 through Sunday 23:59)
        $today = Carbon::today();

        if ($today->isFriday()) {
            $fridayStart = $today->copy()->startOfDay();
        } elseif ($today->isSaturday() || $today->isSunday()) {
            $fridayStart = $today->copy()->previous(Carbon::FRIDAY)->startOfDay();
        } else {
            // Mon–Thu: look to the coming Friday
            $fridayStart = $today->copy()->next(Carbon::FRIDAY)->startOfDay();
        }

        $sundayEnd = $fridayStart->copy()->next(Carbon::SUNDAY)->endOfDay();

        // Fetch all weekend events ranked by number of attending responses,
        // excluding cancelled and non-public events.
        $allWeekendEvents = Event::where('start_at', '>=', $fridayStart)
            ->where('start_at', '<=', $sundayEnd)
            ->where('visibility_id', '=', Visibility::VISIBILITY_PUBLIC)
            ->whereNull('cancelled_at')
            ->withCount(['eventResponses as response_count'])
            ->orderBy('response_count', 'desc')
            ->orderBy('start_at', 'asc')
            ->get();

        if ($allWeekendEvents->isEmpty()) {
            throw new RuntimeException('No events found for the upcoming weekend.');
        }

        if ($allWeekendEvents->count() <= 10) {
            $selectedEvents = $allWeekendEvents;
        } else {
            // A clear response-count cutoff between position 10 and 11 lets us
            // take a clean top 10; a tie falls back to day-based distribution.
            $tenth = $allWeekendEvents->get(9);
            $eleventh = $allWeekendEvents->get(10);

            if ($tenth && $eleventh && $tenth->response_count !== $eleventh->response_count) {
                $selectedEvents = $allWeekendEvents->take(10);
            } else {
                $fridayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isFriday())
                    ->take(5);
                $saturdayEvents = $allWeekendEvents
                    ->filter(fn ($e) => Carbon::parse($e->start_at)->isSaturday())
                    ->take(5);
                $selectedEvents = $fridayEvents->merge($saturdayEvents);
            }
        }

        $posted = 0;
        $skipped = 0;

        foreach ($selectedEvents as $event) {
            try {
                $this->postStory($event, $userId);
                $posted++;
            } catch (Exception $e) {
                Log::info('Weekend preview: skipping event '.$event->id.': '.$e->getMessage());
                $skipped++;
            }
        }

        if ($posted === 0) {
            throw new RuntimeException('No stories could be posted. Ensure the selected events have photos.');
        }

        return ['posted' => $posted, 'skipped' => $skipped, 'total' => $selectedEvents->count()];
    }
```

Note: delegating each event to `postStory()` reproduces the controller's loop exactly — every per-event failure mode (no photo, no URL, upload exception, status-check false, publish false) throws `RuntimeException`, which the `catch` converts to a skip. `postStory()` also handles the `Activity` + `EventShare` logging via `recordShare()`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Integrations/InstagramEventPoster.php tests/Feature/QueuedWeekendPreviewTest.php
git commit -m "Add InstagramEventPoster::postWeekendPreview service method

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Job `PostWeekendPreviewToInstagram`

**Files:**
- Create: `app/Jobs/Instagram/PostWeekendPreviewToInstagram.php`
- Test: `tests/Feature/QueuedWeekendPreviewTest.php` (append tests)

**Interfaces:**
- Consumes: `InstagramEventPoster::postWeekendPreview(?int $userId): array` (Task 1); `TracksJobStatus` trait (`initJobStatus`, `markRunning`, `markSucceeded`, `markFailed`).
- Produces: `PostWeekendPreviewToInstagram::__construct(public ?int $userId)` with public `?int $jobStatusId` (from trait). Task 3's controller dispatches this class.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/QueuedWeekendPreviewTest.php` (add imports `App\Jobs\Instagram\PostWeekendPreviewToInstagram`, `App\Models\JobStatus`, `App\Notifications\JobCompleted`, `Illuminate\Support\Facades\Notification`):

```php
    public function test_dispatching_the_job_creates_a_queued_job_status(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $job = new PostWeekendPreviewToInstagram($user->id);

        $this->assertNotNull($job->jobStatusId);
        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'user_id' => $user->id,
            'type' => 'instagram_weekend_preview',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
    }

    public function test_successful_job_marks_status_succeeded_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postWeekendPreview')->once()->with($user->id)
            ->andReturn(['posted' => 8, 'skipped' => 2, 'total' => 10]);

        $job = new PostWeekendPreviewToInstagram($user->id);
        $job->handle($poster);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_SUCCEEDED,
            'message' => 'Weekend preview posted: 8 stories published, 2 skipped (no photo).',
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }

    public function test_single_story_success_message_is_singular_without_skips(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postWeekendPreview')->once()
            ->andReturn(['posted' => 1, 'skipped' => 0, 'total' => 1]);

        $job = new PostWeekendPreviewToInstagram($user->id);
        $job->handle($poster);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'message' => 'Weekend preview posted: 1 story published.',
        ]);
    }

    public function test_failed_job_marks_status_failed_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $job = new PostWeekendPreviewToInstagram($user->id);
        $job->failed(new RuntimeException('No events found for the upcoming weekend.'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
            'message' => 'No events found for the upcoming weekend.',
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: the 4 new tests ERROR with `Class "App\Jobs\Instagram\PostWeekendPreviewToInstagram" not found`; the 3 Task-1 tests still PASS.

- [ ] **Step 3: Create the job class**

Create `app/Jobs/Instagram/PostWeekendPreviewToInstagram.php`:

```php
<?php

namespace App\Jobs\Instagram;

use App\Jobs\Concerns\TracksJobStatus;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Queued job that posts the weekend preview (top weekend events, each as an
 * individual story) to Instagram. Event selection happens at run time inside
 * InstagramEventPoster::postWeekendPreview(), so there is no subject model.
 */
class PostWeekendPreviewToInstagram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use TracksJobStatus;

    // Up to 10 stories, each needing an upload, a status poll, and a publish.
    public int $timeout = 1200;

    // No retries: a retry after a partial success would double-post the
    // stories that already published.
    public int $tries = 1;

    public function __construct(
        public ?int $userId
    ) {
        $this->initJobStatus('instagram_weekend_preview', 'Instagram weekend preview', null, $userId);
    }

    public function handle(InstagramEventPoster $poster): void
    {
        $this->markRunning();

        $result = $poster->postWeekendPreview($this->userId);

        $message = 'Weekend preview posted: '.$result['posted'].' stor'
            .($result['posted'] === 1 ? 'y' : 'ies').' published'
            .($result['skipped'] > 0 ? ', '.$result['skipped'].' skipped (no photo).' : '.');

        $this->markSucceeded($message, $result);
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
    }
}
```

(No `SerializesModels` — the job carries only a scalar user id, unlike the sibling jobs which serialize an `Event`.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: 7 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/Instagram/PostWeekendPreviewToInstagram.php tests/Feature/QueuedWeekendPreviewTest.php
git commit -m "Add PostWeekendPreviewToInstagram queued job

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Controller conversion to dispatch-only

**Files:**
- Modify: `app/Http/Controllers/Api/EventInstagramController.php:469-621` (method `postWeekendPreviewToInstagram`) plus imports
- Test: `tests/Feature/QueuedWeekendPreviewTest.php` (append tests)

**Interfaces:**
- Consumes: `PostWeekendPreviewToInstagram::dispatch(?int $userId)` (Task 2); existing `instagramCredentialError(Instagram $instagram): ?string` private helper.
- Produces: n/a (route endpoint; route/name unchanged).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/QueuedWeekendPreviewTest.php` (add imports `App\Models\Group`, `Illuminate\Support\Facades\Queue`):

```php
    private function superAdmin(): User
    {
        $group = Group::firstOrCreate(['name' => 'super_admin']);
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->groups()->attach($group->id);

        return $admin;
    }

    private function mockInstagramCredentials(): void
    {
        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $this->app->instance(Instagram::class, $instagram);
    }

    public function test_route_queues_the_job_for_super_admins(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get('/events/instagram-weekend-preview');

        $response->assertRedirect();
        Queue::assertPushed(PostWeekendPreviewToInstagram::class, function ($job) use ($admin) {
            return $job->userId === $admin->id;
        });
    }

    public function test_route_does_not_queue_for_non_admins(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);

        $response = $this->actingAs($user)->get('/events/instagram-weekend-preview');

        $response->assertRedirect();
        Queue::assertNothingPushed();
    }

    public function test_route_does_not_queue_when_instagram_is_not_linked(): void
    {
        Queue::fake();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(0);
        $this->app->instance(Instagram::class, $instagram);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get('/events/instagram-weekend-preview');

        $response->assertRedirect();
        Queue::assertNothingPushed();
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: `test_route_queues_the_job_for_super_admins` FAILS (`The expected [PostWeekendPreviewToInstagram] job was not pushed` — old synchronous code runs instead and errors/flashes). The two negative tests may already pass; the 7 earlier tests still PASS.

- [ ] **Step 3: Replace the controller method**

In `app/Http/Controllers/Api/EventInstagramController.php`:

1. Add import: `use App\Jobs\Instagram\PostWeekendPreviewToInstagram;`
2. Remove now-unused imports `use App\Models\Activity;` and `use App\Models\Visibility;` (only the old method body used them — verify with a quick grep that no other reference remains in the file; `postCarouselToInstagramApi` uses the fully-qualified `\App\Models\Visibility`).
3. Replace the entire `postWeekendPreviewToInstagram` method (lines 456–621, including its docblock) with:

```php
    /**
     * Queue the weekend preview to be posted to Instagram Stories.
     * Event selection (top weekend events by attending count) happens inside
     * the queued job. Only accessible by admins.
     */
    public function postWeekendPreviewToInstagram(Instagram $instagram): RedirectResponse
    {
        // Admin-only guard
        if (!$this->user || !$this->user->hasGroup('super_admin')) {
            flash()->error('Error', 'You must be an admin to post the weekend preview to Instagram.');

            return back();
        }

        // Fail fast when Instagram is not linked; the job re-checks at run time.
        if ($error = $this->instagramCredentialError($instagram)) {
            flash()->error('Error', $error);

            return back();
        }

        PostWeekendPreviewToInstagram::dispatch($this->user->id);

        flash()->success('Queued', 'The weekend preview is being posted to Instagram in the background. You will be notified when it finishes.');

        return back();
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php`
Expected: 10 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/EventInstagramController.php tests/Feature/QueuedWeekendPreviewTest.php
git commit -m "Convert instagram-weekend-preview endpoint to dispatch queued job

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Static analysis and regression check

**Files:**
- None (verification only; fix anything found)

**Interfaces:**
- Consumes: everything above.
- Produces: green `composer phpstan` and green Instagram-related test files.

- [ ] **Step 1: Run PHPStan**

Run: `composer phpstan`
Expected: no new errors (baseline errors are pre-existing; do NOT edit `phpstan-baseline.neon`). If a new error appears in the three touched/created files, fix the code.

- [ ] **Step 2: Run the neighboring Instagram test suites for regressions**

Run: `./vendor/bin/phpunit tests/Feature/QueuedWeekendPreviewTest.php tests/Feature/QueuedInstagramPostTest.php tests/Feature/AutomateInstagramPostsTest.php`
Expected: all PASS. (Known pre-existing full-suite issues — the by-date single-digit-day error and the compiled-view permission race — only appear in full runs, not these files.)

- [ ] **Step 3: Commit (only if fixes were needed)**

```bash
git add -A
git commit -m "Fix static analysis/regressions for queued weekend preview

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```
