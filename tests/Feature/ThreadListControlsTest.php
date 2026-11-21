<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The thread lists on the shared list-page scaffold (#2406): every list loads
 * the reply count and can sort by author, and the filter and reset controls
 * keep their session state.
 */
class ThreadListControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const FILTERS = 'internal_thread_index/index/filters';

    private const SORT = 'internal_thread_index/index/sort';

    private Thread $alpha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $author = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Zzthreadlisttag', 'slug' => 'zzthreadlisttag']);

        foreach (['Alpha Listed Thread', 'Beta Listed Thread'] as $name) {
            $thread = Thread::factory()->create(['name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $author->id]);
            $thread->tags()->attach($tag->id);
        }
        $this->alpha = Thread::where('name', 'Alpha Listed Thread')->sole();
        Post::factory()->count(2)->create(['thread_id' => $this->alpha->id, 'created_by' => $author->id]);
    }

    /**
     * The reply count the list view was given for the Alpha thread.
     */
    private function replyCountOn(string $url): ?int
    {
        $count = null;
        View::composer('threads.index-tw', function ($view) use (&$count) {
            $count = $view->getData()['threads']->firstWhere('id', $this->alpha->id)?->posts_count;
        });

        $this->get($url)->assertOk();

        return $count;
    }

    public function test_the_main_list_loads_the_reply_count(): void
    {
        // index() alone used to leave it out, so /threads showed no counts
        $this->assertSame(2, $this->replyCountOn('/threads'));
    }

    public function test_the_tag_list_loads_the_reply_count(): void
    {
        $this->assertSame(2, $this->replyCountOn('/threads/tag/zzthreadlisttag'));
    }

    public function test_the_tag_list_can_be_sorted_by_author(): void
    {
        // the tag, category, series and related lists did not join users: an unknown-column error
        $this->get('/threads/tag/zzthreadlisttag?sort=users.name&direction=asc')
            ->assertOk()
            ->assertSee('Alpha Listed Thread')
            ->assertSee('Beta Listed Thread');
    }

    private function filterByName(): void
    {
        $this->post('/threads/filter', [
            'filters' => ['name' => 'Alpha Listed'],
            'sort' => 'threads.name',
            'direction' => 'asc',
        ])->assertOk()->assertSee('Alpha Listed Thread')->assertDontSee('Beta Listed Thread');
    }

    public function test_reset_clears_the_filter_and_the_sort(): void
    {
        $this->filterByName();
        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));

        $this->get('/threads/reset')->assertRedirect(route('threads.index'));

        $this->assertEmpty(session(self::FILTERS));
        $this->assertNull(session(self::SORT));
    }

    public function test_rpp_reset_clears_the_sort_and_keeps_the_filter(): void
    {
        $this->filterByName();

        $this->get('/threads/rpp-reset')->assertRedirect(route('threads.index'));

        $this->assertSame(['name' => 'Alpha Listed'], session(self::FILTERS));
        $this->assertNull(session(self::SORT));
    }
}
