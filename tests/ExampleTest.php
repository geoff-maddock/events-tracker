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
        $this->visit('/')
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
        $this->visit('/')
             ->see('Series')
             ->dontSee('Error');
	}
}
