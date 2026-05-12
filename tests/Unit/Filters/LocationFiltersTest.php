<?php

namespace Tests\Unit\Filters;

use App\Filters\LocationFilters;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LocationFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new LocationFilters($request);

        return $filter->apply(Location::query());
    }

    private function makeLocation(array $attrs = []): Location
    {
        return Location::factory()->create($attrs);
    }

    public function test_name_filter_does_partial_match(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['name' => $unique.' Hall']);
        $this->makeLocation(['name' => 'Other']);

        $results = $this->applyFilters(['name' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_neighborhood_filter_does_partial_match(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['neighborhood' => $unique.'ville']);
        $this->makeLocation(['neighborhood' => 'Elsewhere']);

        $results = $this->applyFilters(['neighborhood' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_slug_filter_matches_exact(): void
    {
        $unique = 'zz-'.uniqid();
        $this->makeLocation(['slug' => $unique]);
        $this->makeLocation(['slug' => 'unrelated-'.uniqid()]);

        $results = $this->applyFilters(['slug' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_city_filter_does_partial_match(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['city' => $unique.'burgh']);
        $this->makeLocation(['city' => 'Other']);

        $results = $this->applyFilters(['city' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_state_filter_matches_exact(): void
    {
        $this->makeLocation(['state' => 'ZP']);
        $this->makeLocation(['state' => 'ZQ']);

        $results = $this->applyFilters(['state' => 'ZP'])->get();

        $this->assertCount(1, $results);
    }

    public function test_search_filter_matches_any_of_several_fields(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['name' => $unique.' Hall']);
        $this->makeLocation(['name' => 'Other', 'city' => 'Elsewhere']);

        $byName = $this->applyFilters(['search' => $unique])->get();

        $this->assertCount(1, $byName);
    }
}
