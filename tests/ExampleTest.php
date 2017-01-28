<?php

class ExampleTest extends TestCase {

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
             ->dontSee('Rails');
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
	}

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testEntitiesExample()
	{
        $this->visit('/entities')
             ->see('Series')
             ->dontSee('Error');
	}
}
