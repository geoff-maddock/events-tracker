<?php

namespace Tests\Feature;

use App\Mail\FollowingThreadUpdate;
use App\Models\Follow;
use App\Models\Forum;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ThreadFollowNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Build a (user, profile) pair with explicit notification settings.
     */
    protected function userWithSettings(array $settings): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(array_merge([
            'user_id' => $user->id,
            'setting_forum_update' => 1,
            'setting_notify_threads_by_follow' => 0,
        ], $settings));

        return $user->fresh();
    }

    protected function followTag(User $user, Tag $tag): void
    {
        Follow::create([
            'user_id' => $user->id,
            'object_type' => 'tag',
            'object_id' => $tag->id,
        ]);
    }

    protected function postThreadWithTag(Tag $tag): void
    {
        $forum = Forum::factory()->create();

        $payload = [
            'name' => 'A discussion thread for notification testing',
            'body' => 'Some body content for the thread.',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'forum_id' => $forum->id,
            'tag_list' => [$tag->id => (string) $tag->id],
        ];

        $this->post('/threads', $payload)->assertStatus(302);
    }

    /** @test */
    public function tag_follower_is_not_notified_when_new_setting_is_off_by_default()
    {
        Mail::fake();

        $author = $this->userWithSettings([]);
        $this->signIn($author);

        $follower = $this->userWithSettings([
            'setting_forum_update' => 1,
            'setting_notify_threads_by_follow' => 0,
        ]);

        $tag = Tag::factory()->create();
        $this->followTag($follower, $tag);

        $this->postThreadWithTag($tag);

        Mail::assertNotSent(FollowingThreadUpdate::class, function ($mail) use ($follower) {
            return $mail->hasTo($follower->email);
        });
    }

    /** @test */
    public function tag_follower_is_notified_when_opted_in_via_new_setting()
    {
        Mail::fake();

        $author = $this->userWithSettings([]);
        $this->signIn($author);

        $follower = $this->userWithSettings([
            'setting_forum_update' => 1,
            'setting_notify_threads_by_follow' => 1,
        ]);

        $tag = Tag::factory()->create();
        $this->followTag($follower, $tag);

        $this->postThreadWithTag($tag);

        Mail::assertSent(FollowingThreadUpdate::class, function ($mail) use ($follower) {
            return $mail->hasTo($follower->email);
        });
    }

    /** @test */
    public function forum_update_off_still_suppresses_even_when_new_setting_is_on()
    {
        Mail::fake();

        $author = $this->userWithSettings([]);
        $this->signIn($author);

        $follower = $this->userWithSettings([
            'setting_forum_update' => 0,
            'setting_notify_threads_by_follow' => 1,
        ]);

        $tag = Tag::factory()->create();
        $this->followTag($follower, $tag);

        $this->postThreadWithTag($tag);

        Mail::assertNotSent(FollowingThreadUpdate::class, function ($mail) use ($follower) {
            return $mail->hasTo($follower->email);
        });
    }

    /** @test */
    public function new_setting_defaults_to_zero_on_a_fresh_profile()
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertSame(0, (int) $profile->fresh()->setting_notify_threads_by_follow);
    }
}
