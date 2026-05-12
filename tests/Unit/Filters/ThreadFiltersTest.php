<?php

namespace Tests\Unit\Filters;

use App\Filters\ThreadFilters;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThreadFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new ThreadFilters($request);

        return $filter->apply(Thread::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        Thread::factory()->create(['name' => 'Synth meetup']);
        Thread::factory()->create(['name' => 'Random discussion']);

        $results = $this->applyFilters(['name' => 'Synth'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Thread::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
