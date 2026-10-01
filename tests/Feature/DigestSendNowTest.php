<?php

namespace Tests\Feature;

use App\Mail\UserUpdate;
use App\Mail\WeeklyUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Profile;
use App\Models\ResponseType;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The "send now" digest actions on the user page send what the scheduled
 * notify/notifyWeekly commands send (DigestBuilder, #2182) and report what
 * actually happened.
 */
class DigestSendNowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));
        Mail::fake();

        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
        $this->user = $this->member(daily: 1, weekly: 1);
    }

    private function member(int $daily, int $weekly): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $user->id, 'setting_daily_update' => $daily, 'setting_weekly_update' => $weekly]);

        return $user;
    }

    /**
     * An event the user follows through both an entity and a tag, one they are
     * going to next week, and one they are going to in a month.
     */
    private function followedEvents(): void
    {
        $entity = Entity::factory()->create(['name' => 'Zz Followed Entity']);
        $tag = Tag::factory()->create(['name' => 'Zzfollowedtag', 'slug' => 'zzfollowedtag']);
        Follow::create(['user_id' => $this->user->id, 'object_type' => 'entity', 'object_id' => $entity->id]);
        Follow::create(['user_id' => $this->user->id, 'object_type' => 'tag', 'object_id' => $tag->id]);

        $both = Event::factory()->create(['name' => 'Zz Both Ways', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => Carbon::now()->addDays(3)]);
        $both->entities()->attach($entity->id);
        $both->tags()->attach($tag->id);

        $attending = ResponseType::where('name', 'Attending')->value('id');
        foreach (['Zz Next Week' => 6, 'Zz Next Month' => 30] as $name => $days) {
            Event::factory()->create(['name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => Carbon::now()->addDays($days)])
                ->eventResponses()->create(['user_id' => $this->user->id, 'response_type_id' => $attending]);
        }
    }

    public function test_send_now_weekly_matches_the_scheduled_weekly_digest(): void
    {
        $this->followedEvents();

        $this->artisan('notifyWeekly')->assertExitCode(0);
        $scheduled = Mail::sent(WeeklyUpdate::class)->first();

        Mail::fake();
        $this->actingAs($this->user)->post("/users/{$this->user->id}/weekly")
            ->assertSessionHas('flash_message.level', 'success');
        $now = Mail::sent(WeeklyUpdate::class)->first();

        $summary = fn (WeeklyUpdate $mail) => [
            collect($mail->events)->pluck('name')->all(),
            collect($mail->interests)->map(fn ($events) => collect($events)->pluck('name')->all())->all(),
        ];
        $this->assertSame($summary($scheduled), $summary($now));

        // attending in the next two weeks only, and an event listed once
        [$attending, $interests] = $summary($now);
        $this->assertSame(['Zz Next Week'], $attending);
        $this->assertSame(['Zz Followed Entity' => ['Zz Both Ways']], $interests);
    }

    public function test_send_now_weekly_says_so_when_there_is_nothing_to_send(): void
    {
        $this->actingAs($this->user)->post("/users/{$this->user->id}/weekly")
            ->assertSessionHas('flash_message.title', 'Nothing to send');

        Mail::assertNothingSent();
    }

    public function test_the_daily_reminder_is_refused_when_daily_updates_are_off(): void
    {
        $user = $this->member(daily: 0, weekly: 1);

        $this->actingAs($this->admin)->post("/users/{$user->id}/reminder")
            ->assertSessionHas('flash_message.message', 'User has daily updates disabled');

        Mail::assertNothingSent();
    }

    public function test_the_daily_reminder_is_sent(): void
    {
        $this->actingAs($this->admin)->post("/users/{$this->user->id}/reminder")
            ->assertSessionHas('flash_message.level', 'success');

        Mail::assertSent(UserUpdate::class, fn ($mail) => $mail->hasTo($this->user->email));
    }

    public function test_the_unauthenticated_notify_route_is_gone(): void
    {
        $this->get("/users/{$this->user->id}/notify")->assertNotFound();
    }
}
