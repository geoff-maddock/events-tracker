<?php
namespace Tests\Browser;

use App\User;
use App\Events;
use Laravel\Dusk\Dusk;
use Tests\DuskTestCase;
use Laravel\Dusk\Chrome;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EventsTest extends DuskTestCase {

    use DatabaseMigrations;

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
        $this->visit('/series')
             ->see('Series')
             ->dontSee('Error');

        //$this->assertStatus(200);
	}

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testEntitiesExample()
	{
        $this->visit('/entities')
             ->see('Series');

       // $this->assertStatus(200);
	}
}
