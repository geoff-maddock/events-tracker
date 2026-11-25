<?php

namespace Tests\Feature;

use App\Mail\FollowingPostUpdate;
use App\Mail\FollowingThreadUpdate;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\FollowerNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who hears about a new thread or reply (#2404): followers of the thread, of
 * its tags and of its series, by their own settings, once each.
 */
class FollowerNotifierForumTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Thread $thread;

    private Tag $tag;

    private Series $series;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->tag = Tag::factory()->create();
        $this->series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $this->thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $this->thread->tags()->attach($this->tag->id);
        $this->thread->series()->attach($this->series->id);
    }

    /**
     * A user who follows $subjects, with the two forum settings as given.
     *
     * @param list<Model> $subjects
     */
    private function follower(array $subjects, int $forumUpdates = 1, int $threadsByFollow = 1): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_forum_update' => $forumUpdates,
            'setting_notify_threads_by_follow' => $threadsByFollow,
        ]);
        foreach ($subjects as $subject) {
            Follow::factory()->of($subject)->create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function reply(): Post
    {
        return Post::factory()->create(['thread_id' => $this->thread->id]);
    }

    /**
     * @param class-string<\Illuminate\Mail\Mailable> $mail
     */
    private function assertMailedOnce(string $mail, User ...$users): void
    {
        Mail::assertSent($mail, count($users));
        foreach ($users as $user) {
            Mail::assertSent($mail, fn ($sent) => $sent->hasTo($user->email));
        }
    }

    #[Test]
    public function a_new_thread_reaches_followers_of_its_tags_and_its_series_once_each(): void
    {
        $byTag = $this->follower([$this->tag]);
        $bySeries = $this->follower([$this->series]);
        $byBoth = $this->follower([$this->tag, $this->series]);

        app(FollowerNotifier::class)->thread($this->thread);

        $this->assertMailedOnce(FollowingThreadUpdate::class, $byTag, $bySeries, $byBoth);
        Mail::assertNotSent(FollowingPostUpdate::class);
    }

    #[Test]
    public function a_new_thread_is_only_for_those_who_asked_for_threads_by_follow(): void
    {
        // forum updates on, threads-by-follow off (#1853), for a tag and a series follower
        $this->follower([$this->tag], threadsByFollow: 0);
        $this->follower([$this->series], threadsByFollow: 0);
        // threads-by-follow on, forum updates off
        $this->follower([$this->tag], forumUpdates: 0);
        $this->follower([$this->series], forumUpdates: 0);
        // no profile at all
        Follow::factory()->of($this->tag)->create();
        Follow::factory()->of($this->series)->create();

        app(FollowerNotifier::class)->thread($this->thread);

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_series_follower_does_not_hear_about_a_thread_they_cannot_see(): void
    {
        $follower = $this->follower([$this->series]);
        $this->thread->update(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        app(FollowerNotifier::class)->thread($this->thread->fresh());
        app(FollowerNotifier::class)->post($this->reply());
        Mail::assertNothingSent();

        $this->thread->update(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        app(FollowerNotifier::class)->thread($this->thread->fresh());
        $this->assertMailedOnce(FollowingThreadUpdate::class, $follower);
    }

    #[Test]
    public function a_reply_reaches_followers_of_the_thread_its_tags_and_its_series_once_each(): void
    {
        $byThread = $this->follower([$this->thread]);
        $byTag = $this->follower([$this->tag]);
        $bySeries = $this->follower([$this->series]);
        $byAll = $this->follower([$this->thread, $this->tag, $this->series]);
        // replies don't need the threads-by-follow setting, only forum updates
        $noThreadsByFollow = $this->follower([$this->tag], threadsByFollow: 0);

        app(FollowerNotifier::class)->post($this->reply());

        $this->assertMailedOnce(FollowingPostUpdate::class, $byThread, $byTag, $bySeries, $byAll, $noThreadsByFollow);
        Mail::assertNotSent(FollowingThreadUpdate::class);
    }

    #[Test]
    public function a_reply_is_not_sent_to_followers_with_forum_updates_off(): void
    {
        $this->follower([$this->thread], forumUpdates: 0);
        $this->follower([$this->tag], forumUpdates: 0);
        $this->follower([$this->series], forumUpdates: 0);
        Follow::factory()->of($this->thread)->create();

        app(FollowerNotifier::class)->post($this->reply());

        Mail::assertNothingSent();
    }
}
