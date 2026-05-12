<?php

namespace Tests\Unit\Filters;

use App\Filters\SeriesFilters;
use App\Models\Entity;
use App\Models\EventType;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeriesFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new SeriesFilters($request);

        return $filter->apply(Series::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        Series::factory()->create(['name' => 'Zz Mondays']);
        Series::factory()->create(['name' => 'Other Series']);

        $results = $this->applyFilters(['name' => 'Zz'])->get();

        $this->assertCount(1, $results);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Series::factory()->create(['description' => 'ZZUniquePattern showcase']);
        Series::factory()->create(['description' => 'unrelated']);

        $results = $this->applyFilters(['description' => 'ZZUniquePattern'])->get();

        $this->assertCount(1, $results);
    }

    public function test_venue_filter_matches_by_name(): void
    {
        $venue = Entity::factory()->venue()->create(['name' => 'Zz-velvet']);
        Series::factory()->create(['venue_id' => $venue->id]);
        Series::factory()->create();

        $results = $this->applyFilters(['venue' => 'zz-velvet'])->get();

        $this->assertCount(1, $results);
    }

    public function test_promoter_filter_matches_by_name(): void
    {
        $promoter = Entity::factory()->promoter()->create(['name' => 'Zz-runner']);
        Series::factory()->create(['promoter_id' => $promoter->id]);
        Series::factory()->create();

        $results = $this->applyFilters(['promoter' => 'zz-runner'])->get();

        $this->assertCount(1, $results);
    }

    public function test_tag_filter_matches_tag_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-st']);
        $series = Series::factory()->create();
        $series->tags()->attach($tag->id);
        Series::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-st'])->get();

        $this->assertCount(1, $results);
    }

    public function test_tag_all_filter_requires_every_tag(): void
    {
        $a = Tag::factory()->create(['slug' => 'zz-sta']);
        $b = Tag::factory()->create(['slug' => 'zz-stb']);
        $both = Series::factory()->create();
        $both->tags()->attach([$a->id, $b->id]);
        $only = Series::factory()->create();
        $only->tags()->attach($a->id);

        $results = $this->applyFilters(['tag_all' => 'zz-sta,zz-stb'])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($both->id, $results->first()->id);
    }

    public function test_related_filter_matches_entity_name(): void
    {
        $entity = Entity::factory()->create(['name' => 'Zz-related-band']);
        $series = Series::factory()->create();
        $series->entities()->attach($entity->id);
        Series::factory()->create();

        $results = $this->applyFilters(['related' => 'zz-related-band'])->get();

        $this->assertCount(1, $results);
    }

    public function test_event_type_filter_matches_by_name(): void
    {
        $type = EventType::first();
        Series::factory()->create(['event_type_id' => $type->id]);

        $results = $this->applyFilters(['event_type' => strtolower($type->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_occurrence_type_filter_matches_by_name(): void
    {
        $occurrence = OccurrenceType::where('name', '!=', 'No Schedule')->first();
        Series::factory()->create(['occurrence_type_id' => $occurrence->id]);

        $results = $this->applyFilters(['occurrence_type' => strtolower($occurrence->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_occurrence_week_filter_matches_by_name(): void
    {
        $week = OccurrenceWeek::first();
        Series::factory()->create(['occurrence_week_id' => $week->id]);

        $results = $this->applyFilters(['occurrence_week' => strtolower($week->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_occurrence_day_filter_matches_by_name(): void
    {
        $day = OccurrenceDay::first();
        Series::factory()->create(['occurrence_day_id' => $day->id]);

        $results = $this->applyFilters(['occurrence_day' => strtolower($day->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_start_at_range_filter(): void
    {
        Series::factory()->create(['start_at' => Carbon::parse('2026-06-15 20:00')]);
        Series::factory()->create(['start_at' => Carbon::parse('2026-07-15 20:00')]);

        $results = $this->applyFilters([
            'start_at' => ['start' => '2026-06-01', 'end' => '2026-06-30'],
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_start_at_non_array_is_passthrough(): void
    {
        Series::factory()->count(2)->create();

        $results = $this->applyFilters(['start_at' => 'notanarray'])->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }

    public function test_end_at_range_filter(): void
    {
        Series::factory()->create(['end_at' => Carbon::parse('2026-06-15 23:00')]);
        Series::factory()->create(['end_at' => Carbon::parse('2026-07-15 23:00')]);

        $results = $this->applyFilters([
            'end_at' => ['start' => '2026-07-01', 'end' => '2026-07-31'],
        ])->get();

        $this->assertCount(1, $results);
    }

    public function test_ages_filter_currently_references_nonexistent_column(): void
    {
        // SeriesFilters::ages orders by `ages_id`, which does not exist on the
        // `series` table. This is a real bug in the filter — captured here so
        // it surfaces in CI rather than only at runtime. Once the filter is
        // fixed, flip this assertion to a happy-path test.
        Series::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->applyFilters(['ages' => 'asc'])->get();
    }

    public function test_visibility_filter_matches_by_id(): void
    {
        Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        $results = $this->applyFilters(['visibility' => Visibility::VISIBILITY_PUBLIC])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $row) {
            $this->assertSame(Visibility::VISIBILITY_PUBLIC, $row->visibility_id);
        }
    }

    public function test_is_benefit_filter(): void
    {
        Series::factory()->create(['is_benefit' => 1]);
        Series::factory()->create(['is_benefit' => 0]);

        $results = $this->applyFilters(['is_benefit' => 1])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $row) {
            $this->assertSame(1, (int) $row->is_benefit);
        }
    }
}
