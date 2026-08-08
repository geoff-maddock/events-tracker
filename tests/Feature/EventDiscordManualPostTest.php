<?php

namespace Tests\Feature;

use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The manual "Post to Discord" action on an event (issue #2058).
 */
class EventDiscordManualPostTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['discord.enabled' => true]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    private function eligibleEvent(): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(10),
            'do_not_repost' => false,
        ]);

        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    public function test_an_ordinary_user_cannot_post_an_event_to_discord(): void
    {
        $this->withExceptionHandling();
        Queue::fake();

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->create();
        $this->signIn();

        $this->post(route('events.discordPost', ['id' => $event->id]))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_an_admin_queues_a_post_to_every_matching_channel(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->count(2)->create();
        $this->signIn($this->admin());

        $this->post(route('events.discordPost', ['id' => $event->id]))
            ->assertRedirect(route('events.show', ['event' => $event->slug]));

        Queue::assertPushed(PostEventToDiscord::class, function (PostEventToDiscord $job) use ($event): bool {
            return $job->eventId === $event->id
                && DiscordPost::REASON_MANUAL === $job->reason
                && 2 === count($job->targetIds);
        });
    }

    public function test_naming_a_channel_posts_only_there(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        $chosen = DiscordTarget::factory()->matchAll()->create();
        DiscordTarget::factory()->matchAll()->create();
        $this->signIn($this->admin());

        $this->post(route('events.discordPost', ['id' => $event->id]), [
            'target_id' => [$chosen->id],
        ])->assertRedirect();

        Queue::assertPushed(PostEventToDiscord::class, fn (PostEventToDiscord $job): bool => [$chosen->id] === $job->targetIds);
    }

    public function test_a_channel_that_disallows_manual_posts_is_skipped(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->create(['allow_manual' => false]);
        $this->signIn($this->admin());

        $this->post(route('events.discordPost', ['id' => $event->id]))->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_a_recent_post_is_soft_blocked_but_force_overrides_it(): void
    {
        Queue::fake();

        $event = $this->eligibleEvent();
        $target = DiscordTarget::factory()->matchAll()->create();
        $this->signIn($this->admin());

        DiscordPost::factory()->create([
            'discord_target_id' => $target->id,
            'event_id' => $event->id,
            'reason' => DiscordPost::REASON_MANUAL,
            'reason_key' => 'x',
            'posted_at' => now()->subMinutes(5),
        ]);

        // Guards against a double-clicked menu item...
        $this->post(route('events.discordPost', ['id' => $event->id]))->assertRedirect();
        Queue::assertNothingPushed();

        // ...without preventing a deliberate repost.
        $this->post(route('events.discordPost', ['id' => $event->id]), ['force' => 1])->assertRedirect();
        Queue::assertPushed(PostEventToDiscord::class);
    }

    public function test_nothing_is_queued_while_the_feature_is_switched_off(): void
    {
        config(['discord.enabled' => false]);
        Queue::fake();

        $event = $this->eligibleEvent();
        DiscordTarget::factory()->matchAll()->create();
        $this->signIn($this->admin());

        $this->post(route('events.discordPost', ['id' => $event->id]))->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_the_action_appears_on_the_event_page_for_admins_only(): void
    {
        $event = $this->eligibleEvent();

        $this->signIn($this->admin());
        $this->get(route('events.show', ['event' => $event->slug]))
            ->assertOk()
            ->assertSee('Post to Discord');

        $this->signIn();
        $this->get(route('events.show', ['event' => $event->slug]))
            ->assertOk()
            ->assertDontSee('Post to Discord');
    }
}
