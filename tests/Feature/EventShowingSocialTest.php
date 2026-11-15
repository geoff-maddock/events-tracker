<?php

namespace Tests\Feature;

use App\Console\Commands\AutomateInstagramPosts;
use App\Jobs\Discord\PostEventToDiscord;
use App\Mail\EntityReminder;
use App\Models\DiscordTarget;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventShare;
use App\Models\EventStatus;
use App\Models\Photo;
use App\Models\ResponseType;
use App\Models\SurveyInvitation;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\EventTime;
use App\Services\Integrations\Discord\DiscordEmbedBuilder;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * A short run is promoted in one post that lists all its dates, and becomes
 * due again as each showing becomes the next one (#2375).
 */
class EventShowingSocialTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** Opened Mar 1; also plays Mar 4 and Mar 6. "Now" is Mar 3 at noon. */
    private Event $run;

    private Event $friday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Carbon::setTestNow('2027-03-03 12:00:00');

        $this->run = $this->event('ZZ Run Play', '2027-03-01 20:00:00');
        $this->run->syncShowings([
            ['start_at' => '2027-03-04 19:00:00'],
            ['start_at' => '2027-03-06 14:00:00', 'end_at' => '2027-03-06 16:00:00'],
        ]);
        $this->run = $this->run->fresh();
        $this->friday = $this->event('ZZ Friday Gig', '2027-03-05 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(string $name, string $startAt): Event
    {
        $event = Event::factory()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $startAt,
            'end_at' => null,
            'door_at' => null,
            'cancelled_at' => null,
            'series_id' => null,
            'do_not_repost' => false,
        ]);
        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    public function test_captions_lead_with_the_next_showing_and_list_the_rest(): void
    {
        $brief = $this->run->getBriefFormat();
        $this->assertStringStartsWith('Thursday March 4th 2027 | ZZ Run Play', $brief);
        $this->assertStringContainsString('at 7PM', $brief);
        $this->assertStringContainsString('Dates: Thu Mar 4 7PM, Sat Mar 6 2PM', $brief);

        $this->assertStringContainsString('Showings: Thu Mar 4 7PM, Sat Mar 6 2PM', $this->run->getInstagramFormat());

        $single = $this->friday->getBriefFormat();
        $this->assertStringStartsWith('Friday March 5th 2027 | ZZ Friday Gig', $single);
        $this->assertStringNotContainsString('Dates:', $single);
        $this->assertStringNotContainsString('Showings:', $this->friday->getInstagramFormat());

        // down to its last date: the headline already says it
        Carbon::setTestNow('2027-03-05 12:00:00');
        $last = $this->run->fresh()->getBriefFormat();
        $this->assertStringStartsWith('Saturday March 6th 2027', $last);
        $this->assertStringNotContainsString('Dates:', $last);
    }

    public function test_the_discord_embed_leads_with_the_next_showing_and_lists_the_rest(): void
    {
        $embed = (new DiscordEmbedBuilder)->forEvent($this->run, DiscordTarget::factory()->create())['embeds'][0];
        $fields = collect($embed['fields'])->keyBy('name');

        $next = EventTime::toInstant('2027-03-04 19:00:00');
        $last = EventTime::toInstant('2027-03-06 14:00:00');

        $this->assertSame($next?->toIso8601String(), $embed['timestamp']);
        $this->assertStringContainsString('<t:'.$next?->timestamp.':F>', $fields['When']['value']);
        $this->assertSame('<t:'.$next?->timestamp.":f>\n<t:".$last?->timestamp.':f>', $fields['Showings']['value']);

        $single = (new DiscordEmbedBuilder)->forEvent($this->friday, DiscordTarget::factory()->create())['embeds'][0];
        $this->assertNotContains('Showings', array_column($single['fields'], 'name'));
    }

    public function test_the_digest_gives_a_run_one_entry_with_its_dates_in_the_window(): void
    {
        $description = (new DiscordEmbedBuilder)->forDigest(
            DiscordTarget::factory()->create(),
            new Collection([$this->run]),
            now(),
            now()->addWeek(),
        )['embeds'][0]['description'];

        $this->assertSame(1, substr_count($description, 'ZZ Run Play'));
        $this->assertStringContainsString(
            '<t:'.EventTime::toInstant('2027-03-04 19:00:00')?->timestamp.':D>, <t:'.EventTime::toInstant('2027-03-06 14:00:00')?->timestamp.':D>',
            $description
        );
    }

    public function test_discord_matches_a_run_on_each_of_its_dates_until_the_last(): void
    {
        $target = DiscordTarget::factory()->matchAll()->create();
        $matcher = app(DiscordTargetMatcher::class);

        $on = fn (string $day) => $matcher->eventsFor($target, Carbon::parse($day)->startOfDay(), Carbon::parse($day)->endOfDay())
            ->pluck('name')->filter(fn ($name) => str_starts_with($name, 'ZZ '))->values()->all();

        $this->assertSame(['ZZ Run Play'], $on('2027-03-04'));
        $this->assertSame([], $on('2027-03-02'));

        // its first date has passed, but it is still on
        $this->assertTrue($matcher->accepts($target, $this->run));

        Carbon::setTestNow('2027-03-07 12:00:00');
        $this->assertFalse($matcher->accepts($target, $this->run));
    }

    public function test_a_run_gets_a_reminder_for_each_date(): void
    {
        config(['discord.enabled' => true]);
        Queue::fake();
        DiscordTarget::factory()->matchAll()->reminders([1])->create();

        $this->artisan('discord:reminders')->assertSuccessful();
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => $job->eventId === $this->run->id && '1:2027-03-04' === $job->reasonKey);

        Carbon::setTestNow('2027-03-04 12:00:00');
        $this->artisan('discord:reminders')->assertSuccessful();
        // a single-date event keeps the plain offset key
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => $job->eventId === $this->friday->id && '1' === $job->reasonKey);

        Carbon::setTestNow('2027-03-05 12:00:00');
        $this->artisan('discord:reminders')->assertSuccessful();
        Queue::assertPushed(PostEventToDiscord::class, fn ($job): bool => $job->eventId === $this->run->id && '1:2027-03-06' === $job->reasonKey);
    }

    /**
     * @return Collection<int, Event>
     */
    private function instagramDue(): Collection
    {
        $method = new \ReflectionMethod(AutomateInstagramPosts::class, 'getEventsToPost');

        return $method->invoke(new AutomateInstagramPosts());
    }

    private function sharedOn(Event $event, string $postedAt): void
    {
        EventShare::create([
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '1',
            'posted_at' => $postedAt,
        ]);
    }

    public function test_instagram_posts_a_run_again_once_a_showing_has_played(): void
    {
        $this->sharedOn($this->run, '2027-02-28 10:00:00');
        $this->sharedOn($this->friday, '2027-02-28 10:00:00');

        // the Mar 1 showing has played since the last post; Friday is only days away
        $due = $this->instagramDue();
        $this->assertTrue($due->contains('id', $this->run->id));
        $this->assertFalse($due->contains('id', $this->friday->id));

        // posted after the Mar 1 showing, and Mar 4 hasn't played yet: nothing new to post
        $this->sharedOn($this->run, '2027-03-01 21:00:00');
        Carbon::setTestNow('2027-03-04 12:00:00');
        $this->assertFalse($this->instagramDue()->contains('id', $this->run->id));
    }

    public function test_emails_show_the_next_showing_and_the_rest(): void
    {
        $entry = view('emails.event-update-markdown', ['event' => $this->run, 'url' => 'http://localhost/'])->render();
        $this->assertStringContainsString('Thursday March 4th 2027', $entry);
        $this->assertStringContainsString('Showings: Thu Mar 4 7PM, Sat Mar 6 2PM', $entry);

        $reminder = (new EntityReminder(
            'http://localhost/',
            'Site',
            'admin@example.com',
            'noreply@example.com',
            'feedback@example.com',
            Entity::factory()->create(),
            new EloquentCollection([$this->run, $this->friday]),
            new EloquentCollection(),
            new EloquentCollection(),
        ))->to('contact@example.com')->render();

        $this->assertStringContainsString('Thursday March 4th 2027 at 7:00 PM (+ more dates)', $reminder);
        $this->assertStringContainsString('Friday March 5th 2027 at 8:00 PM', $reminder);
    }

    public function test_attendees_are_asked_for_feedback_after_the_last_showing(): void
    {
        config(['feedback.enabled' => true]);

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);
        $user->profile()->create(['setting_feedback_requests' => 1]);
        $this->run->update(['event_status_id' => EventStatus::APPROVED]);
        EventResponse::create(['event_id' => $this->run->id, 'user_id' => $user->id, 'response_type_id' => ResponseType::ATTENDING]);

        // the first night was two days ago, but the run isn't over
        $this->artisan('feedback:generate')->assertSuccessful();
        $this->assertSame(0, SurveyInvitation::where('user_id', $user->id)->count());

        Carbon::setTestNow('2027-03-08 12:00:00');
        $this->artisan('feedback:generate')->assertSuccessful();
        $this->assertSame(1, SurveyInvitation::where('user_id', $user->id)->where('subject_id', $this->run->id)->count());
    }
}
