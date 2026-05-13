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

        $this->get('/posts')->assertOk();
    }

    public function test_posts_show_redirects_to_parent_thread(): void
    {
        $thread = Thread::factory()->create();
        $post = Post::factory()->create(['thread_id' => $thread->id]);

        // Post show redirects to the thread page (posts are not standalone).
        $this->get('/posts/'.$post->id)->assertStatus(302);
    }
}
