<?php
namespace Tests\Feature;

use App\Activity;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;

class ImpersonateUsersTest extends TestCase
{
    /** @test  */
    function non_admins_cannot_impersonate_users()
    {
        $user = factory(User::class)->create();

        $this->get('/impersonate/' . $user->id)
            ->assertRedirect('login');

        $this->actingAs($user);

        $this->get('/impersonate/' . $user->id)->assertStatus(403);
    }

//    /** @test */
//    function admins_can_impersonate_users()
//    {
//        $admin = factory('App\User')->create(['type' => 'admin']);
//        $user = factory('App\User')->create();
//
//        $this->actingAs($admin);
//
//        $this->get('/impersonate/' . $user->id);
//        $this->assertEquals(auth()->user()->id, $user->id);
//    }
}