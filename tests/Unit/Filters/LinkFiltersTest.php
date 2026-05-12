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

    private function createLink(array $attrs = []): Link
    {
        // NOTE: LinkFactory references a stale `is_active` column. Create
        // directly until the factory is fixed.
        return Link::create(array_merge([
            'url' => 'https://example.com/'.uniqid(),
            'text' => 'Example link',
            'title' => 'Example',
        ], $attrs));
    }

    public function test_url_filter_does_partial_match(): void
    {
        $this->createLink(['url' => 'https://bandcamp.com/album']);
        $this->createLink(['url' => 'https://other.com/page']);

        $results = $this->applyFilters(['url' => 'bandcamp'])->get();

        $this->assertCount(1, $results);
    }

    public function test_text_filter_does_partial_match(): void
    {
        $this->createLink(['text' => 'Bandcamp page']);
        $this->createLink(['text' => 'Official site']);

        $results = $this->applyFilters(['text' => 'Bandcamp'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        $this->createLink();
        $this->createLink();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
    }
}
