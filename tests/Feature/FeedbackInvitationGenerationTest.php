<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\EventStatus;
use App\Models\ResponseType;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers `feedback:generate` — who gets asked for post-event feedback, and
 * just as importantly who does not (issue #1998).
 */
class FeedbackInvitationGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['feedback.enabled' => true]);
    }

    /**
     * An active, verified user who is opted in to feedback requests.
     */
    private function makeAttendee(array $profileOverrides = []): User
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->profile()->create(array_merge([
            'setting_feedback_requests' => 1,
        ], $profileOverrides));

        return $user->fresh();
    }

    /**
     * EventFactory randomizes visibility_id and pins start_at to now(), so every
     * event used here has to set its own window and visibility explicitly.
     */
    private function makeEvent(string $start, string $end = null, array $overrides = []): Event
    {
        return Event::factory()->create(array_merge([
            'start_at' => $start,
            'end_at' => $end,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'event_status_id' => EventStatus::APPROVED,
        ], $overrides));
    }

    private function attend(User $user, Event $event): EventResponse
    {
        return EventResponse::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => ResponseType::ATTENDING,
        ]);
    }

    /** @test */
    public function it_creates_an_invitation_for_an_attendee_of_a_recently_finished_event(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $invitation = SurveyInvitation::where('user_id', $user->id)->first();

        $this->assertNotNull($invitation);
        $this->assertSame(SurveyInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame('event', $invitation->subject_type);
        $this->assertSame($event->id, $invitation->subject_id);
        $this->assertNotNull($invitation->expires_at);
        $this->assertNotEmpty($invitation->token);
        $this->assertSame(
            SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE)->id,
            $invitation->survey_campaign_id
        );
    }

    /** @test */
    public function it_resolves_the_event_subject_through_the_morph_map(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $invitation = SurveyInvitation::where('user_id', $user->id)->first();

        $this->assertTrue($invitation->hasSubject());
        $this->assertInstanceOf(Event::class, $invitation->subject);
        $this->assertSame($event->id, $invitation->subject->id);
    }

    /** @test */
    public function it_waits_for_the_configured_delay_after_the_event_ends(): void
    {
        $user = $this->makeAttendee();
        // Ended 3 hours ago; delay_hours is 12.
        $event = $this->makeEvent(now()->subHours(6)->toDateTimeString(), now()->subHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_ignores_events_older_than_the_lookback_window(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(40)->toDateTimeString(), now()->subDays(40)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_ignores_future_events(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->addDays(5)->toDateTimeString(), now()->addDays(5)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_falls_back_to_start_at_when_end_at_is_null(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), null);
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::count());
    }

    /** @test */
    public function it_ignores_non_attending_responses(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());

        EventResponse::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => ResponseType::INTERESTED,
        ]);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_ignores_cancelled_events(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(
            now()->subDays(2)->toDateTimeString(),
            now()->subDays(2)->addHours(3)->toDateTimeString(),
            ['event_status_id' => EventStatus::CANCELLED]
        );
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_ignores_events_with_cancelled_visibility(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(
            now()->subDays(2)->toDateTimeString(),
            now()->subDays(2)->addHours(3)->toDateTimeString(),
            ['visibility_id' => Visibility::VISIBILITY_CANCELLED]
        );
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function running_twice_creates_exactly_one_invitation(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();
        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::count());
    }

    /** @test */
    public function it_skips_users_who_opted_out(): void
    {
        $user = $this->makeAttendee(['setting_feedback_requests' => 0]);
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_skips_users_with_no_profile_row(): void
    {
        // Legacy users without a profile row are excluded by design — the whole
        // app treats a missing profile as "not opted in".
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->profile()->delete();

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_skips_users_who_cannot_log_in(): void
    {
        $user = $this->makeAttendee();
        $user->update(['user_status_id' => UserStatus::SUSPENDED]);

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_skips_users_with_unverified_email(): void
    {
        $user = $this->makeAttendee();
        $user->forceFill(['email_verified_at' => null])->save();

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_does_nothing_when_the_feature_flag_is_off(): void
    {
        config(['feedback.enabled' => false]);

        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function dry_run_reports_candidates_without_writing(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate --dry-run')
            ->expectsOutputToContain('1 invitation(s) would be created')
            ->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function it_does_not_silently_drop_candidates_while_inserting(): void
    {
        // The candidate query filters on `survey_invitations.id is null` and the
        // run inserts exactly those rows, so any OFFSET-based paging would walk
        // off a shrinking result set and skip candidates. Iteration must be
        // stable under its own writes.
        config([
            'feedback.max_pending_per_user' => 0,
            'feedback.min_days_between_prompts' => 0,
        ]);

        $event = $this->makeEvent(
            now()->subDays(2)->toDateTimeString(),
            now()->subDays(2)->addHours(3)->toDateTimeString()
        );

        foreach (range(1, 7) as $i) {
            $this->attend($this->makeAttendee(), $event);
        }

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(7, SurveyInvitation::count(), 'Every candidate should get an invitation.');
    }

    /** @test */
    public function dry_run_predicts_the_same_count_the_real_run_creates(): void
    {
        // The point of --dry-run is to predict the real run. Because a dry run
        // writes nothing, the per-user volume caps have to be tracked in memory
        // or it reports every candidate as creatable.
        config(['feedback.max_pending_per_user' => 1]);

        $user = $this->makeAttendee();

        foreach (range(1, 4) as $offset) {
            $event = $this->makeEvent(
                now()->subDays(1 + $offset)->toDateTimeString(),
                now()->subDays(1 + $offset)->addHours(3)->toDateTimeString()
            );
            $this->attend($user, $event);
        }

        $this->artisan('feedback:generate --dry-run')
            ->expectsOutputToContain('1 invitation(s) would be created')
            ->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::count());
    }

    /** @test */
    public function it_respects_the_max_pending_per_user_cap(): void
    {
        config(['feedback.max_pending_per_user' => 1]);

        $user = $this->makeAttendee();

        foreach (range(1, 3) as $offset) {
            $event = $this->makeEvent(
                now()->subDays(1 + $offset)->toDateTimeString(),
                now()->subDays(1 + $offset)->addHours(3)->toDateTimeString()
            );
            $this->attend($user, $event);
        }

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::where('user_id', $user->id)->count());
    }

    /** @test */
    public function dismissing_a_prompt_starts_a_cooldown(): void
    {
        config(['feedback.max_pending_per_user' => 0, 'feedback.min_days_between_prompts' => 7]);

        $user = $this->makeAttendee();
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        // Dismissed two days ago — they told us to back off, so we do.
        SurveyInvitation::factory()->dismissed()->create([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'dismissed_at' => now()->subDays(2),
        ]);

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::where('user_id', $user->id)->count());
    }

    /** @test */
    public function completing_a_prompt_does_not_start_a_cooldown(): void
    {
        // Someone who just answered is engaged. Making them wait a week is how
        // their other events used to age out of the lookback window unasked.
        config(['feedback.max_pending_per_user' => 0, 'feedback.min_days_between_prompts' => 7]);

        $user = $this->makeAttendee();
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        SurveyInvitation::factory()->completed()->create([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'completed_at' => now()->subMinutes(5),
        ]);

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(2, SurveyInvitation::where('user_id', $user->id)->count());
    }

    /** @test */
    public function the_freshest_event_is_queued_first(): void
    {
        config(['feedback.max_pending_per_user' => 1]);

        $user = $this->makeAttendee();

        // Attended in a deliberately unhelpful RSVP order: the oldest event was
        // RSVP'd to first, so ordering by event_responses.id would pick it.
        $oldest = $this->makeEvent(now()->subDays(9)->toDateTimeString(), now()->subDays(9)->addHours(3)->toDateTimeString());
        $newest = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $middle = $this->makeEvent(now()->subDays(5)->toDateTimeString(), now()->subDays(5)->addHours(3)->toDateTimeString());

        $this->attend($user, $oldest);
        $this->attend($user, $newest);
        $this->attend($user, $middle);

        $this->artisan('feedback:generate')->assertSuccessful();

        $invitation = SurveyInvitation::where('user_id', $user->id)->first();

        $this->assertSame($newest->id, $invitation->subject_id, 'Ask while the memory is sharp.');
    }

    /** @test */
    public function a_busy_week_of_events_all_get_asked_about(): void
    {
        // The regression this whole change exists for: with a queue depth of 1
        // and a cooldown on completion, the third event aged out of the
        // 14-day lookback window before the user was ever asked.
        $user = $this->makeAttendee();

        $events = [];
        foreach ([2, 5, 9] as $daysAgo) {
            $event = $this->makeEvent(
                now()->subDays($daysAgo)->toDateTimeString(),
                now()->subDays($daysAgo)->addHours(3)->toDateTimeString()
            );
            $this->attend($user, $event);
            $events[] = $event;
        }

        $this->artisan('feedback:generate')->assertSuccessful();

        $invitations = SurveyInvitation::where('user_id', $user->id)->orderBy('id')->get();

        $this->assertCount(3, $invitations, 'All three events should be queued while still in the window.');
        $this->assertSame(
            [$events[0]->id, $events[1]->id, $events[2]->id],
            $invitations->pluck('subject_id')->all(),
            'Queued freshest first.'
        );

        // Every one of them outlives the lookback window, because expiry is
        // driven by expires_at (30 days), not by the creation window.
        foreach ($invitations as $invitation) {
            $this->assertTrue($invitation->expires_at->isAfter(now()->addDays(14)));
        }
    }

    /** @test */
    public function the_limit_option_caps_how_many_are_created(): void
    {
        config(['feedback.max_pending_per_user' => 0, 'feedback.min_days_between_prompts' => 0]);

        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());

        foreach (range(1, 3) as $i) {
            $this->attend($this->makeAttendee(), $event);
        }

        $this->artisan('feedback:generate --limit=2')->assertSuccessful();

        $this->assertSame(2, SurveyInvitation::count());
    }

    /** @test */
    public function single_event_mode_only_considers_that_event(): void
    {
        config(['feedback.max_pending_per_user' => 0, 'feedback.min_days_between_prompts' => 0]);

        $user = $this->makeAttendee();

        $wanted = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $other = $this->makeEvent(now()->subDays(3)->toDateTimeString(), now()->subDays(3)->addHours(3)->toDateTimeString());

        $this->attend($user, $wanted);
        $this->attend($user, $other);

        $this->artisan('feedback:generate --single-event='.$wanted->slug)->assertSuccessful();

        $this->assertSame(1, SurveyInvitation::count());
        $this->assertSame($wanted->id, SurveyInvitation::first()->subject_id);
    }

    /** @test */
    public function the_expiry_sweep_marks_overdue_invitations_expired(): void
    {
        $invitation = SurveyInvitation::factory()->create([
            'status' => SurveyInvitation::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(SurveyInvitation::STATUS_EXPIRED, $invitation->fresh()->status);
    }

    /** @test */
    public function the_expiry_sweep_leaves_current_invitations_alone(): void
    {
        $invitation = SurveyInvitation::factory()->create([
            'status' => SurveyInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(5),
        ]);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(SurveyInvitation::STATUS_PENDING, $invitation->fresh()->status);
    }

    /** @test */
    public function it_removes_invitations_whose_event_was_deleted(): void
    {
        // Nothing soft-deletes in this app and there's no FK on the polymorphic
        // subject columns, so hard-deleted events leave invitations dangling.
        $invitation = SurveyInvitation::factory()->create(['subject_type' => 'event']);

        Event::where('id', $invitation->subject_id)->delete();

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertDatabaseMissing('survey_invitations', ['id' => $invitation->id]);
    }

    /** @test */
    public function campaign_filter_can_skip_the_post_event_campaign(): void
    {
        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate --campaign=some-other-campaign')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }

    /** @test */
    public function an_inactive_campaign_generates_nothing(): void
    {
        $campaign = SurveyCampaign::where('slug', SurveyCampaign::POST_EVENT_ATTENDEE)->first();
        $campaign->update(['is_active' => false]);
        SurveyCampaign::forgetCached(SurveyCampaign::POST_EVENT_ATTENDEE);

        $user = $this->makeAttendee();
        $event = $this->makeEvent(now()->subDays(2)->toDateTimeString(), now()->subDays(2)->addHours(3)->toDateTimeString());
        $this->attend($user, $event);

        $this->artisan('feedback:generate')->assertSuccessful();

        $this->assertSame(0, SurveyInvitation::count());
    }
}
