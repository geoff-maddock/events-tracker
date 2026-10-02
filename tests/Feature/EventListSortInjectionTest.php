<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The event lists take a sort field that flows into orderBy() via
 * ListEntityResultBuilder. A syntactically valid but non-existent column (e.g. a
 * scanner's "namexh3probe9", persisted in the session or passed as ?sort=)
 * previously reached the query and produced an unknown-column SQLSTATE 500
 * (EVENTREPO-YP on /events/tag/{tag}, EVENTREPO-YQ). startEventList() now configures
 * the allowed sort fields, so an invalid value falls back to the default.
 */
class EventListSortInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_events_tag_list_falls_back_on_injected_sort_column(): void
    {
        $this->withExceptionHandling();

        $tag = Tag::factory()->create(['name' => 'Zzsorttag', 'slug' => 'zzsorttag']);

        $this->get('/events/tag/'.$tag->slug.'?sort=namexh3probe9&direction=asc')
            ->assertOk();
    }

    public function test_events_index_falls_back_on_injected_sort_column(): void
    {
        $this->withExceptionHandling();

        $this->get('/events?sort=namexh3probe9&direction=asc')
            ->assertOk();
    }
}
