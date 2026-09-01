<?php

namespace Tests\Feature;

use App\Jobs\Instagram\PostTodaysPreviewToInstagram;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventShare;
use App\Models\Group;
use App\Models\JobStatus;
use App\Models\Photo;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\Visibility;
use App\Notifications\JobCompleted;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class QueuedTodaysPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function todaysEvent(User $user, bool $withPhoto = true, int $hour = 20): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
            'start_at' => Carbon::today()->addHours($hour),
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

    /**
     * Give an event a known attending-response count so the top-10 ranking is
     * deterministic. EventResponse has no factory, and (event_id, user_id) must
     * stay distinct, so each response gets its own user.
     */
    private function withResponses(Event $event, int $count): Event
    {
        foreach (range(1, $count) as $ignored) {
            EventResponse::create([
                'event_id' => $event->id,
                'user_id' => User::factory()->create(['user_status_id' => 1])->id,
                'response_type_id' => ResponseType::ATTENDING,
            ]);
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

    public function test_service_throws_when_no_events_are_scheduled_today(): void
    {
        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No events found for today.');

        $poster->postTodaysPreview(null);
    }

    public function test_service_posts_stories_and_skips_events_without_photos(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $withPhoto = $this->todaysEvent($user, true, 20);
        $this->todaysEvent($user, false, 22); // no photo -> skipped

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postTodaysPreview($user->id);

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
        $this->todaysEvent($user, false, 20); // only event has no photo

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stories could be posted. Ensure the selected events have photos.');

        $poster->postTodaysPreview($user->id);
    }

    public function test_service_ignores_events_outside_today(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $today = $this->todaysEvent($user, true, 20);
        $this->todaysEvent($user, true, 48);  // tomorrow
        $this->todaysEvent($user, true, -12); // yesterday

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postTodaysPreview($user->id);

        $this->assertSame(1, $result['total']);
        $this->assertDatabaseHas('event_shares', ['event_id' => $today->id]);
    }

    public function test_service_excludes_cancelled_and_non_public_events(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $visible = $this->todaysEvent($user, true, 20);
        $this->todaysEvent($user, true, 21)->update(['cancelled_at' => Carbon::now()]);
        $this->todaysEvent($user, true, 22)->update(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postTodaysPreview($user->id);

        $this->assertSame(1, $result['total']);
        $this->assertDatabaseHas('event_shares', ['event_id' => $visible->id]);
    }

    public function test_service_caps_the_run_at_ten_most_popular_events(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        // 12 events, none with responses, so the ranking tie-breaks on start
        // time and the two latest fall outside the cap.
        $dropped = [];
        foreach (range(1, 12) as $i) {
            $event = $this->todaysEvent($user, true, 8 + $i);
            if ($i > 10) {
                $dropped[] = $event->id;
            }
        }

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postTodaysPreview($user->id);

        $this->assertSame(['posted' => 10, 'skipped' => 0, 'total' => 10], $result);
        foreach ($dropped as $id) {
            $this->assertDatabaseMissing('event_shares', ['event_id' => $id]);
        }
    }

    public function test_service_posts_the_selection_in_chronological_order(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        // Popularity and start time deliberately disagree: the most-responded
        // event starts last, so ranking order != posting order.
        $late = $this->withResponses($this->todaysEvent($user, true, 22), 10);
        $middle = $this->withResponses($this->todaysEvent($user, true, 20), 5);
        $early = $this->withResponses($this->todaysEvent($user, true, 18), 1);

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $poster->postTodaysPreview($user->id);

        $postedOrder = EventShare::orderBy('id')->pluck('event_id')->all();

        $this->assertSame([$early->id, $middle->id, $late->id], $postedOrder);
    }

    public function test_dispatching_the_job_creates_a_queued_job_status(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);

        $job = new PostTodaysPreviewToInstagram($user->id);

        $this->assertNotNull($job->jobStatusId);
        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'user_id' => $user->id,
            'type' => 'instagram_todays_preview',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
    }

    public function test_successful_job_marks_status_succeeded_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postTodaysPreview')->once()->with($user->id)
            ->andReturn(['posted' => 8, 'skipped' => 2, 'total' => 10]);

        $job = new PostTodaysPreviewToInstagram($user->id);
        $job->handle($poster);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_SUCCEEDED,
            'message' => "Today's preview posted: 8 stories published, 2 skipped (no photo).",
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }

    public function test_single_story_success_message_is_singular_without_skips(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postTodaysPreview')->once()
            ->andReturn(['posted' => 1, 'skipped' => 0, 'total' => 1]);

        $job = new PostTodaysPreviewToInstagram($user->id);
        $job->handle($poster);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'message' => "Today's preview posted: 1 story published.",
        ]);
    }

    public function test_failed_job_marks_status_failed_and_notifies_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['user_status_id' => 1]);

        $job = new PostTodaysPreviewToInstagram($user->id);
        $job->failed(new RuntimeException('No events found for today.'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
            'message' => 'No events found for today.',
        ]);
        Notification::assertSentTo($user, JobCompleted::class);
    }

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

        $response = $this->actingAs($admin)->get('/events/instagram-todays-preview');

        $response->assertRedirect();
        Queue::assertPushed(PostTodaysPreviewToInstagram::class, function ($job) use ($admin) {
            return $job->userId === $admin->id;
        });
    }

    public function test_route_does_not_queue_for_non_admins(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);

        $response = $this->actingAs($user)->get('/events/instagram-todays-preview');

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

        $response = $this->actingAs($admin)->get('/events/instagram-todays-preview');

        $response->assertRedirect();
        Queue::assertNothingPushed();
    }

    public function test_ajax_route_queues_the_job_and_returns_json_toast(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->getJson('/events/instagram-todays-preview');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'title' => 'Queued',
            ]);
        Queue::assertPushed(PostTodaysPreviewToInstagram::class, function ($job) use ($admin) {
            return $job->userId === $admin->id;
        });
    }

    public function test_events_index_shows_the_menu_item_to_super_admins_only(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/events')
            ->assertOk()
            ->assertSee("Today's Preview", false);

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user)->get('/events')
            ->assertOk()
            ->assertDontSee("Today's Preview", false);
    }

    public function test_ajax_route_returns_json_error_for_non_admins(): void
    {
        Queue::fake();
        $this->mockInstagramCredentials();

        $user = User::factory()->create(['user_status_id' => 1]);

        $response = $this->actingAs($user)->getJson('/events/instagram-todays-preview');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'title' => 'Error',
            ]);
        Queue::assertNothingPushed();
    }
}
