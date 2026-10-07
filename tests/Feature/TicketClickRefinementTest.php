<?php

namespace Tests\Feature;

use App\Models\ClickTrack;
use App\Models\Entity;
use App\Models\EntityStatDaily;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Ticket-link clicks in entity stats count only real people before the event
 * ended, and admins can see each click behind the numbers (#2293).
 */
class TicketClickRefinementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    private function click(Event $event, Carbon $at, ?string $userAgent = self::BROWSER): ClickTrack
    {
        return ClickTrack::create([
            'event_id' => $event->id,
            'venue_id' => $event->venue_id,
            'user_agent' => $userAgent,
            'clicked_at' => $at,
        ]);
    }

    private function clicksOn(Entity $entity, Carbon $day): int
    {
        $this->artisan('entities:rollup-stats', ['--date' => $day->toDateString()])->assertSuccessful();

        return (int) EntityStatDaily::where('entity_id', $entity->id)->whereDate('date', $day)->value('clicks');
    }

    public function test_ai_agents_and_requests_without_a_user_agent_are_not_recorded(): void
    {
        $event = Event::factory()->create(['ticket_link' => 'https://example.com/tickets']);

        $chatGpt = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot';
        $this->withHeaders(['User-Agent' => $chatGpt])->get('/go/evt-'.$event->id)->assertRedirect();
        $this->withHeaders(['User-Agent' => ''])->get('/go/evt-'.$event->id)->assertRedirect();
        $this->assertDatabaseMissing('click_tracks', ['event_id' => $event->id]);

        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/go/evt-'.$event->id)->assertRedirect();
        $this->assertDatabaseHas('click_tracks', ['event_id' => $event->id, 'user_agent' => self::BROWSER]);
    }

    public function test_a_click_after_the_event_ended_is_recorded_but_not_counted(): void
    {
        $day = Carbon::yesterday();
        $venue = Entity::factory()->venue()->create();
        $event = Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => $day->copy()->subDays(3)->setTime(20, 0),
            'end_at' => $day->copy()->subDays(3)->setTime(23, 0),
        ]);

        $this->click($event, $day->copy()->setTime(15, 0));

        $this->assertSame(1, ClickTrack::where('event_id', $event->id)->count());
        $this->assertSame(0, $this->clicksOn($venue, $day));
    }

    public function test_without_an_end_time_clicks_count_until_the_end_of_the_start_day(): void
    {
        $day = Carbon::yesterday();
        $venue = Entity::factory()->venue()->create();
        $event = Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => $day->copy()->setTime(19, 0),
            'end_at' => null,
        ]);

        $this->click($event, $day->copy()->setTime(12, 0));  // before the show
        $this->click($event, $day->copy()->setTime(21, 0));  // after doors, same night
        $this->assertSame(2, $this->clicksOn($venue, $day));

        $this->click($event, Carbon::today()->setTime(0, 30)); // the next day
        $this->assertSame(0, $this->clicksOn($venue, Carbon::today()));
    }

    public function test_an_end_time_equal_to_the_start_still_counts_clicks_that_night(): void
    {
        $day = Carbon::yesterday();
        $venue = Entity::factory()->venue()->create();
        $event = Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => $day->copy()->setTime(20, 0),
            'end_at' => $day->copy()->setTime(20, 0),
        ]);

        $this->click($event, $day->copy()->setTime(21, 30)); // door sales
        $this->assertSame(1, $this->clicksOn($venue, $day));

        $this->click($event, Carbon::today()->setTime(0, 30));
        $this->assertSame(0, $this->clicksOn($venue, Carbon::today()));
    }

    public function test_a_multi_day_event_counts_clicks_until_its_end_time(): void
    {
        $day = Carbon::yesterday();
        $venue = Entity::factory()->venue()->create();
        $event = Event::factory()->create([
            'venue_id' => $venue->id,
            'start_at' => $day->copy()->subDays(2)->setTime(12, 0),
            'end_at' => Carbon::today()->setTime(18, 0),
        ]);

        $this->click($event, $day->copy()->setTime(15, 0)); // day 3 of a festival
        $this->assertSame(1, $this->clicksOn($venue, $day));
    }

    public function test_older_bot_rows_are_left_out_when_stats_are_rebuilt(): void
    {
        $day = Carbon::yesterday();
        $venue = Entity::factory()->venue()->create();
        $event = Event::factory()->create(['venue_id' => $venue->id, 'start_at' => Carbon::now()->addWeek(), 'end_at' => null]);

        $this->click($event, $day->copy()->setTime(10, 0));
        $this->click($event, $day->copy()->setTime(11, 0), 'Mozilla/5.0 (compatible; Claude-User/1.0)');
        $this->click($event, $day->copy()->setTime(12, 0), null);
        $this->click($event, $day->copy()->setTime(13, 0), '');

        $this->assertSame(1, $this->clicksOn($venue, $day));
    }

    public function test_admins_see_each_click_and_why_it_does_or_does_not_count(): void
    {
        $venue = Entity::factory()->venue()->create();
        $upcoming = Event::factory()->create(['name' => 'Zz Upcoming Show', 'venue_id' => $venue->id, 'start_at' => Carbon::now()->addWeek(), 'end_at' => null]);
        $over = Event::factory()->create([
            'name' => 'Zz Finished Show',
            'venue_id' => $venue->id,
            'start_at' => Carbon::now()->subDays(5)->setTime(20, 0),
            'end_at' => Carbon::now()->subDays(5)->setTime(23, 0),
        ]);
        $other = Event::factory()->create(['name' => 'Zz Somewhere Else', 'start_at' => Carbon::now()->addWeek()]);

        $this->click($upcoming, Carbon::now()->subDay());
        $this->click($upcoming, Carbon::now()->subDay(), 'Mozilla/5.0 (compatible; Perplexity-User/1.0)');
        $this->click($over, Carbon::now()->subDays(2));
        $this->click($other, Carbon::now()->subDay());

        $this->actingAs($this->makeUser('admin'))
            ->get(route('entities.stats.clicks', $venue))
            ->assertOk()
            ->assertSee('Zz Upcoming Show')
            ->assertSee('Zz Finished Show')
            ->assertDontSee('Zz Somewhere Else')
            ->assertSee('Bot or AI agent')
            ->assertSee('Event was over')
            ->assertSee('1 of 3 count in the stats');
    }

    public function test_the_click_list_is_admin_only(): void
    {
        $owner = $this->makeUser();
        $venue = Entity::factory()->venue()->create(['created_by' => $owner->id]);
        $link = route('entities.stats.clicks', ['entity' => $venue, 'period' => 30]);

        $this->get(route('entities.stats.clicks', $venue))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('entities.stats.clicks', $venue))->assertForbidden();

        // the owner's stats page doesn't link to it; an admin's does
        $this->actingAs($owner)->get(route('entities.stats', $venue))->assertOk()->assertDontSee(e($link), false);
        $this->actingAs($this->makeUser('admin'))->get(route('entities.stats', $venue))->assertOk()->assertSee(e($link), false);
    }
}
