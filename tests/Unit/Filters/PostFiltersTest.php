<?php

namespace Tests\Unit\Filters;

use App\Filters\PostFilters;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PostFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new PostFilters($request);

        return $filter->apply(Post::query());
    }

    public function test_body_filter_does_partial_match(): void
    {
        Post::factory()->create(['body' => 'A reply about synthesizers']);
        Post::factory()->create(['body' => 'Something else entirely']);

        $results = $this->applyFilters(['body' => 'synthesizers'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Post::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
