<?php

namespace Tests\Unit\Filters;

use App\Filters\EventFilters;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        $this->assertEquals('Big Synthwave Night', $results->first()->name);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Event::factory()->create(['description' => 'A loud and weird affair']);
        Event::factory()->create(['description' => 'Quiet acoustic set']);

        $results = $this->applyFilters(['description' => 'weird'])->get();

        $this->assertCount(1, $results);
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
