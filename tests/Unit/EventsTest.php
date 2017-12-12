<?php
namespace Tests\Unit;

use App\User;
use App\Events;
use Carbon\Carbon;
use Laravel\Dusk\Dusk;
use Tests\TestCase;
use Laravel\Dusk\Chrome;
use Laravel\Dusk\Browser;
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

}
