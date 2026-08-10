<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions for a batch of routes a crawl surfaced as broken:
 * header-dump bodies, a 500, a hanging mailer, a duplicate <title>,
 * and a self-referential canonical on error pages.
 */
class BrokenListingRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * The gated listings declared `: string`, so PHP stringified the
     * RedirectResponse and served the raw HTTP message as a 200 body.
     */
    public function test_gated_listings_redirect_guests_instead_of_dumping_the_response(): void
    {
        foreach (['/forums', '/posts'] as $path) {
            $response = $this->get($path);

            $response->assertRedirect();
            $this->assertStringNotContainsString(
                'HTTP/1.0 302',
                $response->getContent(),
                $path.' served a stringified RedirectResponse as its body'
            );
        }
    }

    public function test_legacy_all_aliases_permanently_redirect_to_their_index(): void
    {
        $this->get('/forums/all')->assertRedirect('/forums')->assertStatus(301);
        $this->get('/posts/all')->assertRedirect('/posts')->assertStatus(301);
        // this one 500'd: BlogsController never had the indexAll it routed to
        $this->get('/blogs/all')->assertRedirect('/blogs')->assertStatus(301);
    }

    /**
     * /events/daily was an unauthenticated GET that mailed every user in the
     * database. The scheduled `notify` command owns that job now.
     */
    public function test_events_daily_mailer_route_is_gone(): void
    {
        $this->get('/events/daily')->assertNotFound();
    }

    public function test_events_past_has_its_own_title(): void
    {
        $index = $this->get('/events');
        $past = $this->get('/events/past');

        $index->assertOk();
        $past->assertOk();

        $past->assertSee('Past Events', false);
        $past->assertDontSee('Pittsburgh Events Calendar', false);
        $this->assertNotSame($this->title($index->getContent()), $this->title($past->getContent()));
    }

    public function test_error_pages_do_not_self_canonicalize_and_are_noindexed(): void
    {
        $response = $this->get('/a-url-that-does-not-exist');

        $response->assertNotFound();
        $response->assertDontSee('rel="canonical"', false);
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    protected function title(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/s', $html, $m);

        return trim($m[1] ?? '');
    }
}
