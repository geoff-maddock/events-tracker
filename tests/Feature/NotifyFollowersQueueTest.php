<?php

namespace Tests\Feature;

use App\Jobs\NotifyFollowers;
use App\Mail\FollowingThreadUpdate;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Follower emails for new threads, posts and events are sent from the queued
 * NotifyFollowers job, not inside the request that created the item (#2170).
 */
class NotifyFollowersQueueTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function tagFollower(Tag $tag): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->delete();
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_forum_update' => 1,
            'setting_notify_threads_by_follow' => 1,
        ]);
        Follow::create(['user_id' => $user->id, 'object_type' => 'tag', 'object_id' => $tag->id]);

        return $user->fresh();
    }

    private function signedInAuthor(): User
    {
        $author = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);
        $this->actingAs($author);

        return $author;
    }

    public function test_creating_a_thread_queues_the_follower_job_instead_of_mailing_inline(): void
    {
        Queue::fake();
        Mail::fake();
        $tag = Tag::factory()->create();
        $this->tagFollower($tag);
        $thread = Thread::factory()->make();

        $this->signedInAuthor();
        $this->post('/threads', array_merge($thread->toArray(), ['tag_list' => [$tag->id]]))->assertRedirect();

        Queue::assertPushed(NotifyFollowers::class, fn (NotifyFollowers $job) => $job->subject instanceof Thread);
        Mail::assertNothingSent();
    }

    public function test_the_job_emails_each_follower_once(): void
    {
        Mail::fake();
        $tag = Tag::factory()->create();
        $other = Tag::factory()->create();
        $follower = $this->tagFollower($tag);
        Follow::create(['user_id' => $follower->id, 'object_type' => 'tag', 'object_id' => $other->id]);
        $thread = Thread::factory()->create();
        $thread->tags()->sync([$tag->id, $other->id]);

        (new NotifyFollowers($thread))->handle(app(\App\Services\FollowerNotifier::class));

        Mail::assertSent(FollowingThreadUpdate::class, 1);
        Mail::assertSent(FollowingThreadUpdate::class, fn ($mail) => $mail->hasTo($follower->email));
    }

    public function test_the_job_runs_once_and_after_commit(): void
    {
        $job = new NotifyFollowers(Thread::factory()->create());

        // a retry after a partial run would email the same followers twice
        $this->assertSame(1, $job->tries);
        $this->assertTrue($job->afterCommit);
    }

    public function test_followers_come_with_their_profiles_loaded(): void
    {
        $tag = Tag::factory()->create();
        $this->tagFollower($tag);
        $this->tagFollower($tag);

        $followers = $tag->followers();

        $this->assertCount(2, $followers);
        $this->assertTrue($followers->every(fn (User $user) => $user->relationLoaded('profile')));
    }
}
