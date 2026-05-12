<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\TrackPreviousUrl;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrackPreviousUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([StartSession::class, TrackPreviousUrl::class])->group(function () {
            Route::get('/_test/track/show', fn () => 'show')->name('test.show');
            Route::get('/_test/track/other', fn () => 'other')->name('test.other');
            Route::get('/_test/track/create', fn () => 'create')->name('test.create');
            Route::get('/_test/track/{id}/edit', fn () => 'edit')->name('test.edit');
        });
    }

    public function test_first_visit_records_tracked_url_but_no_previous(): void
    {
        $this->get('/_test/track/show')->assertOk();

        $this->assertStringContainsString('/_test/track/show', session('tracked_url'));
        $this->assertNull(session('previous_url'));
    }

    public function test_second_distinct_visit_promotes_tracked_to_previous(): void
    {
        $this->get('/_test/track/show')->assertOk();
        $first = session('tracked_url');

        $this->get('/_test/track/other')->assertOk();

        $this->assertSame($first, session('previous_url'));
        $this->assertStringContainsString('/_test/track/other', session('tracked_url'));
    }

    public function test_revisiting_same_url_does_not_overwrite_previous(): void
    {
        $this->get('/_test/track/show')->assertOk();
        $this->get('/_test/track/other')->assertOk();
        $previousAfterTwo = session('previous_url');

        // Reload the same page
        $this->get('/_test/track/other')->assertOk();

        $this->assertSame($previousAfterTwo, session('previous_url'));
    }

    public function test_create_route_is_skipped(): void
    {
        $this->get('/_test/track/create')->assertOk();

        $this->assertNull(session('tracked_url'));
    }

    public function test_edit_route_is_skipped(): void
    {
        $this->get('/_test/track/42/edit')->assertOk();

        $this->assertNull(session('tracked_url'));
    }
}
