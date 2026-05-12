<?php

namespace Tests\Unit\Filters;

use App\Filters\PostFilters;
use App\Models\Entity;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
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

    public function test_tag_filter_matches_by_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-post-tag']);
        $post = Post::factory()->create();
        $post->tags()->attach($tag->id);
        Post::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-post-tag'])->get();

        $this->assertCount(1, $results);
    }

    public function test_user_filter_matches_username_exactly(): void
    {
        $user = User::factory()->create(['name' => 'ZZUniquePoster']);
        Post::factory()->create(['created_by' => $user->id]);
        Post::factory()->create();

        $results = $this->applyFilters(['user' => 'ZZUniquePoster'])->get();

        $this->assertCount(1, $results);
    }

    public function test_related_filter_matches_entity_name(): void
    {
        $entity = Entity::factory()->create(['name' => 'Zz-the-band']);
        $post = Post::factory()->create();
        $post->entities()->attach($entity->id);
        Post::factory()->create();

        $results = $this->applyFilters(['related' => 'zz-the-band'])->get();

        $this->assertCount(1, $results);
    }

    public function test_series_filter_references_missing_relation_on_post(): void
    {
        // PostFilters::series calls whereHas('series'), but the Post model
        // does not define a `series` relation. This is a real bug — record it
        // here so the filter cannot regress further without a CI failure.
        Post::factory()->create();

        $this->expectException(\BadMethodCallException::class);

        $this->applyFilters(['series' => 'mondays'])->get();
    }

    public function test_empty_filters_return_all_records(): void
    {
        Post::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
