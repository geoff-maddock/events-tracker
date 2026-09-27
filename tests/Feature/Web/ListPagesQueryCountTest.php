<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Link;
use App\Models\Location;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The tag, search, entity and series list pages render cards that read
 * relations and follow state per row; those are eager-loaded or memoized so a
 * page's query count doesn't grow with its rows (#2173).
 */
class ListPagesQueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    private Tag $tag;

    private int $made = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // search matches related tags by exact name without FULLTEXT, which can't
        // see rows inside the test's uncommitted transaction
        $this->tag = Tag::factory()->create(['name' => 'Zzlisttag', 'slug' => 'zzlisttag']);
    }

    private function activeStatusId(): int
    {
        return (int) EntityStatus::where('name', 'Active')->value('id');
    }

    private function makeEntity(): Entity
    {
        $n = ++$this->made;
        $entity = Entity::factory()->create([
            'name' => "Zzlist Entity {$n}",
            'slug' => "zzlist-entity-{$n}",
            'entity_status_id' => $this->activeStatusId(),
        ]);
        $entity->tags()->attach($this->tag->id);
        $entity->roles()->attach(Role::query()->inRandomOrder()->value('id'));
        $entity->links()->attach(Link::factory()->create(['url' => "https://zzlist{$n}.bandcamp.com"])->id);
        Location::factory()->create(['entity_id' => $entity->id]);
        Follow::create(['object_type' => 'entity', 'object_id' => $entity->id, 'user_id' => $this->user->id]);

        return $entity;
    }

    /**
     * One of each list row, all tagged with the page's tag and followed by the user.
     */
    private function makeRows(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $entity = $this->makeEntity();

            $series = Series::factory()->create(['name' => 'Zzlist Series '.$this->made]);
            $series->tags()->attach($this->tag->id);
            $series->entities()->attach($entity->id);
            Location::factory()->create(['entity_id' => $series->venue_id]);
            Event::factory()->create(['series_id' => $series->id, 'start_at' => now()->addDays($i + 2)]);
            Follow::create(['object_type' => 'series', 'object_id' => $series->id, 'user_id' => $this->user->id]);

            foreach ([now()->addDays($i + 1), now()->subDays($i + 1)] as $startAt) {
                $event = Event::factory()->create(['start_at' => $startAt]);
                $event->tags()->attach($this->tag->id);
                $event->entities()->attach($entity->id);
                Location::factory()->create(['entity_id' => $event->venue_id]);
            }

            $thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
            $thread->tags()->attach($this->tag->id);
            Post::factory()->count(2)->create(['thread_id' => $thread->id]);

            // tag cards on the search page, each checking the user's follow state
            Tag::factory()->create(['name' => 'Zzlisttag '.$this->made, 'slug' => 'zzlisttag-'.$this->made]);
        }
    }

    private function queriesFor(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_list_page_query_counts_do_not_grow_with_rows(): void
    {
        $urls = ['/tags/zzlisttag', '/search?keyword=zzlisttag', '/entities', '/entities/following', '/series', '/series/following'];
        $this->actingAs($this->user);

        $this->makeRows(2);
        $few = array_map(fn ($u) => $this->queriesFor($u), $urls);

        $this->makeRows(4);
        $many = array_map(fn ($u) => $this->queriesFor($u), $urls);

        foreach ($urls as $i => $url) {
            $this->assertLessThanOrEqual($few[$i] + 2, $many[$i], "{$url}: 2 rows = {$few[$i]} queries, 6 rows = {$many[$i]}");
        }
    }

    public function test_cards_show_follow_state_from_the_users_follows(): void
    {
        $followed = $this->makeEntity();
        $other = Entity::factory()->create([
            'name' => 'Zzlist Unfollowed',
            'slug' => 'zzlist-unfollowed',
            'entity_status_id' => $this->activeStatusId(),
        ]);
        $other->tags()->attach($this->tag->id);

        $html = $this->actingAs($this->user)->get('/tags/zzlisttag')->assertOk()->getContent();

        $this->assertStringContainsString(route('entities.unfollow', ['id' => $followed->id]), $html);
        $this->assertStringNotContainsString(route('entities.follow', ['id' => $followed->id]), $html);
        $this->assertStringContainsString(route('entities.follow', ['id' => $other->id]), $html);
    }

    public function test_entity_card_edit_link_is_only_shown_to_owners(): void
    {
        $entity = $this->makeEntity();
        $editUrl = route('entities.edit', ['entity' => $entity->slug]);

        // Entity::ownedBy() is a query scope: called on a model it returned a
        // Builder, which is truthy, so every signed-in user saw the edit link
        $this->actingAs($this->user)->get('/tags/zzlisttag')->assertOk()->assertDontSee($editUrl, false);

        $entity->owners()->attach($this->user->id);
        $this->actingAs($this->user)->get('/tags/zzlisttag')->assertOk()->assertSee($editUrl, false);
    }

    public function test_series_upcoming_and_latest_event_pick_one_event_per_series(): void
    {
        $series = Series::factory()->count(2)->create();
        $expected = [];
        foreach ($series as $s) {
            Event::factory()->create(['series_id' => $s->id, 'start_at' => now()->subDays(9)]);
            $latest = Event::factory()->create(['series_id' => $s->id, 'start_at' => now()->subDays(2)]);
            $soonest = Event::factory()->create(['series_id' => $s->id, 'start_at' => now()->addDays(3)]);
            Event::factory()->create(['series_id' => $s->id, 'start_at' => now()->addDays(10)]);
            $expected[$s->id] = [$soonest->id, $latest->id];
        }

        $loaded = Series::with('upcomingEvent', 'latestEvent')->whereIn('id', array_keys($expected))->get();

        foreach ($loaded as $s) {
            $this->assertSame($expected[$s->id], [$s->upcomingEvent?->id, $s->latestEvent?->id]);
            $this->assertSame($expected[$s->id][0], $s->nextEvent()?->id);
        }
    }

    public function test_related_tags_counts_co_occurring_tags_by_name(): void
    {
        $often = Tag::factory()->create(['name' => 'Zzoften', 'slug' => 'zzoften']);
        $once = Tag::factory()->create(['name' => 'Zzonce', 'slug' => 'zzonce']);

        foreach ([[$often], [$often, $once], []] as $others) {
            $event = Event::factory()->create();
            $event->tags()->attach([$this->tag->id, ...array_map(fn ($t) => $t->id, $others)]);
        }
        // an event without this tag doesn't count
        Event::factory()->create()->tags()->attach([$often->id, $once->id]);

        $this->assertSame(['Zzoften' => 2, 'Zzonce' => 1], $this->tag->relatedTags());
    }

    public function test_saving_a_tag_clears_the_cached_tag_options(): void
    {
        Cache::put('form-opts-tags', ['stale'], 3600);
        Cache::put('filter-opts-tags-slug', ['stale'], 3600);

        Tag::factory()->create(['name' => 'Zznewtag', 'slug' => 'zznewtag']);

        $this->assertFalse(Cache::has('form-opts-tags'));
        $this->assertFalse(Cache::has('filter-opts-tags-slug'));
    }
}
