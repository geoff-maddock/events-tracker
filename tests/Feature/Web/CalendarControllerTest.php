<?php

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\Series;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/calendar')->assertOk();
    }

    public function test_index_eager_loads_series_upcoming_event_without_n_plus_one(): void
    {
        // Several active series, each with a future event, so Series::nextEvent()
        // is called once per series while building the calendar event list.
        for ($i = 1; $i <= 3; $i++) {
            $series = Series::factory()->create([
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
            Event::factory()->create([
                'series_id' => $series->id,
                'start_at' => now()->addDays($i),
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
        }

        DB::enableQueryLog();
        $this->get('/calendar')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');

        // The per-series "next event" lookup must be eager-loaded, not run once per series.
        $perSeriesNextEventQueries = $queries->filter(fn ($q) => str_contains($q, 'from `events`')
            && str_contains($q, 'series_id'));

        $this->assertLessThanOrEqual(
            1,
            $perSeriesNextEventQueries->count(),
            'Series next-event lookups should be eager-loaded, not queried per series (N+1).'
        );
    }
}
