<?php

namespace Tests\Unit\Filters;

use App\Filters\ThreadFilters;
use App\Models\Entity;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
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

    public function test_thread_category_filter_matches_by_name(): void
    {
        $category = ThreadCategory::first();
        $this->assertNotNull($category);
        Thread::factory()->create(['thread_category_id' => $category->id]);

        $results = $this->applyFilters(['thread_category' => strtolower($category->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_category_alias_matches_thread_category(): void
    {
        $category = ThreadCategory::first();
        Thread::factory()->create(['thread_category_id' => $category->id]);

        $results = $this->applyFilters(['category' => strtolower($category->name)])->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_tag_filter_matches_by_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-thread-tag']);
        $thread = Thread::factory()->create();
        $thread->tags()->attach($tag->id);
        Thread::factory()->create();

        $results = $this->applyFilters(['tag' => 'zz-thread-tag'])->get();

        $this->assertCount(1, $results);
    }

    public function test_series_filter_matches_by_slug(): void
    {
        $series = Series::factory()->create(['slug' => 'zz-thread-series']);
        $thread = Thread::factory()->create();
        $thread->series()->attach($series->id);
        Thread::factory()->create();

        $results = $this->applyFilters(['series' => 'zz-thread-series'])->get();

        $this->assertCount(1, $results);
    }

    public function test_user_filter_matches_username(): void
    {
        // Thread::boot() overrides created_by from Auth::user() in `creating`,
        // so act as the user before factory create() — passing created_by in
        // attributes gets clobbered.
        $user = User::factory()->create(['name' => 'ZZThreadAuthor']);
        $this->actingAs($user);
        Thread::factory()->create();

        // A separate thread by a different user.
        $this->actingAs(User::factory()->create());
        Thread::factory()->create();

        $results = $this->applyFilters(['user' => 'ZZThreadAuthor'])->get();

        $this->assertCount(1, $results);
    }

    public function test_related_filter_matches_entity_name(): void
    {
        $entity = Entity::factory()->create(['name' => 'Zz-thread-band']);
        $thread = Thread::factory()->create();
        $thread->entities()->attach($entity->id);
        Thread::factory()->create();

        $results = $this->applyFilters(['related' => 'zz-thread-band'])->get();

        $this->assertCount(1, $results);
    }

    public function test_empty_filters_return_all_records(): void
    {
        Thread::factory()->count(3)->create();

        $results = $this->applyFilters([])->get();

        $this->assertGreaterThanOrEqual(3, $results->count());
    }
}
