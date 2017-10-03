<?php

namespace Tests\Unit;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Chrome;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends DuskTestCase
{
//    /**
//     * A basic browser test example.
//     *
//     * @return void
//     */
//    public function testUserExample()
//    {
//        $user = factory(User::class)->create([
//            'email' => 'taylor@laravel.com',
//        ]);
//
//        $this->browse(function ($browser) use ($user) {
//            $browser->visit('/login')
//                ->type('email', $user->email)
//                ->type('password', 'secret')
//                ->press('Login')
//                ->assertPathIs('/home');
//        });
//    }

    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBrowseExample()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Laravel');
        });
    }

    public function testApplication()
    {
        $response = $this->withSession(['foo' => 'bar'])
            ->get('/');
    }
}
