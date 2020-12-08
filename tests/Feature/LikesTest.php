<?php

namespace Tests\Feature;

use App\Entity;
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

        $post = create('App\Post');

        $this->get('/posts/' . $post->id . '/like');

        $this->assertCount(1, $post->likes);
    }

    /** @test */
    public function an_authenticated_user_can_like_a_thread()
    {
        $this->signIn();

        $thread = create('App\Thread');

        $this->get('/threads/' . $thread->id . '/like');

        $this->assertCount(1, $thread->likes);
    }
}
