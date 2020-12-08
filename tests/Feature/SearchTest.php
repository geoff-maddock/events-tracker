<?php

namespace Tests\Feature;

use Tests\TestCase;
use Faker\Factory as Faker;

class SearchTest extends TestCase
{
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
