<?php

namespace Tests\Unit\Filters;

use App\Filters\LinkFilters;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LinkFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new LinkFilters($request);

        return $filter->apply(Link::query());
    }

    public function test_url_filter_does_partial_match(): void
    {
        Link::factory()->create(['url' => 'https://bandcamp.com/album']);
        Link::factory()->create(['url' => 'https://other.com/page']);

        $results = $this->applyFilters(['url' => 'bandcamp'])->get();

        $this->assertCount(1, $results);
    }

    public function test_text_filter_does_partial_match(): void
    {
        Link::factory()->create(['text' => 'Bandcamp page']);
        Link::factory()->create(['text' => 'Official site']);

        $results = $this->applyFilters(['text' => 'Bandcamp'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Link::factory()->count(2)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }
}
