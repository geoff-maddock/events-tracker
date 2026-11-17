<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The trial entity-page sections: Recently Performs With / At and Recently
 * Tagged, which only count events from the last two years.
 */
class EntityRecentSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private int $activeStatusId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->activeStatusId = (int) EntityStatus::where('name', 'Active')->value('id');
    }

    private function entity(string $name): Entity
    {
        return Entity::factory()->create(['name' => $name, 'slug' => \Str::slug($name), 'entity_status_id' => $this->activeStatusId]);
    }

    /**
     * @param array<int, Entity> $performers
     * @param array<int, Tag>    $tags
     */
    private function event(Entity $venue, array $performers, array $tags, \DateTimeInterface $startAt, int $visibility = Visibility::VISIBILITY_PUBLIC): Event
    {
        $event = Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => $startAt,
            'visibility_id' => $visibility,
        ]);
        $event->entities()->attach(collect($performers)->pluck('id'));
        $event->tags()->attach(collect($tags)->pluck('id'));

        return $event;
    }

    public function test_recent_rankings_only_count_events_from_the_last_two_years(): void
    {
        $artist = $this->entity('Zz Recent Artist');
        $oldFriend = $this->entity('Zz Old Friend');
        $newFriend = $this->entity('Zz New Friend');
        $oldVenue = $this->entity('Zz Old Venue');
        $newVenue = $this->entity('Zz New Venue');
        $oldTag = Tag::factory()->create(['name' => 'zz-old-tag']);
        $newTag = Tag::factory()->create(['name' => 'zz-new-tag']);

        $this->event($oldVenue, [$artist, $oldFriend], [$oldTag], now()->subYears(3));
        $this->event($oldVenue, [$artist, $oldFriend], [$oldTag], now()->subYears(4));
        $this->event($newVenue, [$artist, $newFriend], [$newTag, $oldTag], now()->subMonths(2));
        $this->event($newVenue, [$artist, $newFriend], [$newTag], now()->subMonths(6));
        // upcoming events haven't happened yet
        $this->event($oldVenue, [$artist, $oldFriend], [$oldTag], now()->addWeek());

        // all-time rankings still see everything
        $this->assertSame($oldFriend->id, $artist->getFrequentlyPerformsWith()->first()?->id);

        $this->assertSame([$newFriend->id], $artist->getRecentlyPerformsWith()->pluck('id')->all());
        $this->assertSame([$newVenue->id], $artist->getRecentlyPerformsAt()->pluck('id')->all());

        $tags = $artist->getRecentlyTagged();
        $this->assertSame([$newTag->id, $oldTag->id], $tags->pluck('id')->all());
        $this->assertSame([2, 1], $tags->pluck('frequency')->map(fn ($f) => (int) $f)->all());
    }

    public function test_recently_tagged_includes_events_hosted_by_a_venue(): void
    {
        $venue = $this->entity('Zz Hosting Venue');
        $band = $this->entity('Zz Touring Band');
        $tag = Tag::factory()->create(['name' => 'zz-venue-tag']);

        $this->event($venue, [$band], [$tag], now()->subMonth());

        $this->assertSame([$tag->id], $venue->getRecentlyTagged()->pluck('id')->all());

        // a space on its own event's lineup isn't listed as somewhere it performs
        $this->event($venue, [$venue, $band], [$tag], now()->subWeek());
        $this->assertTrue($venue->getRecentlyPerformsAt()->isEmpty());
        $this->assertTrue($venue->getFrequentlyPerformsAt()->isEmpty());
    }

    public function test_recent_sections_respect_event_visibility(): void
    {
        $creator = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $artist = $this->entity('Zz Visibility Artist');
        $hidden = $this->entity('Zz Hidden Friend');
        $venue = $this->entity('Zz Hidden Venue');
        $tag = Tag::factory()->create(['name' => 'zz-hidden-tag']);

        $event = $this->event($venue, [$artist, $hidden], [$tag], now()->subMonth(), Visibility::VISIBILITY_PRIVATE);
        $event->forceFill(['created_by' => $creator->id])->saveQuietly();

        $this->assertTrue($artist->getRecentlyPerformsWith()->isEmpty());
        $this->assertTrue($artist->getRecentlyPerformsAt()->isEmpty());
        $this->assertTrue($artist->getRecentlyTagged()->isEmpty());

        $this->assertSame([$hidden->id], $artist->getRecentlyPerformsWith(10, $creator)->pluck('id')->all());
        $this->assertSame([$tag->id], $artist->getRecentlyTagged(20, $creator)->pluck('id')->all());
    }

    public function test_entity_page_shows_the_recent_sections(): void
    {
        $artist = $this->entity('Zz Page Artist');
        $friend = $this->entity('Zz Page Friend');
        $venue = $this->entity('Zz Page Venue');
        $tag = Tag::factory()->create(['name' => 'zz-page-tag']);

        // a single event: the recent sections don't wait for the "frequently" threshold
        $this->event($venue, [$artist, $friend], [$tag], now()->subMonth());

        $this->get('/entities/'.$artist->slug)
            ->assertOk()
            ->assertSee('Recently Performs With')
            ->assertSee('Recently Performs At')
            ->assertSee('Recently Tagged')
            ->assertSee('Zz Page Friend')
            ->assertSee('zz-page-tag');
    }
}
