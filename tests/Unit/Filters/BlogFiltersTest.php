<?php

namespace Tests\Unit\Filters;

use App\Filters\BlogFilters;
use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BlogFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new BlogFilters($request);

        return $filter->apply(Blog::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        Blog::factory()->create(['name' => 'My First Post']);
        Blog::factory()->create(['name' => 'Another Article']);

        $results = $this->applyFilters(['name' => 'First'])->get();

        $this->assertCount(1, $results);
    }

    public function test_body_filter_does_partial_match(): void
    {
        Blog::factory()->create(['body' => 'A great evening was had']);
        Blog::factory()->create(['body' => 'Lots of cats']);

        $results = $this->applyFilters(['body' => 'evening'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Blog::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
