<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSeriesEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_command_runs_with_no_series(): void
    {
        $this->artisan('series:create-events')->assertExitCode(0);
    }

    public function test_command_skips_series_that_already_has_a_future_event(): void
    {
        $occurrence = OccurrenceType::where('name', '!=', 'No Schedule')->first();

        $series = Series::factory()->create([
            'occurrence_type_id' => $occurrence->id,
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Pre-existing future event for this series
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => Carbon::now()->addDays(7),
        ]);

        $eventsBefore = Event::where('series_id', $series->id)->count();

        $this->artisan('series:create-events')->assertExitCode(0);

        $this->assertSame(
            $eventsBefore,
            Event::where('series_id', $series->id)->count(),
            'A new event should not be created when one already exists.'
        );
    }

    public function test_command_ignores_series_with_no_schedule_occurrence_type(): void
    {
        $noSchedule = OccurrenceType::where('name', 'No Schedule')->first();
        $this->assertNotNull($noSchedule, 'Expected "No Schedule" occurrence type to be seeded.');

        $series = Series::factory()->create([
            'occurrence_type_id' => $noSchedule->id,
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->artisan('series:create-events')->assertExitCode(0);

        $this->assertSame(
            0,
            Event::where('series_id', $series->id)->count(),
            'No Schedule series should be excluded by the query.'
        );
    }

    public function test_command_ignores_cancelled_series(): void
    {
        $occurrence = OccurrenceType::where('name', '!=', 'No Schedule')->first();

        $series = Series::factory()->create([
            'occurrence_type_id' => $occurrence->id,
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_CANCELLED,
        ]);

        $this->artisan('series:create-events')->assertExitCode(0);

        $this->assertSame(0, Event::where('series_id', $series->id)->count());
    }
}
