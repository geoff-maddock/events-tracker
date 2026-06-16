<?php

namespace Tests\Feature;

use App\Filters\PhotoFilters;
use App\Http\Requests\ListQueryParameters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * The list pages take a sort field that flows into orderBy() via
 * ListEntityResultBuilder. A malformed value (e.g. a stray quote from a fuzz
 * probe or a broken link) previously reached the query and produced a SQLSTATE
 * 500; it must now fall back to the default sort column.
 */
class ListSortHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildList(?string $sortField)
    {
        $params = Mockery::mock(ListQueryParameters::class);
        $params->shouldReceive('getFilters')->andReturn([]);
        $params->shouldReceive('getIsEmptyFilter')->andReturn(false);
        $params->shouldReceive('getSortFieldName')->andReturn($sortField);
        $params->shouldReceive('getSortDirection')->andReturn('desc');
        $params->shouldReceive('getLimit')->andReturn(25);
        $params->shouldReceive('getPage')->andReturn(1);

        $builder = new ListEntityResultBuilder($params);
        $builder->setQueryBuilder(Photo::query())
            ->setFilter(app(PhotoFilters::class))
            ->setDefaultSort(['photos.created_at' => 'desc']);
        $builder->setMultiSort([]);
        $builder->setDefaultLimit(25);

        return $builder->listResultSetFactory();
    }

    public function test_malformed_sort_field_falls_back_and_query_runs(): void
    {
        $result = $this->buildList("created_at'");

        // Fell back to the safe default rather than the malformed input...
        $this->assertSame('photos.created_at', $result->getSort());
        // ...and the built query executes without a bad-column SQLSTATE error.
        $result->getList()->get();
        $this->assertTrue(true);
    }

    public function test_valid_sort_field_is_preserved(): void
    {
        $result = $this->buildList('photos.name');

        $this->assertSame('photos.name', $result->getSort());
        $result->getList()->get();
        $this->assertTrue(true);
    }
}
