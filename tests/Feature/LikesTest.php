<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class LikesTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    #[Test]
    public function a_guest_cannot_like_anything()
    {
        $this->withExceptionHandling()
            ->post('/posts/1/like')
            ->assertRedirect('/');
    }

    #[Test]
    public function an_authenticated_user_can_like_a_post()
    {
        $this->signIn();

        $post = Post::factory()->create();
        // the likes column, not the likes() relation it shares a name with
        $likes = (int) $post->getAttribute('likes');

        $this->post('/posts/' . $post->id . '/like');

        $post->refresh();

        $this->assertEquals($likes + 1, $post->getAttribute('likes'));
    }

    #[Test]
    public function an_authenticated_user_can_like_a_thread()
    {
        $this->signIn();

        $thread = Thread::factory()->create();
        $likes = (int) $thread->getAttribute('likes');

        $this->post('/threads/' . $thread->id . '/like');

        $thread->refresh();

        $this->assertEquals($likes + 1, $thread->getAttribute('likes'));
    }
}
