<?php

namespace Tests\Feature\Web;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostsControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_posts_index_renders(): void
    {
        Post::factory()->count(2)->create();

        // /posts is behind the show_forum gate, which today only admins pass
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $this->actingAs($admin->fresh());

        $this->get('/posts')->assertOk();
    }

    /**
     * Previously this returned 200 with a stringified RedirectResponse as the
     * body, because the controller was typed `: string`.
     */
    public function test_posts_index_redirects_users_who_cannot_see_the_forum(): void
    {
        $response = $this->get('/posts');

        $response->assertRedirect();
        $this->assertStringNotContainsString('HTTP/1.0 302', $response->getContent());
    }

    public function test_posts_show_redirects_to_parent_thread(): void
    {
        $thread = Thread::factory()->create();
        $post = Post::factory()->create(['thread_id' => $thread->id]);

        // Post show redirects to the thread page (posts are not standalone).
        $this->get('/posts/'.$post->id)->assertStatus(302);
    }
}
