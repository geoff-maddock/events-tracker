<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UsersTest extends TestCase
{
    /** @test */
    public function a_user_can_fetch_their_most_recent_post()
    {
        $user = create('App\User');
        $post = create('App\Post', ['created_by' => $user->id]);
        //dd($user->id);
        $post->created_by = $user->id;
        $this->assertEquals($post->id, $user->lastPost->id);
    }
}
