<?php
namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class ImpersonateUsersTest extends TestCase
{
    /** @test  */
    function non_admins_cannot_impersonate_users()
    {
        // create a new user
        $user = factory(User::class)->create();

        // try to impersonate the user without signing in
        $response = $this->withExceptionHandling()
            ->get('/impersonate/' . $user->id)
            ->assertStatus(403);


    }

    /** @test */
    function admins_can_impersonate_users()
    {
        // create a new user
        $user = factory(User::class)->create();

        // create a new admin user
        $admin = factory(User::class)->create();
        $admin->assignGroup('admin');

        $this->actingAs($admin);

        $response = $this->get('/impersonate/' . $user->id);

        $response->assertStatus(302);
    }
}
