<?php

namespace Tests\Unit\Filters;

use App\Filters\GroupFilters;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GroupFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new GroupFilters($request);

        return $filter->apply(Group::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        $unique = 'zzfilter'.uniqid();
        Group::factory()->create(['name' => $unique.'-alpha']);
        Group::factory()->create(['name' => 'unrelated-'.uniqid()]);

        $results = $this->applyFilters(['name' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_label_filter_does_partial_match(): void
    {
        $unique = 'ZZLabel'.uniqid();
        Group::factory()->create(['label' => $unique]);
        Group::factory()->create(['label' => 'unrelated-'.uniqid()]);

        $results = $this->applyFilters(['label' => $unique])->get();

        $this->assertCount(1, $results);
    }

    public function test_level_filter_matches_exact_value(): void
    {
        $level = random_int(900, 9999);
        Group::factory()->create(['level' => $level]);

        $results = $this->applyFilters(['level' => $level])->get();

        $this->assertCount(1, $results);
    }
}
