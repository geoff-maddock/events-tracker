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
