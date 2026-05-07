<?php

namespace Tests\Feature;

use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function shorten_endpoint_creates_a_short_url_record()
    {
        $url = 'http://localhost/events/apply-filter?filters%5Bname%5D=Concert';

        $response = $this->postJson(route('short-url.shorten'), ['url' => $url]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['code', 'short_url']);

        $this->assertDatabaseHas('short_urls', ['url' => $url]);
    }

    /** @test */
    public function shorten_endpoint_returns_existing_record_for_same_url()
    {
        $url = 'http://localhost/events/apply-filter?filters%5Bname%5D=Concert';

        $this->postJson(route('short-url.shorten'), ['url' => $url]);
        $response = $this->postJson(route('short-url.shorten'), ['url' => $url]);

        $response->assertStatus(200);
        $this->assertEquals(1, ShortUrl::where('url', $url)->count());
    }

    /** @test */
    public function shorten_endpoint_returns_different_codes_for_different_urls()
    {
        $url1 = 'http://localhost/events/apply-filter?filters%5Bname%5D=Concert';
        $url2 = 'http://localhost/events/apply-filter?filters%5Bname%5D=Festival';

        $response1 = $this->postJson(route('short-url.shorten'), ['url' => $url1]);
        $response2 = $this->postJson(route('short-url.shorten'), ['url' => $url2]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertNotEquals($response1->json('code'), $response2->json('code'));
    }

    /** @test */
    public function shorten_endpoint_rejects_invalid_url()
    {
        $this->withExceptionHandling();

        $response = $this->postJson(route('short-url.shorten'), ['url' => 'not-a-url']);

        $response->assertStatus(422);
    }

    /** @test */
    public function shorten_endpoint_rejects_missing_url()
    {
        $this->withExceptionHandling();

        $response = $this->postJson(route('short-url.shorten'), []);

        $response->assertStatus(422);
    }

    /** @test */
    public function redirect_endpoint_resolves_short_code_to_full_url()
    {
        $url = 'http://localhost/events/apply-filter?filters%5Bname%5D=Concert';

        $shortenResponse = $this->postJson(route('short-url.shorten'), ['url' => $url]);
        $code = $shortenResponse->json('code');

        $redirectResponse = $this->get(route('short-url.redirect', ['code' => $code]));

        $redirectResponse->assertRedirect($url);
    }

    /** @test */
    public function redirect_endpoint_increments_visit_count()
    {
        $shortUrl = ShortUrl::create([
            'code' => 'test1234',
            'url' => 'http://localhost/events/apply-filter?filters%5Bname%5D=Concert',
            'visit_count' => 0,
        ]);

        $this->get(route('short-url.redirect', ['code' => $shortUrl->code]));

        $this->assertEquals(1, $shortUrl->fresh()->visit_count);
    }

    /** @test */
    public function redirect_endpoint_returns_404_for_unknown_code()
    {
        $this->withExceptionHandling();

        $response = $this->get(route('short-url.redirect', ['code' => 'deadbeef']));

        $response->assertStatus(404);
    }

    /** @test */
    public function short_url_in_response_points_to_redirect_route()
    {
        $url = 'http://localhost/series/filter?filters%5Bname%5D=Jazz';

        $response = $this->postJson(route('short-url.shorten'), ['url' => $url]);

        $response->assertStatus(200);
        $this->assertStringContainsString('/s/', $response->json('short_url'));
    }
}
