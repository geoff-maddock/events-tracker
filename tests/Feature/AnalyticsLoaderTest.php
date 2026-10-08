<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * gtag.js loads after the page settles, not during page load (#2302).
 */
class AnalyticsLoaderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_gtag_is_deferred_instead_of_loaded_with_the_page(): void
    {
        config(['app.analytics' => 'G-TEST123']);

        $response = $this->get('/events');

        $response->assertOk();
        $response->assertDontSee('<script async src="https://www.googletagmanager.com/gtag/js', false);
        $response->assertSee("gtag('config', 'G-TEST123');", false);
        $response->assertSee("s.src = 'https://www.googletagmanager.com/gtag/js?id=G-TEST123';", false);
        $response->assertSee('setTimeout(load, 5000)', false);
        // scrolling inside <main> must count as interaction too
        $response->assertSee("var events = ['pointerdown', 'keydown', 'wheel', 'scroll', 'touchstart'];", false);
        $response->assertSee('document.addEventListener(e, load, options)', false);
    }

    public function test_no_analytics_without_a_measurement_id(): void
    {
        config(['app.analytics' => '', 'app.google_tags' => 'GTM-TEST']);

        $response = $this->get('/events');

        $response->assertOk();
        $response->assertDontSee('gtag/js', false);
        $response->assertDontSee('dataLayer', false);
    }
}
