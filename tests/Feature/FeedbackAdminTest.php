<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SurveyAnswer;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin reporting over collected feedback (issue #1998).
 *
 * Responses are private by default, so the gating tests here matter as much as
 * the aggregation ones.
 */
class FeedbackAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    private function makeUser(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function makeEvent(string $name = 'Summer Showcase'): Event
    {
        return Event::factory()->create([
            'name' => $name,
            'start_at' => now()->subDays(3),
            'end_at' => now()->subDays(3)->addHours(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
    }

    /**
     * A full response: rating, two multi-choice options, and a comment.
     */
    private function makeResponse(User $user, Event $event, int $rating = 4, array $overrides = []): SurveyResponse
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        $response = SurveyResponse::factory()->create(array_merge([
            'survey_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'subject_type' => 'event',
            'subject_id' => $event->id,
        ], $overrides));

        SurveyAnswer::factory()->rating($rating)->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $campaign->questions->firstWhere('key', 'overall_rating')->id,
        ]);

        foreach (['lineup', 'sound'] as $option) {
            SurveyAnswer::factory()->option($option)->create([
                'survey_response_id' => $response->id,
                'survey_question_id' => $campaign->questions->firstWhere('key', 'liked_most')->id,
            ]);
        }

        SurveyAnswer::factory()->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $campaign->questions->firstWhere('key', 'comments')->id,
            'value_text' => 'The opener was excellent.',
        ]);

        return $response;
    }

    // ------------------------------------------------------------------ access

    /** @test */
    public function admin_can_view_the_response_index(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent());

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index'))
            ->assertOk()
            ->assertSee('Feedback Responses')
            ->assertSee('Summer Showcase');
    }

    /** @test */
    public function a_non_admin_cannot_view_the_response_index(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->makeUser())
            ->get(route('feedback.admin.index'))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_is_redirected_from_the_response_index(): void
    {
        $this->withExceptionHandling();

        $this->get(route('feedback.admin.index'))->assertRedirect();
    }

    /** @test */
    public function the_summary_and_export_are_admin_only(): void
    {
        $this->withExceptionHandling();

        $user = $this->makeUser();

        $this->actingAs($user)->get(route('feedback.admin.summary'))->assertForbidden();
        $this->actingAs($user)->get(route('feedback.admin.export'))->assertForbidden();
    }

    /** @test */
    public function the_response_detail_page_is_admin_only(): void
    {
        $this->withExceptionHandling();

        $response = $this->makeResponse($this->makeUser(), $this->makeEvent());

        $this->actingAs($this->makeUser())
            ->get(route('feedback.admin.show', $response->id))
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- filters

    /** @test */
    public function filtering_by_event_narrows_the_list(): void
    {
        $wanted = $this->makeEvent('Wanted Event');
        $other = $this->makeEvent('Other Event');

        $this->makeResponse($this->makeUser(), $wanted);
        $this->makeResponse($this->makeUser(), $other);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index', ['event' => 'Wanted']))
            ->assertOk()
            ->assertSee('Wanted Event')
            ->assertDontSee('Other Event');
    }

    /** @test */
    public function filtering_by_user_narrows_the_list(): void
    {
        $wanted = User::factory()->create(['name' => 'Wanted Person', 'user_status_id' => UserStatus::ACTIVE]);
        $other = User::factory()->create(['name' => 'Someone Else', 'user_status_id' => UserStatus::ACTIVE]);

        $this->makeResponse($wanted, $this->makeEvent('Event A'));
        $this->makeResponse($other, $this->makeEvent('Event B'));

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index', ['user' => 'Wanted Person']))
            ->assertOk()
            ->assertSee('Wanted Person')
            ->assertDontSee('Someone Else');
    }

    /** @test */
    public function filtering_by_visibility_narrows_the_list(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent('Private Event'));
        $this->makeResponse($this->makeUser(), $this->makeEvent('Shared Event'), 5, [
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index', ['visibility' => Visibility::VISIBILITY_PUBLIC]))
            ->assertOk()
            ->assertSee('Shared Event')
            ->assertDontSee('Private Event');
    }

    /** @test */
    public function filtering_by_minimum_rating_narrows_the_list(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent('High Rated'), 5);
        $this->makeResponse($this->makeUser(), $this->makeEvent('Low Rated'), 1);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index', ['rating' => 4]))
            ->assertOk()
            ->assertSee('High Rated')
            ->assertDontSee('Low Rated');
    }

    /** @test */
    public function filtering_by_campaign_narrows_the_list(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent('Kept Event'));

        $otherCampaign = SurveyCampaign::factory()->create(['slug' => 'some-other-campaign']);
        SurveyResponse::factory()->create([
            'survey_campaign_id' => $otherCampaign->id,
            'subject_type' => 'event',
            'subject_id' => $this->makeEvent('Dropped Event')->id,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.index', ['campaign' => SurveyCampaign::POST_EVENT_ATTENDEE]))
            ->assertOk()
            ->assertSee('Kept Event')
            ->assertDontSee('Dropped Event');
    }

    // ------------------------------------------------------------------ detail

    /** @test */
    public function the_detail_page_shows_every_answer(): void
    {
        $response = $this->makeResponse($this->makeUser(), $this->makeEvent());

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.show', $response->id))
            ->assertOk()
            ->assertSee('Overall, how was it?')
            ->assertSee('The lineup / music')
            ->assertSee('Sound &amp; production', false)
            ->assertSee('The opener was excellent.')
            ->assertSee('Private');
    }

    // ----------------------------------------------------------------- summary

    /** @test */
    public function the_summary_reports_averages_and_histograms(): void
    {
        $event = $this->makeEvent();

        $this->makeResponse($this->makeUser(), $event, 5);
        $this->makeResponse($this->makeUser(), $event, 3);

        $view = $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.summary', ['campaign' => SurveyCampaign::POST_EVENT_ATTENDEE]))
            ->assertOk();

        // (5 + 3) / 2
        $view->assertSee('4');
        $view->assertSee('The lineup / music');
        $view->assertSee('The opener was excellent.');
    }

    /** @test */
    public function the_summary_reports_the_completion_rate(): void
    {
        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);
        $event = $this->makeEvent();

        // Four invitations, one of which produced a response → 25%.
        SurveyInvitation::factory()->count(4)->create([
            'survey_campaign_id' => $campaign->id,
            'subject_type' => 'event',
            'subject_id' => $event->id,
        ]);

        $this->makeResponse($this->makeUser(), $event);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.summary', ['campaign' => SurveyCampaign::POST_EVENT_ATTENDEE]))
            ->assertOk()
            ->assertSee('25%');
    }

    /** @test */
    public function the_summary_can_be_scoped_to_one_event(): void
    {
        $wanted = $this->makeEvent('Scoped Event');
        $other = $this->makeEvent('Unscoped Event');

        $this->makeResponse($this->makeUser(), $wanted, 5);
        $this->makeResponse($this->makeUser(), $other, 1);

        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.summary', [
                'campaign' => SurveyCampaign::POST_EVENT_ATTENDEE,
                'event' => $wanted->id,
            ]))
            ->assertOk()
            ->assertSee('Scoped Event')
            // Only the 5-star response is in scope.
            ->assertSee('5');
    }

    /** @test */
    public function the_summary_handles_an_unknown_campaign(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.summary', ['campaign' => 'does-not-exist']))
            ->assertOk()
            ->assertSee('could not be found');
    }

    // ------------------------------------------------------------------ export

    /** @test */
    public function the_export_returns_csv_with_a_row_per_response(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent('Exported Event'), 4);

        $response = $this->actingAs($this->makeAdmin())->get(route('feedback.admin.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertStringContainsString('Response ID', $lines[0]);
        $this->assertStringContainsString('overall_rating', $lines[0]);
        $this->assertCount(2, $lines, 'Expected a header row and one data row.');
        $this->assertStringContainsString('Exported Event', $lines[1]);
    }

    /** @test */
    public function the_export_pipe_joins_multi_choice_values(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent(), 4);

        $csv = $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.export'))
            ->streamedContent();

        $this->assertStringContainsString('The lineup / music|Sound & production', $csv);
    }

    /** @test */
    public function the_export_respects_filters(): void
    {
        $this->makeResponse($this->makeUser(), $this->makeEvent('Kept Event'), 5);
        $this->makeResponse($this->makeUser(), $this->makeEvent('Dropped Event'), 1);

        $csv = $this->actingAs($this->makeAdmin())
            ->get(route('feedback.admin.export', ['rating' => 4]))
            ->streamedContent();

        $this->assertStringContainsString('Kept Event', $csv);
        $this->assertStringNotContainsString('Dropped Event', $csv);
    }
}
