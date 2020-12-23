<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PostsTest extends TestCase
{
    private $post;

    public function setUp():void
    {
        parent::setUp();

        $this->post = Post::factory()->create();
    }

    /** @test */
    public function it_has_an_owner()
    {
        $post = Post::factory()->create();

        $this->assertInstanceOf(User::class, $post->user);
    }

    /** @test */
    public function it_knows_if_it_was_just_published()
    {
        $post = Post::factory()->create();
        $this->assertTrue($post->wasJustPublished());
        $post->created_at = Carbon::now()->subMonth();
        $this->assertFalse($post->wasJustPublished());
    }

    /** @test */
    public function it_can_detect_all_mentioned_users_in_the_body()
    {
        $post = new Post([
            'body' => '@JaneDoe wants to talk to @JohnDoe'
        ]);
        $this->assertEquals(['JaneDoe', 'JohnDoe'], $post->mentionedUsers());
    }

    /**
     * Test that a select post is visible
     *
     * @test void
     */
    public function posts_browsable()
    {
        $this->signIn();

        $user = User::find(1);
        $post = Post::first();

        // when we visit a thread page, we'll see the first 100 characters of the post (at minimum)
        $response = $this->followingRedirects()->actingAs($user)
            ->get('/posts/' . $post->id)
            ->assertSee(substr($post->body, 0, 100));
    }
}
