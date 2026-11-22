<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The site's own pages for 403, 429 and 500 (#2398); 404, 419 and 503 already had one.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config(['app.debug' => false]);

        Route::middleware('web')->get('/_test/abort/{code}', fn (int $code) => abort($code));
        Route::middleware('web')->get('/_test/throw', fn () => throw new \RuntimeException('ZZ internal detail'));
    }

    public function test_forbidden_and_throttled_requests_get_the_site_page(): void
    {
        $this->get('/_test/abort/403')->assertStatus(403)->assertSee('Not allowed')->assertSee('noindex', false);
        $this->get('/_test/abort/429')->assertStatus(429)->assertSee('Too many requests')->assertSee('noindex', false);
    }

    public function test_a_server_error_page_shows_no_detail_and_needs_no_database(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->get('/_test/throw');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(500)->assertSee('Something went wrong')->assertDontSee('ZZ internal detail');
        $this->assertSame([], $queries, 'the 500 page must render with the database down');
    }
}
