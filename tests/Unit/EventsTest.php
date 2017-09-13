<?php
namespace Tests\Unit;

use App\User;
use App\Events;
use Carbon\Carbon;
use Laravel\Dusk\Dusk;
use Tests\TestCase;
use Laravel\Dusk\Chrome;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EventsTest extends TestCase {

    //use DatabaseMigrations;

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
	public function testEventsExample()
	{
        $this->browse(function ($browser) {
            $browser->visit('/events')
                -> assertSee('Events');
        });
	    /*
        $this->visit('/events')
             ->see('Events')
             ->dontSee('Error');
	    */
       //$response->assertStatus(200);
	}

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testSeriesExample()
	{
        $this->browse(function ($browser) {
            $browser->visit('/series')
                -> assertSee('Series');
        });
        $this->assertStatus(200);
	}

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testEntitiesExample()
	{
        $this->browse(function ($browser) {
            $browser->visit('/entities')
                -> assertSee('Entities');
        });
        $this->assertStatus(200);
	}
}
