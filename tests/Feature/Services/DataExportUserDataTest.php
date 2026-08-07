<?php

namespace Tests\Feature\Services;

use App\Models\SurveyAnswer;
use App\Models\SurveyCampaign;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The GDPR export enumerates personal data explicitly, so anything new has to
 * be added by hand. Feedback survey responses are private by default, which
 * makes them exactly the kind of data the export must cover (issue #1998).
 */
class DataExportUserDataTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * @return array<string, mixed>
     */
    private function aggregate(User $user): array
    {
        $method = new ReflectionMethod(DataExportService::class, 'aggregateUserData');
        $method->setAccessible(true);

        return $method->invoke(app(DataExportService::class), $user);
    }

    /** @test */
    public function export_includes_the_feedback_request_setting(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create(['setting_feedback_requests' => 0]);

        $data = $this->aggregate($user->fresh());

        $this->assertArrayHasKey('setting_feedback_requests', $data['profile']['profile']);
        $this->assertSame(0, (int) $data['profile']['profile']['setting_feedback_requests']);
    }

    /** @test */
    public function export_includes_submitted_survey_responses_and_answers(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create([]);

        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);
        $question = $campaign->questions->firstWhere('key', 'overall_rating');

        $response = SurveyResponse::factory()->create([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);

        SurveyAnswer::factory()->rating(4)->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $question->id,
        ]);

        $data = $this->aggregate($user->fresh());

        $this->assertArrayHasKey('survey_responses', $data);
        $this->assertCount(1, $data['survey_responses']);

        $exported = $data['survey_responses']->first();

        $this->assertSame($campaign->name, $exported['campaign']);
        $this->assertSame('private', $exported['visibility']);
        $this->assertCount(1, $exported['answers']);
        $this->assertSame('overall_rating', $exported['answers'][0]['question_key']);
        $this->assertSame('4', $exported['answers'][0]['value']);
    }

    /** @test */
    public function export_does_not_leak_other_users_survey_responses(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create([]);

        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        SurveyResponse::factory()->create(['user_id' => $other->id]);

        $data = $this->aggregate($user->fresh());

        $this->assertCount(0, $data['survey_responses']);
    }
}
