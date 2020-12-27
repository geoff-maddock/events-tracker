<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfilesTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test */
    public function a_user_has_a_profile()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user
        ]);

        $response = $this->get('/users/' . $user->id);

        $response->assertStatus(200);
        $response->assertSee($profile->first_name);
    }
}
