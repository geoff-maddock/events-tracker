<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Event;
use App\Models\Follow;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\FeedbackPromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The user-facing half of the proactive feedback interview (issue #1998):
 * prompt resolution, the modal payload, submission, and the ways out.
 */
class FeedbackPromptTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Web endpoints sit behind CSRF; bypass it for the JSON helpers.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        config(['feedback.enabled' => true]);
    }

    /**
     * A user who is eligible for prompts: verified, opted in, and — because
     * onboarding outranks feedback — already following something.
     */
    private function makeUser(array $profileOverrides = []): User
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->profile()->create(array_merge([
            'setting_feedback_requests' => 1,
            'onboarding_completed_at' => now(),
        ], $profileOverrides));

        return $user->fresh();
    }

    private function makeInvitation(User $user, array $overrides = []): SurveyInvitation
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $event = Event::factory()->create([
            'name' => 'Test Show',
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addHours(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        return SurveyInvitation::factory()->create(array_merge([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'subject_type' => 'event',
            'subject_id' => $event->id,
        ], $overrides));
    }

    private function service(): FeedbackPromptService
    {
        return app(FeedbackPromptService::class);
    }

    // ---------------------------------------------------------------- resolution

    /** @test */
    public function it_resolves_a_pending_invitation(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->assertSame($invitation->id, $this->service()->pendingFor($user)?->id);
    }

    /** @test */
    public function it_resolves_nothing_when_the_feature_flag_is_off(): void
    {
        config(['feedback.enabled' => false]);

        $user = $this->makeUser();
        $this->makeInvitation($user);

        $this->assertNull($this->service()->pendingFor($user));
    }

    /** @test */
    public function it_resolves_nothing_when_the_user_opted_out(): void
    {
        $user = $this->makeUser(['setting_feedback_requests' => 0]);
        $this->makeInvitation($user);

        $this->assertNull($this->service()->pendingFor($user));
    }

    /** @test */
    public function it_resolves_nothing_for_a_user_with_no_profile(): void
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->profile()->delete();

        $this->makeInvitation($user->fresh());

        $this->assertNull($this->service()->pendingFor($user->fresh()));
    }

    /** @test */
    public function it_resolves_nothing_for_dismissed_completed_expired_or_snoozed_invitations(): void
    {
        foreach (['dismissed', 'completed', 'expired', 'snoozed'] as $state) {
            $user = $this->makeUser();
            $this->makeInvitation($user, match ($state) {
                'dismissed' => ['status' => SurveyInvitation::STATUS_DISMISSED],
                'completed' => ['status' => SurveyInvitation::STATUS_COMPLETED],
                'expired' => ['expires_at' => now()->subDay()],
                'snoozed' => ['snoozed_until' => now()->addWeek()],
            });

            $this->assertNull($this->service()->pendingFor($user), "State {$state} should not resolve.");
        }
    }

    /** @test */
    public function it_resolves_nothing_when_the_campaign_is_inactive(): void
    {
        $user = $this->makeUser();
        $this->makeInvitation($user);

        SurveyCampaign::where('slug', SurveyCampaign::POST_EVENT_ATTENDEE)->update(['is_active' => false]);

        $this->assertNull($this->service()->pendingFor($user));
    }

    /** @test */
    public function it_resolves_nothing_when_the_subject_event_was_deleted(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        Event::where('id', $invitation->subject_id)->delete();
        SurveyInvitation::forgetPendingCache($user->id);

        $this->assertNull($this->service()->pendingFor($user));
    }

    /** @test */
    public function onboarding_takes_precedence_over_the_feedback_prompt(): void
    {
        // shouldSeeOnboarding() is true for a verified user following nothing
        // who has neither completed nor dismissed onboarding.
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->profile()->create(['setting_feedback_requests' => 1]);
        $user = $user->fresh();

        $this->makeInvitation($user);

        $this->assertTrue($user->shouldSeeOnboarding());
        $this->assertFalse($this->service()->mayDisplayFor($user));
    }

    // ------------------------------------------------------------------- layout

    /** @test */
    public function the_layout_renders_the_prompt_for_a_user_with_an_invitation(): void
    {
        $user = $this->makeUser();
        Follow::create(['user_id' => $user->id, 'object_type' => 'tag', 'object_id' => 1]);
        $this->makeInvitation($user);

        $this->actingAs($user)->get(route('pages.allModules'))
            ->assertOk()
            ->assertSee('feedbackPrompt(', false);
    }

    /** @test */
    public function the_layout_does_not_render_the_prompt_when_disabled(): void
    {
        config(['feedback.enabled' => false]);

        $user = $this->makeUser();
        $this->makeInvitation($user);

        $this->actingAs($user)->get(route('pages.allModules'))
            ->assertOk()
            ->assertDontSee('feedbackPrompt(', false);
    }

    /** @test */
    public function the_layout_does_not_render_the_prompt_for_an_opted_out_user(): void
    {
        $user = $this->makeUser(['setting_feedback_requests' => 0]);
        $this->makeInvitation($user);

        $this->actingAs($user)->get(route('pages.allModules'))
            ->assertOk()
            ->assertDontSee('feedbackPrompt(', false);
    }

    /** @test */
    public function the_layout_shows_onboarding_instead_when_both_apply(): void
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->profile()->create(['setting_feedback_requests' => 1]);
        $this->makeInvitation($user->fresh());

        $this->actingAs($user->fresh())->get(route('pages.allModules'))
            ->assertOk()
            ->assertSee('onboarding(', false)
            ->assertDontSee('feedbackPrompt(', false);
    }

    // ------------------------------------------------------------------- prompt

    /** @test */
    public function the_prompt_endpoint_returns_the_payload_and_marks_it_shown(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $response = $this->actingAs($user)->getJson(route('feedback.prompt'));

        $response->assertOk()
            ->assertJsonPath('invitation.token', $invitation->token)
            ->assertJsonPath('campaign.slug', SurveyCampaign::POST_EVENT_ATTENDEE)
            ->assertJsonPath('subject.type', 'event')
            ->assertJsonPath('subject.name', 'Test Show')
            ->assertJsonCount(6, 'questions')
            ->assertJsonPath('questions.0.key', 'overall_rating')
            ->assertJsonPath('questions.0.required', true)
            ->assertJsonPath('visibility.default', false);

        $this->assertSame(SurveyInvitation::STATUS_SHOWN, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->shown_at);
    }

    /** @test */
    public function the_prompt_endpoint_returns_a_null_invitation_when_nothing_is_pending(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->getJson(route('feedback.prompt'))
            ->assertOk()
            ->assertJsonPath('invitation', null);
    }

    /** @test */
    public function the_prompt_endpoint_404s_when_the_feature_is_off(): void
    {
        $this->withExceptionHandling();
        config(['feedback.enabled' => false]);

        $this->actingAs($this->makeUser())
            ->getJson(route('feedback.prompt'))
            ->assertNotFound();
    }

    // ------------------------------------------------------------------- submit

    /** @test */
    public function it_stores_a_response_with_answers_and_completes_the_invitation(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $response = $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => [
                'overall_rating' => 4,
                'liked_most' => ['lineup', 'sound'],
                'return_likelihood' => 'probably',
                'comments' => 'The opener was great.',
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $stored = SurveyResponse::where('user_id', $user->id)->first();

        $this->assertNotNull($stored);
        $this->assertSame($invitation->id, $stored->survey_invitation_id);
        $this->assertSame('event', $stored->subject_type);
        $this->assertSame($invitation->subject_id, $stored->subject_id);
        $this->assertSame(Visibility::VISIBILITY_PRIVATE, $stored->visibility_id);
        $this->assertNotNull($stored->submitted_at);

        // 1 rating + 2 multi-choice rows + 1 single choice + 1 text
        $this->assertCount(5, $stored->answers);

        $this->assertSame(SurveyInvitation::STATUS_COMPLETED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->completed_at);
    }

    /** @test */
    public function opting_in_to_sharing_makes_the_response_public(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => ['overall_rating' => 5],
            'public' => true,
        ])->assertOk();

        $stored = SurveyResponse::where('user_id', $user->id)->first();

        $this->assertSame(Visibility::VISIBILITY_PUBLIC, $stored->visibility_id);
        $this->assertTrue($stored->isPublic());
        $this->assertSame(1, SurveyResponse::public()->count());
    }

    /** @test */
    public function unknown_answer_keys_are_ignored(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => [
                'overall_rating' => 3,
                'not_a_real_question' => 'whatever',
            ],
        ])->assertOk();

        $this->assertCount(1, SurveyResponse::where('user_id', $user->id)->first()->answers);
    }

    /** @test */
    public function the_required_rating_is_enforced(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => ['comments' => 'No rating given.'],
        ])->assertStatus(422)->assertJsonValidationErrors('answers.overall_rating');

        $this->assertSame(0, SurveyResponse::count());
    }

    /** @test */
    public function an_out_of_range_rating_is_rejected(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => ['overall_rating' => 9],
        ])->assertStatus(422)->assertJsonValidationErrors('answers.overall_rating');
    }

    /** @test */
    public function an_unknown_option_value_is_rejected(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => [
                'overall_rating' => 4,
                'liked_most' => ['not_an_option'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('answers.liked_most.0');
    }

    /** @test */
    public function another_users_invitation_cannot_be_answered(): void
    {
        $this->withExceptionHandling();

        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $invitation = $this->makeInvitation($owner);

        $this->actingAs($intruder)
            ->postJson(route('feedback.store', $invitation), ['answers' => ['overall_rating' => 5]])
            ->assertForbidden();

        $this->assertSame(0, SurveyResponse::count());
    }

    /** @test */
    public function an_already_completed_invitation_returns_gone(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user, ['status' => SurveyInvitation::STATUS_COMPLETED]);

        $this->actingAs($user)
            ->postJson(route('feedback.store', $invitation), ['answers' => ['overall_rating' => 5]])
            ->assertStatus(410);
    }

    /** @test */
    public function an_unknown_token_returns_not_found(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->makeUser())
            ->postJson('/feedback/'.str_repeat('z', 40), ['answers' => ['overall_rating' => 5]])
            ->assertNotFound();
    }

    /** @test */
    public function submitting_while_the_feature_is_off_returns_not_found(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        config(['feedback.enabled' => false]);

        $this->actingAs($user)
            ->postJson(route('feedback.store', $invitation), ['answers' => ['overall_rating' => 5]])
            ->assertNotFound();
    }

    // --------------------------------------------------------------- ways out

    /** @test */
    public function dismissing_closes_the_invitation_and_clears_the_cache(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        // Warm the cache so this genuinely exercises invalidation.
        $this->assertNotNull($this->service()->pendingFor($user));
        $this->assertTrue(Cache::has(SurveyInvitation::pendingCacheKey($user->id)));

        $this->actingAs($user)->postJson(route('feedback.dismiss', $invitation))->assertOk();

        $this->assertSame(SurveyInvitation::STATUS_DISMISSED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->dismissed_at);
        $this->assertNull($this->service()->pendingFor($user->fresh()));
    }

    /** @test */
    public function the_shown_endpoint_records_the_view(): void
    {
        // The in-app modal is seeded by the layout composer rather than by the
        // prompt endpoint, so this is what stamps shown_at for that path.
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.shown', $invitation))->assertOk();

        $fresh = $invitation->fresh();

        $this->assertSame(SurveyInvitation::STATUS_SHOWN, $fresh->status);
        $this->assertNotNull($fresh->shown_at);
    }

    /** @test */
    public function a_shown_invitation_can_still_be_answered(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user, ['status' => SurveyInvitation::STATUS_SHOWN]);

        $this->actingAs($user)->postJson(route('feedback.store', $invitation), [
            'answers' => ['overall_rating' => 5],
        ])->assertOk();

        $this->assertSame(SurveyInvitation::STATUS_COMPLETED, $invitation->fresh()->status);
    }

    /** @test */
    public function snoozing_pushes_the_invitation_out_without_dismissing_it(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.snooze', $invitation))->assertOk();

        $fresh = $invitation->fresh();

        $this->assertNotNull($fresh->snoozed_until);
        $this->assertTrue($fresh->snoozed_until->isFuture());
        $this->assertNotSame(SurveyInvitation::STATUS_DISMISSED, $fresh->status);
        $this->assertNull($this->service()->pendingFor($user->fresh()));
    }

    /** @test */
    public function opting_out_sets_the_profile_flag_and_dismisses_outstanding_invitations(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->postJson(route('feedback.optOut'))->assertOk();

        $this->assertSame(0, (int) $user->fresh()->profile->setting_feedback_requests);
        $this->assertSame(SurveyInvitation::STATUS_DISMISSED, $invitation->fresh()->status);
        $this->assertNull($this->service()->pendingFor($user->fresh()));
    }

    /** @test */
    public function opting_out_works_for_a_user_with_no_profile_row(): void
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->profile()->delete();

        $this->actingAs($user->fresh())->postJson(route('feedback.optOut'))->assertOk();

        $this->assertSame(0, (int) $user->fresh()->profile->setting_feedback_requests);
    }

    /** @test */
    public function an_opted_out_user_cannot_submit(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $user->profile->update(['setting_feedback_requests' => 0]);

        $this->actingAs($user->fresh())
            ->postJson(route('feedback.store', $invitation), ['answers' => ['overall_rating' => 5]])
            ->assertForbidden();
    }

    // --------------------------------------------------------- standalone page

    /** @test */
    public function the_standalone_page_renders_for_the_owner(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $this->actingAs($user)->get(route('feedback.show', $invitation))
            ->assertOk()
            ->assertSee('Test Show')
            ->assertSee('feedbackPrompt(', false);
    }

    /** @test */
    public function the_standalone_page_is_forbidden_for_another_user(): void
    {
        $this->withExceptionHandling();

        $invitation = $this->makeInvitation($this->makeUser());

        $this->actingAs($this->makeUser())
            ->get(route('feedback.show', $invitation))
            ->assertForbidden();
    }

    /** @test */
    public function the_standalone_page_does_not_stack_a_second_modal(): void
    {
        $user = $this->makeUser();
        $invitation = $this->makeInvitation($user);

        $html = $this->actingAs($user)->get(route('feedback.show', $invitation))->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'x-data="feedbackPrompt('),
            'The layout must not add its own prompt on top of the standalone page.'
        );
    }

    // ------------------------------------------------------------------- auth

    /** @test */
    public function all_feedback_endpoints_require_authentication(): void
    {
        $this->withExceptionHandling();

        $invitation = $this->makeInvitation($this->makeUser());

        $this->getJson(route('feedback.prompt'))->assertUnauthorized();
        $this->postJson(route('feedback.optOut'))->assertUnauthorized();
        $this->postJson(route('feedback.dismiss', $invitation))->assertUnauthorized();
        $this->postJson(route('feedback.store', $invitation), ['answers' => []])->assertUnauthorized();
    }
}
