<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Photo;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitializeEventSharesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function publicFutureEventWithPhoto(): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(5),
        ]);

        $photo = Photo::factory()->create(['is_primary' => 1]);
        $event->photos()->attach($photo->id);

        return $event;
    }

    public function test_command_creates_a_share_for_each_eligible_event(): void
    {
        $event = $this->publicFutureEventWithPhoto();

        $this->artisan('instagram:initialize-shares')->assertExitCode(0);

        $this->assertSame(
            1,
            EventShare::where('event_id', $event->id)->where('platform', 'instagram')->count()
        );
    }

    public function test_dry_run_does_not_persist_any_shares(): void
    {
        $event = $this->publicFutureEventWithPhoto();

        $this->artisan('instagram:initialize-shares', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
    }

    public function test_events_without_primary_photo_are_skipped(): void
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addDays(5),
        ]);

        $this->artisan('instagram:initialize-shares')->assertExitCode(0);

        $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
    }

    public function test_events_already_shared_are_not_duplicated(): void
    {
        $event = $this->publicFutureEventWithPhoto();

        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => 'existing-share',
            'posted_at' => Carbon::now()->subDay(),
        ]);

        $this->artisan('instagram:initialize-shares')->assertExitCode(0);

        $this->assertSame(
            1,
            EventShare::where('event_id', $event->id)->where('platform', 'instagram')->count(),
            'No additional share should be created when one already exists.'
        );
    }

    public function test_past_events_are_ignored(): void
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->subDays(5),
        ]);
        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        $this->artisan('instagram:initialize-shares')->assertExitCode(0);

        $this->assertSame(0, EventShare::where('event_id', $event->id)->count());
    }
}
