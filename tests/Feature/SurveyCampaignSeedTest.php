<?php

namespace Tests\Feature;

use App\Models\SurveyAnswer;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Visibility;
use Database\Seeders\SurveyCampaignsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the seeded shape of the feedback campaigns (issue #1998).
 *
 * The idempotency tests here are the important ones: every other lookup seeder
 * in this app opens with DB::table(...)->delete(), and copying that pattern
 * would silently destroy collected feedback on the next seed run.
 */
class SurveyCampaignSeedTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function post_event_campaign_is_seeded_and_active(): void
    {
        $campaign = SurveyCampaign::where('slug', SurveyCampaign::POST_EVENT_ATTENDEE)->first();

        $this->assertNotNull($campaign, 'The post-event-attendee campaign should be seeded.');
        $this->assertTrue($campaign->is_active);
        $this->assertSame('event', $campaign->subject_type);
        $this->assertSame(30, $campaign->expires_after_days);
        $this->assertSame(Visibility::VISIBILITY_PRIVATE, $campaign->default_visibility_id);
    }

    /** @test */
    public function by_slug_resolves_the_campaign(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $this->assertNotNull($campaign);
        $this->assertSame(SurveyCampaign::POST_EVENT_ATTENDEE, $campaign->slug);
    }

    /** @test */
    public function campaign_has_the_expected_active_questions_in_sort_order(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $questions = $campaign->activeQuestions()->get();

        $this->assertCount(9, $questions);
        $this->assertSame(
            [
                'attended',
                'not_attended_reason', 'not_attended_detail',
                'overall_rating', 'liked_most', 'liked_least',
                'want_more', 'return_likelihood', 'comments',
            ],
            $questions->pluck('key')->all()
        );

        $sorts = $questions->pluck('sort')->all();
        $sorted = $sorts;
        sort($sorted);
        $this->assertSame($sorted, $sorts, 'Questions should come back in sort order.');
    }

    /** @test */
    public function the_attendance_gate_is_first_unconditional_and_required(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $gate = $campaign->activeQuestions()->get()->first();

        $this->assertSame('attended', $gate->key);
        $this->assertSame(SurveyQuestion::TYPE_SINGLE_CHOICE, $gate->type);
        $this->assertTrue($gate->is_required);
        $this->assertFalse($gate->isConditional(), 'The gate itself must never be conditional.');
        $this->assertSame(['yes', 'no'], $gate->optionValues());
    }

    /** @test */
    public function every_question_after_the_gate_belongs_to_exactly_one_branch(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $branches = $campaign->activeQuestions()->get()
            ->reject(fn (SurveyQuestion $q) => $q->key === 'attended')
            ->groupBy('depends_on_value');

        // Nothing may be left dangling without a branch, or the no-show path
        // would be asked to rate an event they never saw.
        $this->assertSame(['no', 'yes'], $branches->keys()->sort()->values()->all());
        $this->assertCount(2, $branches['no']);
        $this->assertCount(6, $branches['yes']);

        foreach ($branches->flatten() as $question) {
            $this->assertSame('attended', $question->depends_on_key);
        }
    }

    /** @test */
    public function the_rating_is_required_only_within_the_attended_branch(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $rating = $campaign->activeQuestions()->get()->firstWhere('key', 'overall_rating');

        $this->assertSame(SurveyQuestion::TYPE_RATING, $rating->type);
        $this->assertTrue($rating->is_required);
        $this->assertSame(1, $rating->min_value);
        $this->assertSame(5, $rating->max_value);

        $this->assertTrue($rating->appliesGiven(['attended' => 'yes']));
        $this->assertFalse($rating->appliesGiven(['attended' => 'no']));
        $this->assertFalse($rating->appliesGiven([]), 'An unanswered gate hides everything downstream.');
    }

    /** @test */
    public function the_no_show_branch_asks_why(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);
        $questions = $campaign->activeQuestions()->get();

        $reason = $questions->firstWhere('key', 'not_attended_reason');
        $detail = $questions->firstWhere('key', 'not_attended_detail');

        $this->assertNotNull($reason);
        $this->assertNotNull($detail);

        // Both optional — we ask why, we don't demand it.
        $this->assertFalse($reason->is_required);
        $this->assertFalse($detail->is_required);

        $this->assertTrue($reason->appliesGiven(['attended' => 'no']));
        $this->assertFalse($reason->appliesGiven(['attended' => 'yes']));
        $this->assertContains('cost', $reason->optionValues());
        $this->assertSame(SurveyQuestion::TYPE_TEXT, $detail->type);
    }

    /** @test */
    public function every_question_has_a_known_type(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        foreach ($campaign->questions as $question) {
            $this->assertContains(
                $question->type,
                SurveyQuestion::TYPES,
                "Question {$question->key} has an unknown type: {$question->type}"
            );
        }
    }

    /** @test */
    public function choice_questions_have_non_empty_options_with_unique_values(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $choiceQuestions = $campaign->questions->filter(fn (SurveyQuestion $q) => $q->isChoice());

        $this->assertGreaterThan(0, $choiceQuestions->count());

        foreach ($choiceQuestions as $question) {
            $values = $question->optionValues();

            $this->assertNotEmpty($values, "Question {$question->key} has no options.");
            $this->assertSame(
                array_values(array_unique($values)),
                $values,
                "Question {$question->key} has duplicate option values."
            );

            foreach ($question->options as $option) {
                $this->assertArrayHasKey('label', $option);
                $this->assertNotSame('', $option['label']);
            }
        }
    }

    /** @test */
    public function non_choice_questions_have_no_options(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $nonChoice = $campaign->questions->reject(fn (SurveyQuestion $q) => $q->isChoice());

        foreach ($nonChoice as $question) {
            $this->assertNull($question->options, "Question {$question->key} should not carry options.");
        }
    }

    /** @test */
    public function reseeding_is_idempotent(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);
        $originalId = $campaign->id;
        $originalQuestionIds = $campaign->questions->pluck('id')->sort()->values()->all();

        $this->seed(SurveyCampaignsTableSeeder::class);

        $reseeded = SurveyCampaign::where('slug', SurveyCampaign::POST_EVENT_ATTENDEE)->first();

        $this->assertSame($originalId, $reseeded->id, 'Re-seeding must not replace the campaign row.');
        $this->assertSame(1, SurveyCampaign::where('slug', SurveyCampaign::POST_EVENT_ATTENDEE)->count());
        $this->assertCount(9, $reseeded->questions);
        $this->assertSame(
            $originalQuestionIds,
            $reseeded->questions->pluck('id')->sort()->values()->all(),
            'Re-seeding must not replace question rows — answers FK to them.'
        );
    }

    /** @test */
    public function reseeding_preserves_collected_invitations_and_responses(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);
        $user = User::factory()->create(['user_status_id' => 1]);
        $question = $campaign->questions->firstWhere('key', 'overall_rating');

        $invitation = SurveyInvitation::factory()->create([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);

        $response = SurveyResponse::factory()->create([
            'survey_invitation_id' => $invitation->id,
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);

        $answer = SurveyAnswer::factory()->rating(4)->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $question->id,
        ]);

        $this->seed(SurveyCampaignsTableSeeder::class);

        $this->assertDatabaseHas('survey_invitations', ['id' => $invitation->id]);
        $this->assertDatabaseHas('survey_responses', ['id' => $response->id]);
        $this->assertDatabaseHas('survey_answers', ['id' => $answer->id, 'value_int' => 4]);
    }

    /** @test */
    public function invitations_get_a_token_and_are_actionable_by_default(): void
    {
        $invitation = SurveyInvitation::factory()->create(['token' => '']);

        $this->assertNotEmpty($invitation->token);
        $this->assertSame(40, strlen($invitation->token));
        $this->assertTrue($invitation->isActionable());
        $this->assertSame($invitation->id, SurveyInvitation::actionable()->first()?->id);
    }

    /** @test */
    public function expired_dismissed_completed_and_snoozed_invitations_are_not_actionable(): void
    {
        $expired = SurveyInvitation::factory()->expired()->create();
        $dismissed = SurveyInvitation::factory()->dismissed()->create();
        $completed = SurveyInvitation::factory()->completed()->create();
        $snoozed = SurveyInvitation::factory()->snoozed()->create();

        $this->assertFalse($expired->isActionable());
        $this->assertFalse($dismissed->isActionable());
        $this->assertFalse($completed->isActionable());
        $this->assertFalse($snoozed->isActionable());

        $this->assertSame(0, SurveyInvitation::actionable()->count());
    }

    /** @test */
    public function the_target_unique_index_prevents_duplicate_invitations(): void
    {
        $invitation = SurveyInvitation::factory()->create();

        $duplicate = SurveyInvitation::firstOrCreate([
            'survey_campaign_id' => $invitation->survey_campaign_id,
            'user_id' => $invitation->user_id,
            'subject_type' => $invitation->subject_type,
            'subject_id' => $invitation->subject_id,
        ]);

        $this->assertSame($invitation->id, $duplicate->id);
        $this->assertSame(1, SurveyInvitation::count());
    }

    /** @test */
    public function subjectless_invitations_still_dedupe(): void
    {
        // NULL-in-unique-index trap: with nullable subject columns MySQL would
        // treat every subject-less row as distinct and this would insert twice.
        $invitation = SurveyInvitation::factory()->subjectless()->create();

        $duplicate = SurveyInvitation::firstOrCreate([
            'survey_campaign_id' => $invitation->survey_campaign_id,
            'user_id' => $invitation->user_id,
            'subject_type' => '',
            'subject_id' => 0,
        ]);

        $this->assertSame($invitation->id, $duplicate->id);
        $this->assertSame(1, SurveyInvitation::count());
    }
}
