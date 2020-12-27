<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test */
    public function a_user_can_fetch_their_most_recent_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $user->id]);

        $post->created_by = $user->id;
        $this->assertEquals($post->id, $user->lastPost->id);
    }
}
