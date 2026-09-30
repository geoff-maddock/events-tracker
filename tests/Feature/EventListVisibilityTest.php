<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\EventType;
use App\Models\Photo;
use App\Models\ResponseType;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Event lists apply visibility: a private event shows only to its creator, on
 * every page and feed that lists events (they used to list them to anyone).
 */
class EventListVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PRIVATE_NAME = 'ZZ Private Listed Event';

    private const PUBLIC_NAME = 'ZZ Public Listed Event';

    private User $owner;

    private Entity $venue;

    private Entity $artist;

    private Series $series;

    private Photo $photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->owner->profile()->firstOrCreate([])->forceFill(['setting_public_profile' => 1])->save();

        $type = EventType::factory()->create(['name' => 'Zzlisttype']);
        $tag = Tag::factory()->create(['name' => 'Zzlisttag', 'slug' => 'zzlisttag']);
        $this->venue = Entity::factory()->create(['slug' => 'zz-list-venue']);
        $this->venue->roles()->attach(Role::where('slug', 'venue')->value('id'));
        $this->artist = Entity::factory()->create(['slug' => 'zz-list-artist']);
        $this->artist->roles()->attach(Role::where('slug', 'artist')->value('id'));

        $this->series = Series::factory()->create(['slug' => 'zz-list-series', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        // Event stamps created_by from the signed-in user
        $this->actingAs($this->owner);
        $attending = ResponseType::where('name', 'Attending')->value('id');
        foreach ([self::PRIVATE_NAME => Visibility::VISIBILITY_PRIVATE, self::PUBLIC_NAME => Visibility::VISIBILITY_PUBLIC] as $name => $visibility) {
            $event = Event::factory()->create([
                'name' => $name, 'visibility_id' => $visibility, 'created_by' => $this->owner->id,
                'event_type_id' => $type->id, 'start_at' => Carbon::now()->addDays(2),
            ]);
            $event->tags()->attach($tag->id);
            $event->entities()->attach([$this->venue->id, $this->artist->id]);
            $event->eventResponses()->create(['user_id' => $this->owner->id, 'response_type_id' => $attending]);
            EventReview::create(['event_id' => $event->id, 'user_id' => $this->owner->id, 'review_type_id' => 1, 'review' => 'A ZZ review', 'attended' => 1]);

            // a past and an upcoming instance in the series
            foreach ([-3, 3] as $days) {
                Event::factory()->create([
                    'name' => $name.($days < 0 ? ' Past' : ' Next'), 'visibility_id' => $visibility,
                    'created_by' => $this->owner->id, 'series_id' => $this->series->id, 'start_at' => Carbon::now()->addDays($days),
                ]);
            }
            if ($visibility === Visibility::VISIBILITY_PRIVATE) {
                // PhotoFactory writes files; the pages only need a row
                $photoId = DB::table('photos')->insertGetId([
                    'name' => 'zz-list.jpg', 'path' => 'photos/zz-list.jpg', 'thumbnail' => 'photos/tn-zz-list.jpg',
                    'caption' => 'zz', 'is_event' => 1, 'created_by' => $this->owner->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->photo = Photo::findOrFail($photoId);
                $event->photos()->attach($photoId);
            }
        }
        auth()->logout();
    }

    /**
     * @return array<int, string>
     */
    private function publicUrls(): array
    {
        return [
            '/events/type/Zzlisttype',
            "/users/{$this->owner->id}/attending",
            "/users/{$this->owner->id}/attending-ical",
            "/users/{$this->owner->id}/interested-ical",
            '/events/feed/tag/zzlisttag',
            '/venue/zz-list-venue',
            '/artist/zz-list-artist',
            '/entities/zz-list-artist',
            "/users/{$this->owner->slug}",
            "/users/{$this->owner->slug}?tabs[events]=attending",
            '/rss',
            '/rss/tag/zzlisttag',
            '/series/zz-list-series',
            '/series',
            '/photos',
            "/photos/{$this->photo->id}",
            '/reviews',
        ];
    }

    public function test_guests_do_not_see_private_events_in_lists(): void
    {
        foreach ($this->publicUrls() as $url) {
            $content = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(self::PRIVATE_NAME, $content, "{$url} lists a private event to a guest");
        }

        // the lists still work: the public events are there
        $this->get('/events/type/Zzlisttype')->assertSee(self::PUBLIC_NAME);
        $this->get("/users/{$this->owner->id}/attending-ical")->assertSee(self::PUBLIC_NAME);
        $this->get('/rss')->assertSee(self::PUBLIC_NAME);
        $this->get('/series/zz-list-series')->assertSee(self::PUBLIC_NAME.' Past');
        $this->get('/reviews')->assertSee(self::PUBLIC_NAME);
    }

    public function test_other_users_do_not_see_private_events_in_lists(): void
    {
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));

        foreach (array_merge($this->publicUrls(), ['/radar', '/popular', "/users/{$this->owner->id}/ical"]) as $url) {
            $content = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(self::PRIVATE_NAME, $content, "{$url} lists a private event to another user");
        }

        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]), 'sanctum');
        $json = $this->getJson("/api/users/{$this->owner->id}/events-attending")->assertOk()->getContent();
        $this->assertStringNotContainsString(self::PRIVATE_NAME, $json, 'the API attending list shows a private event');
        $this->assertStringContainsString(self::PUBLIC_NAME, $json);

        $this->get('/popular')->assertSee(self::PUBLIC_NAME);
    }

    public function test_the_owner_still_sees_their_private_events(): void
    {
        $this->actingAs($this->owner);

        foreach (['/events/attending', "/users/{$this->owner->id}/attending", "/users/{$this->owner->slug}", '/artist/zz-list-artist', '/radar'] as $url) {
            $this->get($url)->assertOk()->assertSee(self::PRIVATE_NAME);
        }
    }
}
