<?php

namespace Tests\Unit\Policies;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Policies\EventPolicy;
use App\Policies\ForumPolicy;
use App\Policies\PostPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_event_policy_allows_create_for_active_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user);

        $policy = new EventPolicy();
        $this->assertTrue($policy->create($user));
    }

    public function test_event_policy_denies_create_when_not_authenticated(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // Note: not calling actingAs — Auth::check() will be false.

        $policy = new EventPolicy();
        $this->assertFalse($policy->create($user));
    }

    public function test_forum_policy_allows_show_for_any_user(): void
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();

        $policy = new ForumPolicy();
        $this->assertTrue($policy->show($user, $forum));
    }

    public function test_forum_policy_denies_update_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $forum = Forum::factory()->create();
        $forum->forceFill(['created_by' => $owner->id])->save();

        $policy = new ForumPolicy();
        $this->assertFalse($policy->update($other, $forum->fresh()));
    }

    public function test_forum_policy_allows_update_for_owner(): void
    {
        $owner = User::factory()->create();
        $forum = Forum::factory()->create();
        $forum->forceFill(['created_by' => $owner->id])->save();

        $policy = new ForumPolicy();
        $this->assertTrue($policy->update($owner, $forum->fresh()));
    }

    public function test_post_policy_allows_update_for_creator(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $user->id]);

        $policy = new PostPolicy();
        $this->assertTrue($policy->update($user, $post));
    }

    public function test_post_policy_denies_update_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $creator->id]);

        $policy = new PostPolicy();
        $this->assertFalse($policy->update($other, $post));
    }

    public function test_post_policy_allows_delete_for_creator(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $user->id]);

        $policy = new PostPolicy();
        $this->assertTrue($policy->delete($user, $post));
    }

    public function test_post_policy_denies_delete_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $creator->id]);

        $policy = new PostPolicy();
        $this->assertFalse($policy->delete($other, $post));
    }

    public function test_thread_policy_allows_view_for_any_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();

        $policy = new ThreadPolicy();
        $this->assertTrue($policy->view($user, $thread));
    }

    public function test_thread_policy_allows_create_when_authenticated(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user);

        $policy = new ThreadPolicy();
        $this->assertTrue($policy->create($user));
    }

    public function test_thread_policy_allows_update_for_creator(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        $thread->forceFill(['created_by' => $user->id])->save();

        $policy = new ThreadPolicy();
        $this->assertTrue($policy->update($user, $thread->fresh()));
    }

    public function test_thread_policy_denies_update_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $thread = Thread::factory()->create();
        $thread->forceFill(['created_by' => $creator->id])->save();

        $policy = new ThreadPolicy();
        $this->assertFalse($policy->update($other, $thread->fresh()));
    }

    public function test_thread_policy_all_returns_true(): void
    {
        $thread = Thread::factory()->create();

        $policy = new ThreadPolicy();
        $this->assertTrue($policy->all($thread));
    }
}
