<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Follow;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The calendar sub-pages (tag, related, type, free, min-age, attending,
 * by-date) render with a ranged feed URL scoped to the page, instead of
 * inlining every matching event ever (#2252).
 */
class CalendarSubPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));

        $creator = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->viewer = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $tag = Tag::factory()->create(['name' => 'Zz Sub Tag', 'slug' => 'zz-sub-tag']);
        $artist = Entity::factory()->create(['name' => 'Zz Sub Artist', 'slug' => 'zz-sub-artist']);
        $concert = EventType::where('name', 'Concert')->value('id');
        $club = EventType::where('name', 'Club Night')->value('id');
        $attending = ResponseType::where('name', 'Attending')->value('id');

        $this->actingAs($creator);
        $make = function (string $name, array $attributes) {
            return Event::factory()->create($attributes + [
                'name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'start_at' => Carbon::now()->addDays(3), 'door_price' => 10, 'min_age' => 21, 'event_type_id' => EventType::where('name', 'Club Night')->value('id'),
            ]);
        };
        $make('Zz Tagged', [])->tags()->attach($tag->id);
        $make('Zz With Artist', [])->entities()->attach($artist->id);
        $make('Zz Concert', ['event_type_id' => $concert]);
        $make('Zz Free', ['door_price' => 0]);
        $make('Zz All Ages', ['min_age' => 0]);
        $make('Zz Attended', [])->eventResponses()->create(['user_id' => $this->viewer->id, 'response_type_id' => $attending]);
        $make('Zz Guarded Free', ['door_price' => 0, 'visibility_id' => Visibility::VISIBILITY_GUARDED]);
        $make('Zz Private Free', ['door_price' => 0, 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);
        $make('Zz Out Of Range Free', ['door_price' => 0, 'start_at' => Carbon::now()->addMonths(3)]);

        // a free, all-ages, concert series with the tag and artist, followed by the viewer
        $series = Series::factory()->create([
            'name' => 'Zz Sub Series', 'slug' => 'zz-sub-series', 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => 2, 'occurrence_week_id' => 1, 'occurrence_day_id' => 4, 'cancelled_at' => null,
            'founded_at' => '2024-01-03 21:00:00', 'start_at' => '2024-01-03 21:00:00', 'length' => 3,
            'door_price' => 0, 'min_age' => 0, 'event_type_id' => $concert,
        ]);
        $series->tags()->attach($tag->id);
        $series->entities()->attach($artist->id);
        Follow::create(['user_id' => $this->viewer->id, 'object_type' => 'series', 'object_id' => $series->id]);
        // and one that matches none of the pages
        Series::factory()->create([
            'name' => 'Zz Other Series', 'slug' => 'zz-other-series', 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => 2, 'occurrence_week_id' => 1, 'occurrence_day_id' => 4, 'cancelled_at' => null,
            'founded_at' => '2024-01-03 21:00:00', 'start_at' => '2024-01-03 21:00:00', 'length' => 3,
            'door_price' => 15, 'min_age' => 21, 'event_type_id' => $club,
        ]);
        auth()->logout();
    }

    /**
     * Load the page, then its feed for October 1 to November 15.
     *
     * @return array<int, string> the titles in the feed
     */
    private function feedTitles(string $page): array
    {
        $view = $this->get($page)->assertOk()->assertViewMissing('eventList');
        $feedUrl = $view->viewData('calendarFeedUrl');
        $this->assertStringStartsWith(url('/calendar'), $feedUrl);

        $separator = str_contains($feedUrl, '?') ? '&' : '?';
        $json = $this->getJson($feedUrl.$separator.'start=2026-10-01T00:00:00&end=2026-11-15T00:00:00')->assertOk()->json();

        return collect($json)->pluck('title')->sort()->values()->all();
    }

    public function test_the_tag_calendar_shows_the_tags_events_and_series(): void
    {
        $this->assertSame(['Zz Sub Series', 'Zz Tagged'], $this->feedTitles('/calendar/tag/zz-sub-tag'));
    }

    public function test_the_related_calendar_shows_the_entitys_events_and_series(): void
    {
        $this->assertSame(['Zz Sub Series', 'Zz With Artist'], $this->feedTitles('/calendar/related-to/zz-sub-artist'));
    }

    public function test_the_type_calendar_shows_that_types_events_and_series(): void
    {
        $this->assertSame(['Zz Concert', 'Zz Sub Series'], $this->feedTitles('/calendar/type/concert'));
    }

    public function test_the_free_calendar_shows_free_events_the_viewer_may_see_in_range(): void
    {
        // guests see public events only
        $this->assertSame(['Zz Free', 'Zz Sub Series'], $this->feedTitles('/calendar/free'));

        // signed in, guarded events too (as on /calendar), but never someone else's private one
        $this->actingAs($this->viewer);
        $this->assertSame(['Zz Free', 'Zz Guarded Free', 'Zz Sub Series'], $this->feedTitles('/calendar/free'));
    }

    public function test_the_min_age_calendar_shows_events_open_to_that_age(): void
    {
        // everything else here is 21+
        $this->assertSame(['Zz All Ages', 'Zz Sub Series'], $this->feedTitles('/calendar/min-age/18'));
    }

    public function test_the_attending_calendar_shows_attended_events_and_followed_series(): void
    {
        $this->actingAs($this->viewer);

        $this->assertSame(['Zz Attended', 'Zz Sub Series'], $this->feedTitles('/calendar/attending'));
    }

    public function test_the_by_date_calendar_opens_on_that_month(): void
    {
        $this->get('/calendar/by-date/2026/11')->assertOk()
            ->assertViewHas('initialDate', '2026-11-01')
            ->assertViewHas('calendarFeedUrl', url('/calendar'));
    }
}
