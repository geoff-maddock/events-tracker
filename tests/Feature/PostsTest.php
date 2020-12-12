<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostsTest extends TestCase
{
    private $post;

    public function setUp():void
    {
        parent::setUp();

        $this->post = factory(Post::class)->create();
    }

    /** @test */
    public function it_has_an_owner()
    {
        $post = factory(Post::class)->create();

        $this->assertInstanceOf(User::class, $post->user);
    }

    /** @test */
    public function it_knows_if_it_was_just_published()
    {
        $post = create(Post::class);
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
        $user = User::find(1);

        $post = Post::first();
        // when we visit a thread page
        $response = $this->followingRedirects()->actingAs($user)
            ->get('/posts/' . $post->id)
            ->assertSee($post->body);
    }
}
