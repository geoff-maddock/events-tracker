<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Profile;
use App\Models\SurveyAnswer;
use App\Models\SurveyInvitation;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Visibility;
use App\Services\FeedbackPromptService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Proactive feedback interview (issue #1998).
 *
 * Drives the in-app prompt and the standalone feedback page. Invitations are
 * bound by token, not id.
 */
class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackPromptService $prompts)
    {
        parent::__construct();
    }

    /**
     * The payload for whatever prompt this user should see, if any.
     *
     * Marks the invitation as shown. Returns a null invitation rather than an
     * error when there's nothing pending, so the modal can quietly self-close.
     */
    public function prompt(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();

        $invitation = $this->prompts->pendingFor($request->user());

        if (! $invitation) {
            return response()->json(['invitation' => null]);
        }

        $invitation->markShown();

        return response()->json($this->prompts->payload($invitation));
    }

    /**
     * Standalone (non-modal) feedback page. Also the seam for a future emailed
     * feedback link — it already takes the invitation token.
     */
    public function show(Request $request, SurveyInvitation $invitation): View
    {
        $this->assertFeatureEnabled();
        $this->assertOwner($request, $invitation);
        $this->assertActionable($invitation);

        $invitation->loadMissing([
            'campaign.questions',
            'subject' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Event::class => ['venue', 'photos'],
            ]),
        ]);
        $invitation->markShown();

        return view('feedback.show-tw')
            ->with('payload', $this->prompts->payload($invitation));
    }

    /**
     * Record that the prompt was displayed, without answering it.
     */
    public function shown(Request $request, SurveyInvitation $invitation): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->assertOwner($request, $invitation);

        $invitation->markShown();

        return response()->json(['success' => true]);
    }

    /**
     * Store a submission and close out the invitation.
     */
    public function store(Request $request, SurveyInvitation $invitation): JsonResponse|RedirectResponse
    {
        $this->assertFeatureEnabled();
        $this->assertOwner($request, $invitation);
        $this->assertActionable($invitation);

        /** @var User $user */
        $user = $request->user();

        // Defense in depth: opting out already dismisses pending invitations,
        // so this should be unreachable — but the submit path must enforce it.
        abort_if((int) ($user->profile?->setting_feedback_requests ?? 0) !== 1, 403);

        $invitation->loadMissing('campaign.questions');
        $campaign = $invitation->campaign;

        $validated = $request->validate($this->prompts->rulesFor($campaign));

        $answers = $validated['answers'] ?? [];
        $isPublic = (bool) ($validated['public'] ?? false);

        $response = DB::transaction(function () use ($invitation, $campaign, $user, $answers, $isPublic) {
            $response = SurveyResponse::create([
                'survey_invitation_id' => $invitation->id,
                'survey_campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'subject_type' => $invitation->subject_type,
                'subject_id' => $invitation->subject_id,
                'visibility_id' => $isPublic
                    ? Visibility::VISIBILITY_PUBLIC
                    : $campaign->default_visibility_id,
                'submitted_at' => now(),
            ]);

            $this->storeAnswers($response, $campaign->questions->where('is_active', true), $answers);

            $invitation->markCompleted();

            return $response;
        });

        $this->logActivity($invitation, $user);

        if ($request->expectsJson()) {
            // Hand back the next invitation inline so "save & next" doesn't
            // need a second round trip. Null means the queue is now empty.
            $next = $this->prompts->pendingFor($user->fresh());

            if ($next) {
                $next->markShown();
            }

            return response()->json([
                'success' => true,
                'response_id' => $response->id,
                'message' => "Thanks — that's genuinely useful.",
                'next' => $next ? $this->prompts->payload($next) : null,
            ]);
        }

        flash()->success('Thanks', 'Your feedback has been recorded.');

        return redirect()->route('home');
    }

    /**
     * Push this invitation out by a week without dismissing it.
     */
    public function snooze(Request $request, SurveyInvitation $invitation): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->assertOwner($request, $invitation);
        $this->assertActionable($invitation);

        $invitation->snoozed_until = now()->addWeek();
        $invitation->save();

        return response()->json(['success' => true]);
    }

    /**
     * Decline this one invitation. Does not opt the user out of future ones.
     */
    public function dismiss(Request $request, SurveyInvitation $invitation): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->assertOwner($request, $invitation);

        $invitation->markDismissed();

        return response()->json(['success' => true]);
    }

    /**
     * Permanently opt out of all feedback requests.
     *
     * Also dismisses everything currently outstanding, which is what makes the
     * submit-time opt-out check structurally sound rather than decorative.
     */
    public function optOut(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();

        /** @var User $user */
        $user = $request->user();

        // Some legacy users have no profile row yet; create one on demand.
        $profile = $user->profile ?: new Profile(['user_id' => $user->id]);
        $profile->user_id = $user->id;
        $profile->setting_feedback_requests = 0;
        $profile->save();

        $user->surveyInvitations()
            ->whereIn('status', SurveyInvitation::OPEN_STATUSES)
            ->update([
                'status' => SurveyInvitation::STATUS_DISMISSED,
                'dismissed_at' => now(),
                'updated_at' => now(),
            ]);

        SurveyInvitation::forgetPendingCache($user->id);

        return response()->json(['success' => true]);
    }

    /**
     * Write one answer row per value. Multi-choice produces one row per option,
     * which is exactly what the admin group-by wants.
     *
     * @param  \Illuminate\Support\Collection<int, SurveyQuestion>  $questions
     * @param  array<string, mixed>  $answers
     */
    private function storeAnswers(SurveyResponse $response, $questions, array $answers): void
    {
        foreach ($questions as $question) {
            // Unknown keys are ignored; absent optional answers are not stored.
            if (! array_key_exists($question->key, $answers)) {
                continue;
            }

            // Belt and braces: exclude_unless in the rules already strips
            // answers whose branch wasn't taken, but never store one if it
            // somehow survives.
            if (! $question->appliesGiven($answers)) {
                continue;
            }

            $value = $answers[$question->key];

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            match ($question->type) {
                SurveyQuestion::TYPE_RATING => SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $question->id,
                    'value_int' => (int) $value,
                ]),
                SurveyQuestion::TYPE_SINGLE_CHOICE => SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $question->id,
                    'value_option' => (string) $value,
                ]),
                SurveyQuestion::TYPE_MULTI_CHOICE => $this->storeMultiChoice($response, $question, (array) $value),
                default => SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $question->id,
                    'value_text' => (string) $value,
                ]),
            };
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function storeMultiChoice(SurveyResponse $response, SurveyQuestion $question, array $values): void
    {
        foreach (array_unique($values) as $value) {
            SurveyAnswer::create([
                'survey_response_id' => $response->id,
                'survey_question_id' => $question->id,
                'value_option' => (string) $value,
            ]);
        }
    }

    /**
     * Log against the invitation's subject rather than the response.
     *
     * Activity::log() reads $object->name and resolves the action with
     * findOrFail(); a new Action row would need both a seeder entry and a data
     * migration, since production never re-runs seeders.
     */
    private function logActivity(SurveyInvitation $invitation, User $user): void
    {
        $subject = $invitation->subject;

        if ($subject && isset($subject->name)) {
            Activity::log($subject, $user, Action::CREATE, 'Submitted post-event feedback');
        }
    }

    private function assertFeatureEnabled(): void
    {
        abort_unless(config('feedback.enabled'), 404);
    }

    private function assertOwner(Request $request, SurveyInvitation $invitation): void
    {
        abort_unless($request->user() && $invitation->user_id === $request->user()->id, 403);
    }

    /**
     * 410 Gone rather than 404: the invitation existed, it just can no longer
     * be answered.
     */
    private function assertActionable(SurveyInvitation $invitation): void
    {
        abort_unless($invitation->isActionable(), 410);
    }
}
