<?php

namespace App\Http\Controllers;

use App\Filters\SurveyResponseFilters;
use App\Models\Event;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin reporting over collected feedback (issue #1998).
 *
 * Responses are private by default, so every route here is admin-gated. This
 * follows the newer EventGraphController shape — validated request filters and
 * a plain paginator — rather than the older ListParameterSessionStore ceremony,
 * because nothing here needs filters persisted across navigations.
 *
 * Note this is deliberately separate from the existing Event Reviews feature:
 * reviews are unsolicited, public and editorial; feedback is solicited,
 * private-by-default and campaign-driven. The summary cross-links the two.
 */
class FeedbackAdminController extends Controller
{
    /**
     * Filters accepted by the response list.
     */
    protected const ALLOWED_FILTERS = [
        'campaign', 'user', 'event', 'rating', 'visibility',
        'submitted_after', 'submitted_before',
    ];

    protected const PER_PAGE = 25;

    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
        parent::__construct();
    }

    /**
     * Paginated list of submitted responses.
     */
    public function index(Request $request, SurveyResponseFilters $filters): View
    {
        $applied = $this->appliedFilters($request);

        $query = SurveyResponse::with(['campaign', 'user', 'subject', 'answers.question'])
            ->orderByDesc('submitted_at');

        $filters->applyFilters($query, $applied);

        /** @var LengthAwarePaginator $responses */
        $responses = $query->paginate(self::PER_PAGE)->appends($request->query());

        return view('feedback.admin.index-tw')
            ->with('responses', $responses)
            ->with('filters', $applied)
            ->with('campaigns', SurveyCampaign::orderBy('name')->get())
            ->with('visibilities', Visibility::orderBy('id')->get());
    }

    /**
     * A single response with all of its answers.
     */
    public function show(SurveyResponse $surveyResponse): View
    {
        $surveyResponse->load(['campaign', 'user', 'subject', 'answers.question', 'visibility', 'invitation']);

        return view('feedback.admin.show-tw')->with('response', $surveyResponse);
    }

    /**
     * Per-campaign rollup: completion rate, rating averages, option histograms.
     */
    public function summary(Request $request): View
    {
        $slug = $request->query('campaign') ?: SurveyCampaign::POST_EVENT_ATTENDEE;
        $eventId = $request->query('event');

        $campaign = SurveyCampaign::where('slug', $slug)->first();

        if (! $campaign) {
            return view('feedback.admin.summary-tw')
                ->with('campaign', null)
                ->with('campaigns', SurveyCampaign::orderBy('name')->get())
                ->with('stats', [])
                ->with('event', null)
                ->with('counts', ['invitations' => 0, 'responses' => 0, 'completion' => 0.0]);
        }

        $campaign->load('questions');

        $event = $eventId ? Event::find($eventId) : null;

        return view('feedback.admin.summary-tw')
            ->with('campaign', $campaign)
            ->with('campaigns', SurveyCampaign::orderBy('name')->get())
            ->with('event', $event)
            ->with('counts', $this->counts($campaign, $event))
            ->with('stats', $this->questionStats($campaign, $event))
            ->with('comments', $this->recentComments($campaign, $event));
    }

    /**
     * CSV of responses: one row per response, one column per question key.
     */
    public function export(Request $request, SurveyResponseFilters $filters): StreamedResponse
    {
        $applied = $this->appliedFilters($request);

        $query = SurveyResponse::with(['campaign.questions', 'user', 'subject', 'answers.question'])
            ->orderByDesc('submitted_at');

        $filters->applyFilters($query, $applied);

        $responses = $query->get();

        // Column set is the union of every question key present, so a mixed
        // campaign export still lines up.
        $questionKeys = $responses
            ->flatMap(fn (SurveyResponse $r) => $r->campaign?->questions->pluck('key') ?? collect())
            ->unique()
            ->values()
            ->all();

        $filename = 'feedback-responses-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($responses, $questionKeys, $applied): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                Log::error('Failed to open output stream for feedback export', ['filters' => $applied]);

                return;
            }

            fputcsv($output, array_merge(
                ['Response ID', 'Campaign', 'User', 'Subject', 'Visibility', 'Submitted'],
                $questionKeys
            ));

            foreach ($responses as $response) {
                $byKey = [];

                foreach ($response->answers as $answer) {
                    $key = $answer->question?->key;

                    if (! $key) {
                        continue;
                    }

                    // Multi-choice stores one row per option; join them back up.
                    $byKey[$key] = isset($byKey[$key])
                        ? $byKey[$key].'|'.$answer->displayValue()
                        : $answer->displayValue();
                }

                fputcsv($output, array_merge([
                    $response->id,
                    $response->campaign?->name,
                    $response->user?->name,
                    $response->subject->name ?? null,
                    $response->isPublic() ? 'public' : 'private',
                    $response->submitted_at?->toDateTimeString(),
                ], array_map(fn ($key) => $byKey[$key] ?? '', $questionKeys)));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Only the whitelisted, non-empty filters from the request.
     *
     * @return array<string, string>
     */
    private function appliedFilters(Request $request): array
    {
        return array_filter(
            $request->only(self::ALLOWED_FILTERS),
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @return array{invitations: int, responses: int, completion: float}
     */
    private function counts(SurveyCampaign $campaign, ?Event $event): array
    {
        $invitations = SurveyInvitation::where('survey_campaign_id', $campaign->id)
            ->when($event, fn ($q) => $q->where('subject_type', 'event')->where('subject_id', $event->id))
            ->count();

        $responses = SurveyResponse::where('survey_campaign_id', $campaign->id)
            ->when($event, fn ($q) => $q->where('subject_type', 'event')->where('subject_id', $event->id))
            ->count();

        return [
            'invitations' => $invitations,
            'responses' => $responses,
            'completion' => $invitations > 0 ? round(($responses / $invitations) * 100, 1) : 0.0,
        ];
    }

    /**
     * Per-question aggregates — average for ratings, a histogram for choices.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionStats(SurveyCampaign $campaign, ?Event $event): array
    {
        $responseIds = SurveyResponse::where('survey_campaign_id', $campaign->id)
            ->when($event, fn ($q) => $q->where('subject_type', 'event')->where('subject_id', $event->id))
            ->pluck('id');

        $stats = [];

        foreach ($campaign->questions->sortBy('sort') as $question) {
            if ($question->type === SurveyQuestion::TYPE_TEXT) {
                continue;
            }

            $base = DB::table('survey_answers')
                ->where('survey_question_id', $question->id)
                ->whereIn('survey_response_id', $responseIds);

            if ($question->type === SurveyQuestion::TYPE_RATING) {
                $stats[] = [
                    'question' => $question,
                    'kind' => 'rating',
                    'average' => round((float) ($base->clone()->avg('value_int') ?? 0), 2),
                    'count' => $base->clone()->count(),
                    'histogram' => [],
                ];

                continue;
            }

            $rows = $base->clone()
                ->select('value_option', DB::raw('COUNT(*) as total'))
                ->groupBy('value_option')
                ->orderByDesc('total')
                ->get();

            $stats[] = [
                'question' => $question,
                'kind' => 'choice',
                'average' => null,
                'count' => (int) $rows->sum('total'),
                'histogram' => $rows->map(fn ($row) => [
                    'value' => $row->value_option,
                    'label' => $question->labelForOption((string) $row->value_option),
                    'total' => (int) $row->total,
                ])->all(),
            ];
        }

        return $stats;
    }

    /**
     * Most recent free-text answers, for the qualitative half of the picture.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\SurveyAnswer>
     */
    private function recentComments(SurveyCampaign $campaign, ?Event $event)
    {
        $textQuestionIds = $campaign->questions
            ->where('type', SurveyQuestion::TYPE_TEXT)
            ->pluck('id');

        if ($textQuestionIds->isEmpty()) {
            return collect();
        }

        $responseIds = SurveyResponse::where('survey_campaign_id', $campaign->id)
            ->when($event, fn ($q) => $q->where('subject_type', 'event')->where('subject_id', $event->id))
            ->pluck('id');

        return \App\Models\SurveyAnswer::with(['response.user', 'response.subject'])
            ->whereIn('survey_question_id', $textQuestionIds)
            ->whereIn('survey_response_id', $responseIds)
            ->whereNotNull('value_text')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }
}
