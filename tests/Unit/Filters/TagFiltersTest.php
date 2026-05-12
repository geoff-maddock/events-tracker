<?php

namespace Tests\Unit\Filters;

use App\Filters\TagFilters;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TagFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new TagFilters($request);

        return $filter->apply(Tag::query());
    }

    public function test_name_filter_does_partial_match(): void
    {
        Tag::factory()->create(['name' => 'Synthwave']);
        Tag::factory()->create(['name' => 'Jazz']);

        $results = $this->applyFilters(['name' => 'Synth'])->get();

        $this->assertCount(1, $results);
    }

    public function test_id_filter_matches_single_value(): void
    {
        $tag = Tag::factory()->create();
        Tag::factory()->create();

        $results = $this->applyFilters(['id' => $tag->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals($tag->id, $results->first()->id);
    }

    public function test_id_filter_matches_comma_separated_values(): void
    {
        $a = Tag::factory()->create();
        $b = Tag::factory()->create();
        Tag::factory()->create();

        $results = $this->applyFilters(['id' => $a->id.','.$b->id])->get();

        $this->assertCount(2, $results);
    }

    public function test_description_filter_does_partial_match(): void
    {
        Tag::factory()->create(['name' => 'Synthwave', 'description' => 'Music with synths']);
        Tag::factory()->create(['name' => 'Other', 'description' => 'Acoustic only']);

        $results = $this->applyFilters(['description' => 'synth'])->get();

        $this->assertCount(1, $results);
    }
}
