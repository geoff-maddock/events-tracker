<?php

namespace Tests\Feature;

use Tests\TestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test
     */
    public function a_search_returns_results()
    {
        $this->signIn();
        $faker = Faker::create();
        $keyword = $faker->domainWord;

        $response = $this->get('/search?keyword=' . $keyword);

        $response->assertStatus(200);
        $response->assertSee($keyword);
    }
}
