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

    public function test_attn_filter_partial_matches(): void
    {
        $unique = 'ZZAttn'.uniqid();
        $this->makeLocation(['attn' => $unique.' Manager']);
        $this->makeLocation(['attn' => 'Other Person']);

        $results = $this->applyFilters(['attn' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_addressOne_filter_partial_matches(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['address_one' => "1 {$unique} Lane"]);
        $this->makeLocation(['address_one' => '999 Elsewhere Ave']);

        $results = $this->applyFilters(['addressOne' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_addressTwo_filter_partial_matches(): void
    {
        $unique = 'ZZ'.uniqid();
        $this->makeLocation(['address_two' => "Suite {$unique}"]);
        $this->makeLocation(['address_two' => 'Suite 999']);

        $results = $this->applyFilters(['addressTwo' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_postcode_filter_matches_exact(): void
    {
        $this->makeLocation(['postcode' => 'ZZ-101']);
        $this->makeLocation(['postcode' => 'ZZ-202']);

        $results = $this->applyFilters(['postcode' => 'ZZ-101'])->get();

        $this->assertCount(1, $results);
    }

    public function test_country_filter_matches_exact(): void
    {
        $this->makeLocation(['country' => 'ZZ-Country-A']);
        $this->makeLocation(['country' => 'ZZ-Country-B']);

        $results = $this->applyFilters(['country' => 'ZZ-Country-A'])->get();

        $this->assertCount(1, $results);
    }

    public function test_capacity_filter_matches_exact(): void
    {
        $this->makeLocation(['capacity' => 999]);
        $this->makeLocation(['capacity' => 50]);

        $results = $this->applyFilters(['capacity' => 999])->get();

        $this->assertCount(1, $results);
    }

    public function test_mapUrl_filter_partial_matches(): void
    {
        $unique = 'zzmap'.uniqid();
        $this->makeLocation(['map_url' => "https://maps.example.com/{$unique}"]);
        $this->makeLocation(['map_url' => 'https://maps.example.com/other']);

        $results = $this->applyFilters(['mapUrl' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_locationTypeId_filter_matches_exact(): void
    {
        $location = $this->makeLocation();

        $results = $this->applyFilters(['locationTypeId' => $location->location_type_id])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $row) {
            $this->assertEquals($location->location_type_id, $row->location_type_id);
        }
    }

    public function test_entityId_filter_matches_exact(): void
    {
        $location = $this->makeLocation();

        $results = $this->applyFilters(['entityId' => $location->entity_id])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_visibilityId_filter_matches_exact(): void
    {
        $location = $this->makeLocation();

        $results = $this->applyFilters(['visibilityId' => $location->visibility_id])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }
}
