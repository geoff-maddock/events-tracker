<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\EventType;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\Photo;
use App\Models\Series;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * series:create-events is run by hand rather than scheduled (#2112), so it
 * previews with --dry-run, reports failures in its exit code, and handles the
 * cases that made it fail or duplicate: a series whose creator was deleted,
 * and a night already entered by hand under the same slug.
 */
class CreateSeriesEventsHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function weeklySeries(array $overrides = []): Series
    {
        return Series::factory()->create(array_merge([
            'name' => 'Zz Weekly Night',
            'slug' => 'zz-weekly-night',
            'created_by' => User::factory()->create()->id,
            'event_type_id' => EventType::where('name', 'Concert')->value('id'),
            'occurrence_type_id' => OccurrenceType::where('name', 'Weekly')->value('id'),
            'occurrence_day_id' => OccurrenceDay::where('name', 'Monday')->value('id'),
            'founded_at' => Carbon::now()->subWeeks(2),
            'start_at' => Carbon::now()->addWeek()->setTime(20, 0),
            'length' => 3,
            'cancelled_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ], $overrides));
    }

    public function test_dry_run_lists_the_event_without_creating_it(): void
    {
        $series = $this->weeklySeries();

        $exit = Artisan::call('series:create-events', ['--dry-run' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString("Would create 'Zz Weekly Night'", Artisan::output());
        $this->assertSame(0, Event::where('series_id', $series->id)->count());
    }

    public function test_a_series_whose_creator_was_deleted_gets_an_event_owned_by_the_admin(): void
    {
        config(['app.superuser' => 1]);
        $series = $this->weeklySeries();
        DB::table('series')->where('id', $series->id)->update(['created_by' => null]);

        $exit = Artisan::call('series:create-events');

        $this->assertSame(0, $exit);
        $event = Event::where('series_id', $series->id)->sole();
        $this->assertSame(1, (int) $event->created_by);
    }

    public function test_a_night_already_entered_by_hand_is_not_duplicated(): void
    {
        $series = $this->weeklySeries();
        $date = $series->nextOccurrenceDate();
        $this->assertNotNull($date);
        Event::factory()->create(['slug' => 'zz-weekly-night-'.$date->format('Y-m-d'), 'series_id' => null]);

        Artisan::call('series:create-events');

        $this->assertSame(0, Event::where('series_id', $series->id)->count());
        $this->assertSame(1, Event::where('slug', 'zz-weekly-night-'.$date->format('Y-m-d'))->count());
        $this->assertStringContainsString('already exists', Artisan::output());
    }

    public function test_the_series_photos_are_copied_to_the_event(): void
    {
        $series = $this->weeklySeries();
        $photo = Photo::factory()->create();
        $series->photos()->attach($photo->id);

        Artisan::call('series:create-events');

        $event = Event::where('series_id', $series->id)->sole();
        $this->assertSame([$photo->id], $event->photos()->pluck('photos.id')->all());
    }
}
