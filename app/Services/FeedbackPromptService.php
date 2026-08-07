<?php

namespace App\Services;

use App\Models\Event;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Decides whether to show a user a feedback prompt, and builds the payload the
 * modal renders (issue #1998).
 *
 * The resolution path runs on every authenticated page load, so it is guarded
 * in four layers, cheapest first:
 *
 *   1. config('feedback.enabled')                  — zero queries
 *   2. profile opt-out                             — profile is already warm
 *   3. onboarding mutual exclusion                 — never stack two modals
 *   4. short-lived cache of the invitation id      — avoids a per-request query
 *
 * Only a non-zero cached id causes the full hydration query to run.
 */
class FeedbackPromptService
{
    /**
     * Seconds to cache "which invitation, if any". Bounded staleness against
     * the daily generator; writes invalidate it eagerly.
     */
    private const CACHE_TTL = 300;

    /**
     * Resolve the invitation to prompt this user with, if any.
     */
    public function pendingFor(?User $user): ?SurveyInvitation
    {
        if (! $this->isEnabledFor($user)) {
            return null;
        }

        /** @var User $user */
        $resolve = fn () => (int) ($this->resolveQuery($user)->value('survey_invitations.id') ?? 0);

        try {
            // Deliberately caches a scalar, never a serialized Eloquent model.
            $invitationId = Cache::remember(
                SurveyInvitation::pendingCacheKey($user->id),
                self::CACHE_TTL,
                $resolve
            );
        } catch (Throwable $e) {
            // This runs on every authenticated page load. The cache saves a
            // query; it must never be able to take the whole site down.
            Log::warning('Feedback prompt cache unavailable, falling back to a direct query.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            $invitationId = $resolve();
        }

        if ($invitationId === 0) {
            return null;
        }

        $invitation = SurveyInvitation::with(['campaign.questions', 'subject'])->find($invitationId);

        // Guard against a cache entry that outlived its row, and against an
        // invitation whose subject was hard-deleted (nothing soft-deletes here,
        // and there is no FK on the polymorphic subject columns).
        if (! $invitation || ! $invitation->isActionable()) {
            SurveyInvitation::forgetPendingCache($user->id);

            return null;
        }

        if ($invitation->hasSubject() && $invitation->subject === null) {
            SurveyInvitation::forgetPendingCache($user->id);

            return null;
        }

        return $invitation;
    }

    /**
     * The cheap guards: feature flag, per-user opt-out, and onboarding.
     */
    public function isEnabledFor(?User $user): bool
    {
        if (! config('feedback.enabled')) {
            return false;
        }

        if (! $user) {
            return false;
        }

        // A missing profile row counts as not opted in, matching the strict
        // `$user?->profile?->setting_x !== 1` guard used across the app.
        if ((int) ($user->profile?->setting_feedback_requests ?? 0) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Whether a prompt may be displayed in the layout right now. Onboarding
     * wins — two stacked modals is a bug, not a feature.
     */
    public function mayDisplayFor(?User $user): bool
    {
        if (! $this->isEnabledFor($user)) {
            return false;
        }

        /** @var User $user */
        if ($user->shouldSeeOnboarding() || session('show_onboarding')) {
            return false;
        }

        return true;
    }

    /**
     * Whether the layout should render the prompt for this specific request.
     *
     * On top of mayDisplayFor(), skips non-GET requests, XHR, and the feedback
     * pages themselves — the standalone page renders the component inline and
     * would otherwise get a second copy stacked over it.
     */
    public function mayDisplayInRequest(?Request $request, ?User $user): bool
    {
        if (! $this->mayDisplayFor($user)) {
            return false;
        }

        if (! $request) {
            return true;
        }

        if (! $request->isMethod('GET') || $request->ajax()) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return ! ($routeName && str_starts_with($routeName, 'feedback.'));
    }

    /**
     * Highest-priority actionable invitation for this user.
     *
     * @return \Illuminate\Database\Eloquent\Builder<SurveyInvitation>
     */
    private function resolveQuery(User $user)
    {
        return SurveyInvitation::query()
            ->join('survey_campaigns', 'survey_campaigns.id', '=', 'survey_invitations.survey_campaign_id')
            ->where('survey_invitations.user_id', $user->id)
            ->where('survey_campaigns.is_active', true)
            ->actionable()
            ->orderByDesc('survey_campaigns.priority')
            ->orderBy('survey_invitations.created_at');
    }

    /**
     * Build the JSON payload the modal renders.
     *
     * @return array<string, mixed>
     */
    public function payload(SurveyInvitation $invitation): array
    {
        $campaign = $invitation->campaign;

        return [
            'invitation' => [
                'token' => $invitation->token,
                'url' => route('feedback.show', $invitation),
            ],
            'campaign' => [
                'slug' => $campaign->slug,
                'name' => $campaign->name,
                'intro' => $campaign->intro,
            ],
            'subject' => $this->subjectPayload($invitation),
            'questions' => $this->questionsPayload($campaign),
            'visibility' => [
                'prompt' => $this->visibilityPrompt($invitation),
                'default' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subjectPayload(SurveyInvitation $invitation): ?array
    {
        if (! $invitation->hasSubject()) {
            return null;
        }

        $subject = $invitation->subject;

        if (! $subject) {
            return null;
        }

        $payload = [
            'type' => $invitation->subject_type,
            'name' => $subject->name ?? null,
            'url' => null,
            'date' => null,
        ];

        if ($subject instanceof Event) {
            $payload['url'] = route('events.show', $subject->slug ?: $subject->getKey());
            $payload['date'] = $subject->start_at?->format('D M j, Y');
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questionsPayload(SurveyCampaign $campaign): array
    {
        return $campaign->questions
            ->where('is_active', true)
            ->sortBy('sort')
            ->map(fn (SurveyQuestion $question) => [
                'key' => $question->key,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'help' => $question->help,
                'required' => (bool) $question->is_required,
                'min' => $question->min_value,
                'max' => $question->max_value,
                'options' => $question->options ?? [],
                'maxlength' => $question->type === SurveyQuestion::TYPE_TEXT
                    ? SurveyQuestion::TEXT_MAX_LENGTH
                    : null,
            ])
            ->values()
            ->all();
    }

    private function visibilityPrompt(SurveyInvitation $invitation): string
    {
        if ($invitation->subject_type === 'event') {
            return "Show my feedback publicly on this event's page";
        }

        return 'Share my feedback publicly';
    }

    /**
     * Validation rules for a submission, derived from the campaign's active
     * questions rather than hand-maintained alongside them.
     *
     * @return array<string, mixed>
     */
    public function rulesFor(SurveyCampaign $campaign): array
    {
        $rules = [
            'answers' => ['required', 'array'],
            'public' => ['sometimes', 'boolean'],
        ];

        foreach ($campaign->questions->where('is_active', true) as $question) {
            $field = 'answers.'.$question->key;
            $required = $question->is_required ? 'required' : 'nullable';

            switch ($question->type) {
                case SurveyQuestion::TYPE_RATING:
                    $rules[$field] = [
                        $required,
                        'integer',
                        'between:'.($question->min_value ?? 1).','.($question->max_value ?? 5),
                    ];
                    break;

                case SurveyQuestion::TYPE_SINGLE_CHOICE:
                    $rules[$field] = [$required, 'string', 'in:'.implode(',', $question->optionValues())];
                    break;

                case SurveyQuestion::TYPE_MULTI_CHOICE:
                    $rules[$field] = [$required, 'array'];
                    $rules[$field.'.*'] = ['string', 'in:'.implode(',', $question->optionValues())];
                    break;

                case SurveyQuestion::TYPE_TEXT:
                default:
                    $rules[$field] = [$required, 'string', 'max:'.SurveyQuestion::TEXT_MAX_LENGTH];
                    break;
            }
        }

        return $rules;
    }
}
