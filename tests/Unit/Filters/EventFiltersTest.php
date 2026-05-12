<?php

namespace Tests\Unit\Filters;

use App\Filters\EventFilters;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new EventFilters($request);

        return $filter->apply(Event::query());
    }

    public function test_id_filter_matches_exact_id(): void
    {
        $event = Event::factory()->create();
        Event::factory()->create();

        $results = $this->applyFilters(['id' => $event->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($event->id, $results->first()->id);
    }

    public function test_name_filter_does_partial_match(): void
    {
        Event::factory()->create(['name' => 'Big Synthwave Night']);
        Event::factory()->create(['name' => 'Jazz Brunch']);

        $results = $this->applyFilters(['name' => 'Synthwave'])->get();

        $this->assertCount(1, $results);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Event::factory()->create(['description' => 'A loud and weird affair']);
        Event::factory()->create(['description' => 'Quiet acoustic set']);

        $results = $this->applyFilters(['description' => 'weird'])->get();

        $this->assertCount(1, $results);
    }

    public function test_venue_filter_partial_matches_slug(): void
    {
        $venue = Entity::factory()->venue()->create(['slug' => 'zz-velvet-room']);
        Event::factory()->create(['venue_id' => $venue->id]);
        Event::factory()->create();

        $results = $this->applyFilters(['venue' => 'velvet'])->get();

        $this->assertCount(1, $results);
    }

    public function test_promoter_filter_partial_matches_slug(): void
    {
        $promoter = Entity::factory()->promoter()->create(['slug' => 'zz-night-runner']);
        Event::factory()->create(['promoter_id' => $promoter->id]);
        Event::factory()->create();

        $results = $this->applyFilters(['promoter' => 'night-runner'])->get();

        $this->assertCount(1, $results);
    }

    public function test_tag_filter_matches_single_tag_by_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-doom']);
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);
        Event::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-doom'])->get();

        $this->assertCount(1, $results);
    }

    public function test_tag_filter_supports_comma_separated_list(): void
    {
        $a = Tag::factory()->create(['slug' => 'zz-a']);
        $b = Tag::factory()->create(['slug' => 'zz-b']);
        $eA = Event::factory()->create();
        $eA->tags()->attach($a->id);
        $eB = Event::factory()->create();
        $eB->tags()->attach($b->id);
        Event::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-a,zz-b'])->get();

        $this->assertCount(2, $results);
    }

    public function test_tag_all_filter_requires_every_tag(): void
    {
        $a = Tag::factory()->create(['slug' => 'zz-aa']);
        $b = Tag::factory()->create(['slug' => 'zz-bb']);
        $both = Event::factory()->create();
        $both->tags()->attach([$a->id, $b->id]);
        $onlyA = Event::factory()->create();
        $onlyA->tags()->attach($a->id);

        $results = $this->applyFilters(['tag_all' => 'zz-aa,zz-bb'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($both->id, $results->first()->id);
    }

    public function test_related_filter_matches_entity_slug(): void
    {
        $entity = Entity::factory()->create(['slug' => 'zz-the-band']);
        $event = Event::factory()->create();
        $event->entities()->attach($entity->id);
        Event::factory()->create();

        $results = $this->applyFilters(['related' => 'zz-the-band'])->get();

        $this->assertCount(1, $results);
    }

    public function test_series_filter_matches_series_name_case_insensitively(): void
    {
        $series = Series::factory()->create(['name' => 'Zz-mondays']);
        Event::factory()->create(['series_id' => $series->id]);
        Event::factory()->create();

        $results = $this->applyFilters(['series' => 'zz-mondays'])->get();

        $this->assertCount(1, $results);
    }

    public function test_event_type_filter_matches_by_slug(): void
    {
        $type = EventType::first();
        Event::factory()->create(['event_type_id' => $type->id]);
        Event::factory()->create(['event_type_id' => EventType::where('id', '!=', $type->id)->first()?->id ?? $type->id]);

        $results = $this->applyFilters(['event_type' => $type->slug])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $row) {
            $this->assertEquals($type->id, $row->event_type_id);
        }
    }

    public function test_start_at_range_filter(): void
    {
        Event::factory()->create(['start_at' => Carbon::parse('2026-06-15 20:00')]);
        Event::factory()->create(['start_at' => Carbon::parse('2026-07-15 20:00')]);
        Event::factory()->create(['start_at' => Carbon::parse('2026-08-15 20:00')]);

        $results = $this->applyFilters([
            'start_at' => ['start' => '2026-06-20', 'end' => '2026-07-31'],
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_start_at_non_array_value_is_a_passthrough(): void
    {
        Event::factory()->count(2)->create();

        $results = $this->applyFilters(['start_at' => 'notanarray'])->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_end_at_range_filter(): void
    {
        Event::factory()->create(['end_at' => Carbon::parse('2026-06-15 23:00')]);
        Event::factory()->create(['end_at' => Carbon::parse('2026-07-15 23:00')]);

        $results = $this->applyFilters([
            'end_at' => ['start' => '2026-07-01', 'end' => '2026-07-31'],
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_ages_filter_matches_exact_min_age(): void
    {
        Event::factory()->create(['min_age' => 18]);
        Event::factory()->create(['min_age' => 21]);

        $results = $this->applyFilters(['ages' => 18])->get();

        $this->assertCount(1, $results);
    }

    public function test_is_benefit_filter(): void
    {
        Event::factory()->create(['is_benefit' => 1]);
        Event::factory()->create(['is_benefit' => 0]);

        $results = $this->applyFilters(['is_benefit' => 1])->get();

        $this->assertCount(1, $results);
    }

    public function test_door_price_range_filter(): void
    {
        Event::factory()->create(['door_price' => 5]);
        Event::factory()->create(['door_price' => 20]);
        Event::factory()->create(['door_price' => 50]);

        $results = $this->applyFilters(['door_price' => ['min' => 10, 'max' => 30]])->get();

        $this->assertCount(1, $results);
    }

    public function test_min_age_filter_returns_events_at_or_below_threshold(): void
    {
        Event::factory()->create(['min_age' => 18]);
        Event::factory()->create(['min_age' => 21]);
        Event::factory()->create(['min_age' => 13]);

        $results = $this->applyFilters(['min_age' => 18])->get();

        $this->assertCount(2, $results);
    }

    public function test_my_events_filter_returns_only_attending_events_for_auth_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user);

        $attending = Event::factory()->create();
        Event::factory()->create();

        $responseTypeId = DB::table('response_types')->where('name', 'Attending')->value('id');
        DB::table('event_responses')->insert([
            'event_id' => $attending->id,
            'user_id' => $user->id,
            'response_type_id' => $responseTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = $this->applyFilters(['my_events' => '1'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($attending->id, $results->first()->id);
    }

    public function test_display_type_attending_for_auth_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user);

        $attending = Event::factory()->create();
        Event::factory()->create();

        $responseTypeId = DB::table('response_types')->where('name', 'Attending')->value('id');
        DB::table('event_responses')->insert([
            'event_id' => $attending->id,
            'user_id' => $user->id,
            'response_type_id' => $responseTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = $this->applyFilters(['display_type' => 'attending'])->get();

        $this->assertCount(1, $results);
    }

    public function test_display_type_created_returns_events_user_owns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $owned = Event::factory()->create(['created_by' => $user->id]);
        Event::factory()->create();

        $results = $this->applyFilters(['display_type' => 'created'])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('id')->contains($owned->id));
    }

    public function test_display_type_all_is_passthrough(): void
    {
        Event::factory()->count(3)->create();

        $results = $this->applyFilters(['display_type' => 'all'])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }

    public function test_missing_filter_value_does_not_constrain_query(): void
    {
        Event::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertCount(3, $results);
    }

    public function test_unknown_filter_keys_are_ignored(): void
    {
        Event::factory()->count(2)->create();

        $results = $this->applyFilters(['totally_made_up_filter' => 'value'])->get();

        $this->assertCount(2, $results);
    }
}
