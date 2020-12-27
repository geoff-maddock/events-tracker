<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ImpersonateUsersTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test  */
    public function non_admins_cannot_impersonate_users()
    {
        // create a new user
        $user = User::factory()->create();

        // try to impersonate the user without signing in
        $response = $this->withExceptionHandling()
            ->get('/impersonate/' . $user->id)
            ->assertStatus(403);
    }

    /** @test */
    public function admins_can_impersonate_users()
    {
        // create a new user
        $user = User::factory()->create();

        // create a new admin user
        $admin = User::factory()->create();
        $admin->assignGroup('admin');

        $this->actingAs($admin);

        $response = $this->get('/impersonate/' . $user->id);

        $response->assertStatus(302);
    }
}
