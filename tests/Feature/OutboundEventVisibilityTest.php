<?php

namespace Tests\Feature;

use App\Mail\DailyReminder;
use App\Mail\EntityReminder;
use App\Mail\EntityUpdateSummary;
use App\Mail\FollowingUpdate;
use App\Mail\WeeklyUpdate;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Notifications\EventPublished;
use App\Services\FollowerNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * What goes out of the site (emails to followers and contacts, digests,
 * tweets, captions) never includes someone else's private event (#2244).
 */
class OutboundEventVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PRIVATE_NAME = 'ZZ Private Outbound Event';

    private const PUBLIC_NAME = 'ZZ Public Outbound Event';

    private User $creator;

    private User $follower;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('app.admin', 'admin@example.com');

        $this->creator = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->follower = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email' => 'zz-follower@example.com']);
        Profile::factory()->create([
            'user_id' => $this->follower->id,
            'setting_instant_update' => 1, 'setting_daily_update' => 1, 'setting_weekly_update' => 1,
        ]);

        $this->entity = Entity::factory()->create(['name' => 'Zz Outbound Venue']);
        Follow::create(['user_id' => $this->follower->id, 'object_type' => 'entity', 'object_id' => $this->entity->id]);
    }

    /**
     * A private and a public event by someone else, attached to the followed entity.
     *
     * @return array{0: Event, 1: Event}
     */
    private function events(Carbon $startAt): array
    {
        $this->actingAs($this->creator);
        $made = [];
        foreach ([self::PRIVATE_NAME => Visibility::VISIBILITY_PRIVATE, self::PUBLIC_NAME => Visibility::VISIBILITY_PUBLIC] as $name => $visibility) {
            $event = Event::factory()->create(['name' => $name, 'visibility_id' => $visibility, 'created_by' => $this->creator->id, 'start_at' => $startAt]);
            $event->entities()->attach($this->entity->id);
            $made[] = $event;
        }
        auth()->logout();

        return $made;
    }

    private function assertPublicOnly(string $content, string $where): void
    {
        $this->assertStringNotContainsString(self::PRIVATE_NAME, $content, "{$where} includes someone else's private event");
        $this->assertStringContainsString(self::PUBLIC_NAME, $content, "{$where} lost the public event");
    }

    public function test_followers_are_not_emailed_about_a_private_event(): void
    {
        Mail::fake();
        [$private, $public] = $this->events(Carbon::now()->addDays(3));

        app(FollowerNotifier::class)->event($private);
        Mail::assertNotSent(FollowingUpdate::class);

        app(FollowerNotifier::class)->event($public);
        Mail::assertSent(FollowingUpdate::class, fn ($mail) => $mail->hasTo($this->follower->email));
    }

    public function test_the_daily_and_weekly_digests_leave_out_private_events(): void
    {
        Mail::fake();
        $this->events(Carbon::now()->addHour());

        $this->artisan('notify')->assertExitCode(0);
        Mail::assertSent(DailyReminder::class, function (DailyReminder $mail) {
            $this->assertPublicOnly($mail->render(), 'the daily digest');

            return true;
        });

        $this->artisan('notifyWeekly')->assertExitCode(0);
        Mail::assertSent(WeeklyUpdate::class, function (WeeklyUpdate $mail) {
            $this->assertPublicOnly($mail->render(), 'the weekly digest');

            return true;
        });
    }

    public function test_entity_captions_leave_out_private_events(): void
    {
        $this->events(Carbon::now()->addDays(3));

        $this->assertPublicOnly($this->entity->getInstagramFormat(), 'the Instagram caption');
        $this->assertStringNotContainsString(self::PRIVATE_NAME, $this->entity->getBriefFormat(), 'the tweet includes a private event');
    }

    public function test_the_daily_tweet_skips_private_events(): void
    {
        Notification::fake();
        [$private, $public] = $this->events(Carbon::now()->addHour());

        $this->artisan('dailyTweet')->assertExitCode(0);

        Notification::assertSentTo($public, EventPublished::class);
        Notification::assertNotSentTo($private, EventPublished::class);
    }

    public function test_a_private_event_cannot_be_tweeted(): void
    {
        Notification::fake();
        [$private] = $this->events(Carbon::now()->addDays(3));

        $this->actingAs($this->creator)->post("/events/{$private->id}/tweet")
            ->assertSessionHas('flash_message.message', 'Only public events can be tweeted.');

        Notification::assertNothingSent();
    }

    public function test_emails_to_an_entitys_contact_leave_out_private_events(): void
    {
        Mail::fake();
        $this->events(Carbon::now()->addDays(3));
        $contact = Contact::create(['name' => 'Booking', 'email' => 'zz-booker@example.com']);
        $this->entity->contacts()->attach($contact->id);

        $this->artisan('notifyEntities')->assertExitCode(0);
        Mail::assertSent(EntityReminder::class, function (EntityReminder $mail) {
            $this->assertPublicOnly($mail->render(), 'the entity reminder');

            return true;
        });

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);
        $this->actingAs($admin)->get("/entities/{$this->entity->id}/send-update-summary");
        Mail::assertSent(EntityUpdateSummary::class, function (EntityUpdateSummary $mail) {
            $this->assertPublicOnly($mail->render(), 'the entity update summary');

            return true;
        });
    }
}
