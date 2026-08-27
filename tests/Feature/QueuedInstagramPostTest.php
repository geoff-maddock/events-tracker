<?php

namespace Tests\Feature;

use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostEventToInstagram;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Group;
use App\Models\JobStatus;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use App\Notifications\JobCompleted;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class QueuedInstagramPostTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function eventWithPhoto(User $user): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
        ]);

        $photo = Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'test.jpg',
            'thumbnail' => 'test_thumb.jpg',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $event->photos()->attach($photo->id);

        return $event;
    }

    private function mockInstagramCredentials(): void
    {
        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $this->app->instance(Instagram::class, $instagram);
    }

    public function test_carousel_web_route_queues_a_job(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        $response = $this->actingAs($user)->get('/events/' . $event->id . '/instagram-post');

        $response->assertRedirect();
        Queue::assertPushed(PostEventToInstagram::class, function ($job) use ($event, $user) {
            return $job->event->id === $event->id
                && $job->carousel === true
                && $job->userId === $user->id;
        });
    }

    public function test_story_web_route_queues_a_job_for_admins(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $superGroup = Group::firstOrCreate(['name' => 'super_admin']);
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->groups()->attach($superGroup->id);
        $event = $this->eventWithPhoto($admin);

        $response = $this->actingAs($admin)->get('/events/' . $event->id . '/instagram-story-post');

        $response->assertRedirect();
        Queue::assertPushed(PostEventStoryToInstagram::class);
    }

    private function shareEventToInstagram(Event $event, ?\Carbon\Carbon $postedAt): EventShare
    {
        return EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '555',
            'created_by' => $event->created_by,
            'posted_at' => $postedAt,
        ]);
    }

    public function test_recent_instagram_post_blocks_manual_repost(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);
        $this->shareEventToInstagram($event, now()->subDay());

        $response = $this->actingAs($user)->getJson('/events/' . $event->id . '/instagram-post');

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'title' => 'Already posted']);
        Queue::assertNotPushed(PostEventToInstagram::class);
    }

    public function test_post_older_than_three_days_does_not_block_repost(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);
        $this->shareEventToInstagram($event, now()->subDays(4));

        $this->actingAs($user)->getJson('/events/' . $event->id . '/instagram-post')
            ->assertStatus(200);
        Queue::assertPushed(PostEventToInstagram::class);
    }

    public function test_failed_share_attempt_does_not_block_repost(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);
        // posted_at is null when a share attempt failed — that should not throttle
        $this->shareEventToInstagram($event, null);

        $this->actingAs($user)->getJson('/events/' . $event->id . '/instagram-post')
            ->assertStatus(200);
        Queue::assertPushed(PostEventToInstagram::class);
    }

    public function test_admin_is_exempt_from_repost_throttle(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $adminGroup = Group::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['user_status_id' => 1]);
        $admin->groups()->attach($adminGroup->id);
        $event = $this->eventWithPhoto($admin);
        $this->shareEventToInstagram($event, now()->subDay());

        $this->actingAs($admin)->getJson('/events/' . $event->id . '/instagram-post')
            ->assertStatus(200);
        Queue::assertPushed(PostEventToInstagram::class);
    }

    public function test_api_carousel_blocks_recent_repost_with_429(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');
        $event = $this->eventWithPhoto($user);
        $this->shareEventToInstagram($event, now()->subDay());

        $this->postJson('/api/events/' . $event->id . '/instagram-post')
            ->assertStatus(429)
            ->assertJson(['success' => false]);
        Queue::assertNotPushed(PostEventToInstagram::class);
    }

    public function test_api_carousel_returns_job_status_id(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');
        $event = $this->eventWithPhoto($user);

        $response = $this->postJson('/api/events/' . $event->id . '/instagram-post');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'queued' => true]);
        $this->assertIsInt($response->json('job_status_id'));
        Queue::assertPushed(PostEventToInstagram::class);
    }

    public function test_dispatching_a_job_creates_a_queued_job_status(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        $job = new PostEventToInstagram($event, true, $user->id);

        $this->assertNotNull($job->jobStatusId);
        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'user_id' => $user->id,
            'type' => 'instagram_post',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
    }

    public function test_successful_job_marks_status_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postCarousel')->once()->andReturn(555);

        $job = new PostEventToInstagram($event, true, $user->id);
        $job->handle($poster);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_SUCCEEDED,
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }

    public function test_failed_job_marks_status_failed_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        $job = new PostEventToInstagram($event, true, $user->id);
        $job->failed(new RuntimeException('Instagram is not linked.'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
            'message' => 'Instagram is not linked.',
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }

    public function test_job_records_event_share_when_run_synchronously(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(111);
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);
        $this->app->instance(Instagram::class, $instagram);

        PostEventToInstagram::dispatch($event, true, $user->id);

        $this->assertDatabaseHas('event_shares', [
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '555',
        ]);
    }

    public function test_carousel_upload_failure_preserves_underlying_cause(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        $instagram->shouldReceive('uploadCarouselPhoto')
            ->andThrow(new RuntimeException('IG API 400: media aspect ratio not supported'));

        $poster = new InstagramEventPoster($instagram);

        try {
            $poster->postCarousel($event, $user->id);
            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            // The underlying cause is surfaced in the message so Sentry groups by
            // actual failure instead of one opaque "Please try again." bucket...
            $this->assertStringContainsString('media aspect ratio not supported', $e->getMessage());
            // ...and the original exception is chained so the stack trace survives.
            $this->assertNotNull($e->getPrevious());
            $this->assertSame(
                'IG API 400: media aspect ratio not supported',
                $e->getPrevious()->getMessage()
            );
        }
    }

    public function test_carousel_is_trimmed_to_the_instagram_ten_item_limit(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $event = $this->eventWithPhoto($user);

        // Attach 15 additional (non-primary) photos so the carousel would
        // otherwise contain 16 items — well over Instagram's limit of 10,
        // which makes createCarousel fail wholesale (EVENTREPO-X9).
        for ($i = 0; $i < 15; $i++) {
            $photo = Photo::factory()->create([
                'is_primary' => 0,
                'path' => "extra_{$i}.jpg",
                'thumbnail' => "extra_{$i}_thumb.jpg",
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $event->photos()->attach($photo->id);
        }

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        // No more than 10 containers should ever be uploaded or published.
        $instagram->shouldReceive('uploadCarouselPhoto')->times(10)->andReturn(111);
        $instagram->shouldReceive('checkBatchStatus')
            ->with(Mockery::on(fn ($ids) => is_array($ids) && count($ids) === 10))
            ->andReturn(true);
        $instagram->shouldReceive('createCarousel')
            ->with(Mockery::on(fn ($ids) => is_array($ids) && count($ids) === 10), Mockery::any())
            ->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $poster = new InstagramEventPoster($instagram);
        $result = $poster->postCarousel($event, $user->id);

        $this->assertSame(555, $result);
    }

    public function test_job_status_show_endpoint_returns_json_for_owner(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $status = JobStatus::create([
            'user_id' => $user->id,
            'type' => 'instagram_post',
            'label' => 'Test job',
            'status' => JobStatus::STATUS_RUNNING,
        ]);

        $this->actingAs($user)
            ->getJson('/job-status/' . $status->id)
            ->assertStatus(200)
            ->assertJson(['id' => $status->id, 'status' => 'running', 'finished' => false]);
    }

    public function test_job_status_show_endpoint_forbidden_for_other_user(): void
    {
        $owner = User::factory()->create(['user_status_id' => 1]);
        $other = User::factory()->create(['user_status_id' => 1]);
        $status = JobStatus::create([
            'user_id' => $owner->id,
            'type' => 'instagram_post',
            'status' => JobStatus::STATUS_QUEUED,
        ]);

        $this->actingAs($other)
            ->getJson('/job-status/' . $status->id)
            ->assertStatus(403);
    }

    public function test_job_status_index_requires_authentication(): void
    {
        $this->withExceptionHandling()
            ->get('/job-status')
            ->assertRedirect('/login');
    }
}
