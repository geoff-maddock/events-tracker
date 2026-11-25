<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\Link;
use App\Models\Photo;
use App\Models\ResponseType;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Queries the list pages no longer run (#2390).
 */
class WastedQueriesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);

        return $user;
    }

    private function entity(string $name): Entity
    {
        return Entity::factory()->create([
            'name' => $name,
            'entity_type_id' => EntityType::SPACE,
            'entity_status_id' => EntityStatus::ACTIVE,
        ]);
    }

    /**
     * The SQL of every query $request runs.
     *
     * @return list<string>
     */
    private function queriesOf(callable $request): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return $queries;
    }

    #[Test]
    public function the_entity_list_computes_popularity_only_when_sorted_by_it(): void
    {
        $quiet = $this->entity('Zz Quiet Entity');
        $followed = $this->entity('Zz Followed Entity');
        Follow::create(['object_type' => 'entity', 'object_id' => $followed->id, 'user_id' => $this->activeUser()->id]);

        $byName = $this->queriesOf(fn () => $this->get('/entities')->assertOk());
        $this->assertSame([], array_values(array_filter($byName, fn ($sql) => str_contains($sql, 'entity_follows'))));

        $response = null;
        $byPopularity = $this->queriesOf(function () use (&$response) {
            $response = $this->get('/entities?sort=popularity&direction=desc&filters[name]=Zz')->assertOk();
        });
        $this->assertNotSame([], array_filter($byPopularity, fn ($sql) => str_contains($sql, 'entity_follows')));
        $response->assertSeeInOrder([$followed->name, $quiet->name]);
    }

    #[Test]
    public function the_tag_index_only_queries_for_its_grid(): void
    {
        $response = null;
        $queries = $this->queriesOf(function () use (&$response) {
            $response = $this->get('/tags')->assertOk();
        });

        $response->assertViewMissing('latestTags')->assertViewMissing('events');
        $this->assertSame([], array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'from `series`') || str_contains($sql, 'from `entities`'))));
    }

    #[Test]
    public function is_followed_by_answers_from_one_query_per_kind(): void
    {
        $user = $this->activeUser();
        $entity = $this->entity('Zz Followed Entity');
        $other = $this->entity('Zz Other Entity');
        $tag = Tag::factory()->create();
        $otherTag = Tag::factory()->create();
        Follow::create(['object_type' => 'entity', 'object_id' => $entity->id, 'user_id' => $user->id]);
        Follow::create(['object_type' => 'tag', 'object_id' => $tag->id, 'user_id' => $user->id]);

        $queries = $this->queriesOf(function () use ($user, $entity, $other, $tag, $otherTag) {
            $this->assertTrue($entity->isFollowedBy($user));
            $this->assertFalse($other->isFollowedBy($user));
            $this->assertTrue($tag->isFollowedBy($user));
            $this->assertFalse($otherTag->isFollowedBy($user));
            $this->assertFalse($entity->isFollowedBy(null));
        });

        $this->assertCount(2, $queries);
        // an entity and a tag may share an id: the kind is part of the answer
        $this->assertSame($entity->followedBy($user) !== null, $entity->isFollowedBy($user));
        $this->assertSame($otherTag->followedBy($user) !== null, $otherTag->isFollowedBy($user));
    }

    #[Test]
    public function a_users_own_profile_does_not_look_up_each_follow(): void
    {
        $user = $this->activeUser();
        foreach (range(1, 4) as $i) {
            Follow::create(['object_type' => 'entity', 'object_id' => $this->entity('Zz Followed '.$i)->id, 'user_id' => $user->id]);
            Follow::create(['object_type' => 'tag', 'object_id' => Tag::factory()->create()->id, 'user_id' => $user->id]);
        }

        // the first visit creates the profile and redirects
        $this->actingAs($user)->get('/users/'.$user->id);

        $queries = $this->queriesOf(fn () => $this->actingAs($user)->get('/users/'.$user->id)->assertOk());

        $this->assertSame([], array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'from `follows` where `object_type` = ? and `object_id` = ?'))));
    }

    #[Test]
    public function the_popular_events_api_loads_attendees_in_one_query(): void
    {
        $user = $this->activeUser();
        $events = Event::factory()->count(3)->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(3),
        ]);
        foreach ($events as $event) {
            EventResponse::create(['event_id' => $event->id, 'user_id' => $user->id, 'response_type_id' => ResponseType::ATTENDING]);
        }

        $response = null;
        $queries = $this->queriesOf(function () use (&$response, $user) {
            $response = $this->actingAs($user, 'sanctum')->getJson('/api/events/popular')->assertOk();
        });

        $attendeeQueries = array_filter($queries, fn ($sql) => str_starts_with($sql, 'select `users`.*, `event_responses`.`event_id` as `pivot_event_id`'));
        $this->assertCount(1, $attendeeQueries);

        $first = collect($response->json('data'))->firstWhere('id', $events[0]->id);
        $this->assertSame([$user->id], array_column($first['attendees'], 'id'));
    }

    #[Test]
    public function a_tag_thumbnail_never_comes_from_an_unlisted_entity(): void
    {
        $tag = Tag::factory()->create();
        $unlisted = Entity::factory()->create(['entity_status_id' => EntityStatus::UNLISTED]);
        $unlisted->photos()->attach(Photo::factory()->create(['is_primary' => 1, 'thumbnail' => 'unlisted_thumb.jpg'])->id);
        $unlisted->tags()->attach($tag->id);

        $this->assertNull(Tag::query()->withGridThumbnail()->findOrFail($tag->id)->getAttribute('grid_thumbnail'));

        $listed = $this->entity('Zz Listed Entity');
        $listed->photos()->attach(Photo::factory()->create(['is_primary' => 1, 'thumbnail' => 'listed_thumb.jpg'])->id);
        $listed->tags()->attach($tag->id);

        $this->assertSame('listed_thumb.jpg', Tag::query()->withGridThumbnail()->findOrFail($tag->id)->getAttribute('grid_thumbnail'));
    }

    #[Test]
    public function the_popular_tag_block_follows_a_renamed_or_deleted_tag(): void
    {
        $tag = Tag::factory()->create();
        $tag->events()->attach(Event::factory()->count(2)->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC])->modelKeys());

        $this->assertContains($tag->slug, array_column(Tag::mostUsed(), 'slug'));

        $tag->update(['name' => 'Zz Renamed Tag', 'slug' => 'zz-renamed-tag']);
        $this->assertContains('zz-renamed-tag', array_column(Tag::mostUsed(), 'slug'));

        $tag->delete();
        $this->assertNotContains('zz-renamed-tag', array_column(Tag::mostUsed(), 'slug'));
    }

    #[Test]
    public function a_list_sorted_by_popularity_checks_its_own_page_for_something_to_play(): void
    {
        $artist = $this->entity('Zz Playable Artist');
        $artist->links()->attach(Link::factory()->create(['url' => 'https://soundcloud.com/zz-playable'])->id);

        $response = null;
        $queries = $this->queriesOf(function () use (&$response) {
            $response = $this->get('/entities?sort=popularity&direction=desc&filters[name]=Zz Playable')->assertOk();
        });

        $response->assertSee('Play this list');
        // the totals join the page query and its count, not a third pass for the play button
        $this->assertCount(2, array_filter($queries, fn ($sql) => str_contains($sql, 'entity_follows')));
    }
}
