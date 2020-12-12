<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProfilesTest extends TestCase
{
    /** @test */
    public function a_user_has_a_profile()
    {
        $profile = factory('App\Profile')->create();
        $user = $profile->user;

        $response = $this->get('/users/' . $user->id);

        $response->assertStatus(200);
        $response->assertSee($profile->first_name);
    }
}
