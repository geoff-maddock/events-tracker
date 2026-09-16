<?php

namespace Tests\Feature;

use App\Models\ClickTrack;
use App\Models\Entity;
use App\Models\EntityStatDaily;
use App\Models\Event;
use App\Models\EventReachDaily;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\EntityStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tracking behind the entity owner dashboard (#2146).
 */
class EntityStatsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15';

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    private function viewsToday(Entity $entity): int
    {
        return (int) EntityStatDaily::where('entity_id', $entity->id)
            ->whereDate('date', Carbon::today())
            ->value('views');
    }

    private function follow(User $user, Entity $entity, Carbon $at): void
    {
        $follow = new Follow();
        $follow->forceFill([
            'user_id' => $user->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();
    }

    // Page views

    public function test_a_browser_view_is_counted_and_repeat_views_increment(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->withHeader('User-Agent', self::BROWSER)->get(route('entities.show', $entity))->assertOk();
        $this->withHeader('User-Agent', self::BROWSER)->get(route('entities.show', $entity))->assertOk();

        $this->assertSame(2, $this->viewsToday($entity));
    }

    public function test_bots_scripts_and_prefetches_are_not_counted(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get(route('entities.show', $entity))->assertOk();
        $this->withHeader('User-Agent', '')->get(route('entities.show', $entity))->assertOk();
        $this->withHeaders(['User-Agent' => self::BROWSER, 'Sec-Purpose' => 'prefetch'])
            ->get(route('entities.show', $entity))->assertOk();

        $this->assertSame(0, $this->viewsToday($entity));
    }

    public function test_owner_and_admin_views_are_not_counted(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->withHeader('User-Agent', self::BROWSER)
            ->get(route('entities.show', $entity))->assertOk();
        $this->actingAs($this->makeUser('admin'))->withHeader('User-Agent', self::BROWSER)
            ->get(route('entities.show', $entity))->assertOk();

        $this->assertSame(0, $this->viewsToday($entity));

        $this->actingAs($this->makeUser())->withHeader('User-Agent', self::BROWSER)
            ->get(route('entities.show', $entity))->assertOk();

        $this->assertSame(1, $this->viewsToday($entity));
    }

    // Rollup

    public function test_rollup_counts_follows_clicks_and_responses_for_the_day(): void
    {
        $day = Carbon::yesterday()->setTime(15, 0);
        $venue = Entity::factory()->venue()->create();
        $artist = Entity::factory()->create();

        $event = Event::factory()->create(['venue_id' => $venue->id, 'promoter_id' => null]);
        // the venue is both the event's venue and billed on it: credit it once per click
        $event->entities()->attach([$venue->id, $artist->id]);

        $this->follow($this->makeUser(), $artist, $day);
        $this->follow($this->makeUser(), $artist, $day);
        $this->follow($this->makeUser(), $artist, Carbon::today()->setTime(9, 0)); // outside the day

        ClickTrack::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'clicked_at' => $day]);
        ClickTrack::create(['event_id' => $event->id, 'venue_id' => $venue->id, 'clicked_at' => $day]);

        $response = new EventResponse();
        $response->forceFill([
            'event_id' => $event->id,
            'user_id' => $this->makeUser()->id,
            'response_type_id' => ResponseType::ATTENDING,
            'created_at' => $day,
            'updated_at' => $day,
        ])->save();

        $this->artisan('entities:rollup-stats', ['--date' => $day->toDateString()])->assertSuccessful();

        $artistRow = EntityStatDaily::where('entity_id', $artist->id)->whereDate('date', $day)->firstOrFail();
        $this->assertSame(2, $artistRow->follows);
        $this->assertSame(2, $artistRow->clicks);
        $this->assertSame(1, $artistRow->responses);

        $venueRow = EntityStatDaily::where('entity_id', $venue->id)->whereDate('date', $day)->firstOrFail();
        $this->assertSame(0, $venueRow->follows);
        $this->assertSame(2, $venueRow->clicks);
        $this->assertSame(1, $venueRow->responses);
    }

    public function test_rollup_is_idempotent_and_keeps_live_views(): void
    {
        $day = Carbon::yesterday()->setTime(12, 0);
        $entity = Entity::factory()->create();
        $follower = $this->makeUser();
        $this->follow($follower, $entity, $day);

        EntityStatDaily::create(['entity_id' => $entity->id, 'date' => $day->toDateString(), 'views' => 7]);

        $stats = app(EntityStats::class);
        $stats->rollupDay($day);
        $stats->rollupDay($day);

        $row = EntityStatDaily::where('entity_id', $entity->id)->whereDate('date', $day)->sole();
        $this->assertSame(7, $row->views);
        $this->assertSame(1, $row->follows);

        // an unfollow since the last run is reflected on re-run
        Follow::where('user_id', $follower->id)->delete();
        $stats->rollupDay($day);

        $this->assertSame(0, $row->fresh()->follows);
        $this->assertSame(7, $row->fresh()->views);
    }

    // Digest reach

    public function test_weekly_digest_records_one_reach_per_recipient_per_event(): void
    {
        Mail::fake();

        $artist = Entity::factory()->create();
        $event = Event::factory()->create(['start_at' => Carbon::now()->addDays(3)]);
        $event->entities()->attach($artist->id);

        foreach ([1, 2] as $i) {
            $user = $this->makeUser();
            Profile::factory()->create(['user_id' => $user->id, 'setting_weekly_update' => 1]);
            $this->follow($user, $artist, Carbon::now()->subWeek());
        }

        $this->artisan('notifyWeekly')->assertSuccessful();

        $reach = EventReachDaily::where('event_id', $event->id)
            ->where('channel', EventReachDaily::CHANNEL_DIGEST)
            ->sole();
        $this->assertSame(2, $reach->count);
    }
}
