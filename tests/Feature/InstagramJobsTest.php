<?php

namespace Tests\Feature;

use App\Jobs\Instagram\PostEventStoryToInstagram;
use App\Jobs\Instagram\PostTagToInstagram;
use App\Models\Event;
use App\Models\JobStatus;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\JobCompleted;
use App\Services\Integrations\InstagramEventPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * The story and tag jobs: each tracks its progress in a JobStatus row and
 * tells the user how it ended (#2404). PostEventToInstagram is covered in
 * QueuedInstagramPostTest.
 */
class InstagramJobsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->user = User::factory()->create();
    }

    public function test_the_story_job_is_queued_with_a_status_row_and_retries(): void
    {
        $event = Event::factory()->create(['name' => 'Warehouse Night']);

        $job = new PostEventStoryToInstagram($event, $this->user->id);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'user_id' => $this->user->id,
            'type' => 'instagram_story',
            'label' => 'Instagram story: Warehouse Night',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
        $this->assertSame(3, $job->tries);
    }

    public function test_the_story_job_posts_and_reports_success(): void
    {
        $event = Event::factory()->create();
        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postStory')->once()
            ->with(Mockery::on(fn ($posted) => $posted->is($event)), $this->user->id)
            ->andReturn(555);

        $job = new PostEventStoryToInstagram($event, $this->user->id);
        $job->handle($poster);

        $status = JobStatus::findOrFail($job->jobStatusId);
        $this->assertSame(JobStatus::STATUS_SUCCEEDED, $status->status);
        $this->assertStringContainsString('media id: 555', $status->message);
        Notification::assertSentTo($this->user, JobCompleted::class);
    }

    public function test_a_failed_story_job_is_marked_failed_with_the_reason(): void
    {
        $job = new PostEventStoryToInstagram(Event::factory()->create(), $this->user->id);
        $job->failed(new RuntimeException('Instagram is not linked.'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
            'message' => 'Instagram is not linked.',
        ]);
        Notification::assertSentTo($this->user, JobCompleted::class);
    }

    public function test_the_tag_job_is_never_retried(): void
    {
        $tag = Tag::factory()->create(['name' => 'Techno']);

        $job = new PostTagToInstagram($tag, $this->user->id);

        // a retry after the carousel published would post it twice
        $this->assertSame(1, $job->tries);
        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'type' => 'instagram_post',
            'label' => 'Instagram tag post: Techno',
            'status' => JobStatus::STATUS_QUEUED,
        ]);
    }

    public function test_the_tag_job_posts_and_reports_success(): void
    {
        $tag = Tag::factory()->create(['name' => 'Techno']);
        $poster = Mockery::mock(InstagramEventPoster::class);
        $poster->shouldReceive('postTagCarousel')->once()
            ->with(Mockery::on(fn ($posted) => $posted->is($tag)), $this->user->id)
            ->andReturn(777);

        $job = new PostTagToInstagram($tag, $this->user->id);
        $job->handle($poster);

        $status = JobStatus::findOrFail($job->jobStatusId);
        $this->assertSame(JobStatus::STATUS_SUCCEEDED, $status->status);
        $this->assertStringContainsString('upcoming Techno events', $status->message);
        $this->assertStringContainsString('media id: 777', $status->message);
        Notification::assertSentTo($this->user, JobCompleted::class);
    }

    public function test_a_failed_tag_job_is_marked_failed_with_the_reason(): void
    {
        $job = new PostTagToInstagram(Tag::factory()->create(), $this->user->id);
        $job->failed(new RuntimeException('No upcoming events with photos.'));

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->jobStatusId,
            'status' => JobStatus::STATUS_FAILED,
            'message' => 'No upcoming events with photos.',
        ]);
        Notification::assertSentTo($this->user, JobCompleted::class);
    }
}
