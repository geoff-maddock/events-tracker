<?php

namespace Tests\Unit\Models;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventType;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_future_scope_returns_only_events_starting_today_or_later(): void
    {
        Event::factory()->create(['start_at' => Carbon::yesterday()]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::future()->get();

        foreach ($results as $event) {
            $this->assertGreaterThanOrEqual(Carbon::today()->startOfDay(), $event->start_at);
        }
    }

    public function test_past_scope_returns_only_events_starting_before_today(): void
    {
        Event::factory()->create(['start_at' => Carbon::yesterday()]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::past()->get();

        foreach ($results as $event) {
            $this->assertLessThan(Carbon::today()->startOfDay(), $event->start_at);
        }
    }

    public function test_today_scope_returns_only_events_starting_today(): void
    {
        $today = Event::factory()->create(['start_at' => Carbon::today()->setTime(20, 0)]);
        Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $results = Event::today()->get();

        $this->assertTrue($results->contains('id', $today->id));
        foreach ($results as $event) {
            $this->assertEquals(Carbon::today()->toDateString(), $event->start_at->toDateString());
        }
    }

    public function test_owned_by_returns_true_for_creator(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $user->id])->save();

        $this->assertTrue($event->fresh()->ownedBy($user));
    }

    public function test_owned_by_returns_false_for_non_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create();
        $event->forceFill(['created_by' => $creator->id])->save();

        $this->assertFalse($event->fresh()->ownedBy($other));
    }

    public function test_past_or_future_returns_event_past_for_past_event(): void
    {
        $event = Event::factory()->create(['start_at' => Carbon::yesterday()]);

        $this->assertEquals('event-past', $event->past_or_future);
    }

    public function test_past_or_future_returns_event_future_for_future_event(): void
    {
        $event = Event::factory()->create(['start_at' => Carbon::tomorrow()]);

        $this->assertEquals('event-future', $event->past_or_future);
    }

    public function test_age_format_returns_all_ages_when_min_age_is_zero(): void
    {
        $event = Event::factory()->create(['min_age' => 0]);

        $this->assertEquals('All Ages', $event->age_format);
    }

    public function test_age_format_appends_plus_for_non_zero_min_age(): void
    {
        $event = Event::factory()->create(['min_age' => 21]);

        $this->assertEquals('21+', $event->age_format);
    }

    public function test_age_format_is_empty_when_min_age_is_null(): void
    {
        $event = Event::factory()->create(['min_age' => null]);

        $this->assertEquals('', $event->age_format);
    }

    public function test_end_time_returns_end_at_when_set(): void
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::tomorrow()->setTime(20, 0),
            'end_at' => Carbon::tomorrow()->setTime(23, 0),
        ]);

        $this->assertNotNull($event->end_time);
        $this->assertEquals($event->end_at->toDateTimeString(), $event->end_time->toDateTimeString());
    }

    public function test_default_end_time_falls_back_to_start_plus_default_when_unset(): void
    {
        $event = Event::factory()->create([
            'start_at' => Carbon::tomorrow()->setTime(20, 0),
            'end_at' => null,
        ]);

        $this->assertNotNull($event->default_end_time);
        $this->assertTrue($event->default_end_time->greaterThan($event->start_at));
    }

    public function test_tag_list_attribute_returns_attached_tag_ids(): void
    {
        $event = Event::factory()->create();
        $a = Tag::factory()->create();
        $b = Tag::factory()->create();
        $event->tags()->attach([$a->id, $b->id]);

        $list = $event->fresh()->tag_list;
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $list);
    }

    public function test_tag_names_attribute_returns_comma_separated_tag_names(): void
    {
        $event = Event::factory()->create();
        $a = Tag::factory()->create(['name' => 'Alpha']);
        $b = Tag::factory()->create(['name' => 'Beta']);
        $event->tags()->attach([$a->id, $b->id]);

        $names = $event->fresh()->tag_names;
        $this->assertStringContainsString('Alpha', $names);
        $this->assertStringContainsString('Beta', $names);
    }

    public function test_entity_list_attribute_returns_attached_entity_ids(): void
    {
        $event = Event::factory()->create();
        $entity = Entity::factory()->create();
        $event->entities()->attach($entity->id);

        $this->assertSame([$entity->id], $event->fresh()->entity_list);
    }

    public function test_get_by_tag_filters_events_by_tag_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-get-by-tag']);
        $hit = Event::factory()->create();
        $hit->tags()->attach($tag->id);
        Event::factory()->create();

        $results = Event::getByTag('zz-get-by-tag')->get();

        $this->assertEquals([$hit->id], $results->pluck('id')->all());
    }

    public function test_get_by_venue_filters_events_by_venue_slug(): void
    {
        $venue = Entity::factory()->venue()->create(['slug' => 'zz-by-venue']);
        $hit = Event::factory()->create(['venue_id' => $venue->id]);
        Event::factory()->create();

        $results = Event::getByVenue('zz-by-venue')->get();

        $this->assertEquals([$hit->id], $results->pluck('id')->all());
    }

    public function test_get_by_series_filters_events_by_series_slug(): void
    {
        $series = Series::factory()->create(['slug' => 'zz-by-series']);
        $hit = Event::factory()->create(['series_id' => $series->id]);
        Event::factory()->create();

        $results = Event::getBySeries('zz-by-series')->get();

        $this->assertEquals([$hit->id], $results->pluck('id')->all());
    }

    public function test_attending_count_attribute_counts_attending_responses(): void
    {
        $event = Event::factory()->create();
        $type = ResponseType::where('name', 'Attending')->first();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        foreach ([$u1, $u2] as $user) {
            DB::table('event_responses')->insert([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'response_type_id' => $type->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(2, $event->fresh()->attending_count);
    }

    public function test_get_event_response_returns_users_response_for_event(): void
    {
        $event = Event::factory()->create();
        $type = ResponseType::where('name', 'Attending')->first();
        $user = User::factory()->create();

        $id = DB::table('event_responses')->insertGetId([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => $type->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $event->getEventResponse($user);

        $this->assertNotNull($response);
        $this->assertSame($id, $response->id);
    }

    public function test_get_event_response_returns_null_when_no_response(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $this->assertNull($event->getEventResponse($user));
    }

    public function test_slug_starting_with_digit_gets_dash_prepended(): void
    {
        $event = Event::factory()->create(['slug' => '123-zz-numeric-slug']);

        $this->assertSame('-123-zz-numeric-slug', $event->slug);
    }

    public function test_fully_numeric_slug_gets_dash_prepended(): void
    {
        $event = Event::factory()->create(['slug' => '987654321']);

        $this->assertSame('-987654321', $event->slug);
    }

    public function test_slug_generated_from_digit_leading_name_gets_dash_prepended(): void
    {
        $event = Event::factory()->make(['name' => '1984 Zz Party', 'slug' => 'placeholder']);
        $event->slug = '';

        $this->assertSame('-1984-zz-party', $event->slug);
    }

    public function test_slug_not_starting_with_digit_is_unchanged(): void
    {
        $event = Event::factory()->create(['slug' => 'zz-foo-123']);

        $this->assertSame('zz-foo-123', $event->slug);
    }

    public function test_slug_already_dash_prefixed_is_unchanged(): void
    {
        $event = Event::factory()->create(['slug' => '-123-zz-already-prefixed']);

        $this->assertSame('-123-zz-already-prefixed', $event->slug);
    }
}
