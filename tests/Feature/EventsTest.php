<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Events;
use Carbon\Carbon;
use Laravel\Dusk\Dusk;
use Tests\TestCase;
use Laravel\Dusk\Chrome;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventsTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /**
     * Test that events are browsable
     *
     * @test void
     */
    public function eventsBrowsable()
    {
        $this->get('/events')->assertSee('Events');
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $response = $this->call('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function calendarBrowsable()
    {
        $response = $this->call('GET', '/calendar');

        $this->assertEquals(200, $response->getStatusCode());

        $response->assertSee('Events Calendar');
    }
}
