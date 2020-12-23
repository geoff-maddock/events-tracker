<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LikesTest extends TestCase
{
    // use DatabaseMigrations;

    /** @test */
    public function a_guest_cannot_like_anything()
    {
        $this->withExceptionHandling()
            ->get('/posts/1/like')
            ->assertRedirect('/');
    }

    /** @test */
    public function an_authenticated_user_can_like_a_post()
    {
        $this->signIn();

        $post = Post::factory()->create();
        $likes = $post->likes;

        $this->get('/posts/' . $post->id . '/like');

        $post->refresh();

        $this->assertEquals($likes + 1, $post->likes);
    }

    /** @test */
    public function an_authenticated_user_can_like_a_thread()
    {
        $this->signIn();

        $thread = Thread::factory()->create();
        $likes = $thread->likes;

        $this->get('/threads/' . $thread->id . '/like');

        $thread->refresh();

        $this->assertEquals($likes + 1, $thread->likes);
    }
}
