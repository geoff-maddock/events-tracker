<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Group;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A forum can't be deleted while it has threads; an empty one is deleted
 * with its thread categories (both used to be a foreign-key 500).
 */
class ForumDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
    }

    private function forumWithCategory(): Forum
    {
        $forum = Forum::factory()->create();
        ThreadCategory::factory()->create(['forum_id' => $forum->id]);

        return $forum;
    }

    private function addThread(Forum $forum): void
    {
        $this->actingAs($this->admin);
        Thread::factory()->create(['forum_id' => $forum->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
    }

    public function test_a_forum_with_threads_is_not_deleted(): void
    {
        $forum = $this->forumWithCategory();
        $this->addThread($forum);

        $this->actingAs($this->admin)->from('/forums')->delete("/forums/{$forum->id}")
            ->assertRedirect('/forums')
            ->assertSessionHas('flash_message');
        $this->assertNotNull(Forum::find($forum->id));

        $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/forums/{$forum->id}")->assertStatus(409);
        $this->assertNotNull(Forum::find($forum->id));
        $this->assertSame(1, ThreadCategory::where('forum_id', $forum->id)->count());
    }

    public function test_an_empty_forum_is_deleted_with_its_thread_categories(): void
    {
        $forum = $this->forumWithCategory();

        $this->actingAs($this->admin)->delete("/forums/{$forum->id}")->assertRedirect('/forums');
        $this->assertNull(Forum::find($forum->id));
        $this->assertSame(0, ThreadCategory::where('forum_id', $forum->id)->count());

        $forum = $this->forumWithCategory();

        $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/forums/{$forum->id}")->assertNoContent();
        $this->assertNull(Forum::find($forum->id));
        $this->assertSame(0, ThreadCategory::where('forum_id', $forum->id)->count());
    }
}
