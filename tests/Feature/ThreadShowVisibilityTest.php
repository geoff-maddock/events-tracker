<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The thread page used to check only the admin-only show_forum gate, so members
 * were bounced from their own private threads (#2342). It now applies the same
 * rule as the thread lists.
 */
class ThreadShowVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->author = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function thread(int $visibility): Thread
    {
        // Thread stamps created_by from the signed-in user
        $this->actingAs($this->author);
        $thread = Thread::factory()->create(['visibility_id' => $visibility]);
        $this->app['auth']->forgetGuards();

        return $thread;
    }

    public function test_an_author_can_open_their_own_private_thread(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);

        $this->actingAs($this->author)->get('/threads/'.$thread->id)->assertOk();
    }

    public function test_another_member_cannot_open_a_private_thread(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($other)->get('/threads/'.$thread->id)->assertNotFound();
    }

    public function test_a_guest_cannot_open_a_guarded_thread_but_a_member_can(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_GUARDED);
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->get('/threads/'.$thread->id)->assertNotFound();
        $this->actingAs($other)->get('/threads/'.$thread->id)->assertOk();
    }

    public function test_a_guest_can_open_a_public_thread(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PUBLIC);

        $this->get('/threads/'.$thread->id)->assertOk();
    }

    // the forum page (admin-only) replaced its filtered query with a public-only one
    public function test_the_forum_page_lists_the_viewers_own_private_thread(): void
    {
        $this->author->assignGroup('admin');
        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $forum = Forum::find($thread->forum_id);

        $this->actingAs($this->author)->get('/forums/'.$forum->id)->assertOk()->assertSee($thread->name);
    }
}
