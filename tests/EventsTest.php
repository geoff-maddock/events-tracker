<?php


use Illuminate\Foundation\Testing\DatabaseMigrations;

class EventsTest extends TestCase {

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
        $this->visit('/events')
             ->see('Events')
             ->dontSee('Error');
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
