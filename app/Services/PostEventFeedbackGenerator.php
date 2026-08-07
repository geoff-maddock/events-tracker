<?php

namespace App\Services;

use App\Models\EventStatus;
use App\Models\ResponseType;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\Visibility;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds post-event feedback invitations for users who marked "Attending" on an
 * event that has since finished (issue #1998).
 *
 * Lives in a service rather than the command so the query is testable on its
 * own and the command stays about flags, output and error handling.
 */
class PostEventFeedbackGenerator
{
    /**
     * How many candidate rows to process per chunk.
     */
    private const DEFAULT_CHUNK_SIZE = 500;

    /**
     * Find eligible (user, event) pairs and create invitations for them.
     *
     * @param  array{dry_run?: bool, lookback_days?: int|null, event?: int|string|null, limit?: int|null}  $options
     * @return array{created: int, candidates: int, skipped_volume: int}
     */
    public function generate(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $limit = $options['limit'] ?? null;

        $campaign = SurveyCampaign::bySlug(SurveyCampaign::POST_EVENT_ATTENDEE);

        if (! $campaign || ! $campaign->is_active) {
            return ['created' => 0, 'candidates' => 0, 'skipped_volume' => 0];
        }

        $lookbackDays = $options['lookback_days'] ?? (int) config('feedback.post_event.lookback_days', 14);
        $delayHours = (int) config('feedback.post_event.delay_hours', 12);
        $expireDays = $campaign->expiresAfterDays() ?? (int) config('feedback.post_event.expire_days', 30);

        $windowEnd = now()->subHours($delayHours);
        $windowStart = now()->subDays($lookbackDays);

        $created = 0;
        $candidates = 0;
        $skippedVolume = 0;
        $touchedUsers = [];

        // Counts what this run has already granted each user. A real run sees
        // its own writes through the DB query below; a dry run writes nothing,
        // so without this it would report every candidate as creatable and
        // wildly overstate what the real run does.
        $grantedThisRun = [];

        $chunkSize = (int) config('feedback.generation_chunk_size', self::DEFAULT_CHUNK_SIZE);

        // chunkById, not chunk: this query filters on `survey_invitations.id is
        // null`, and a real run inserts exactly those rows. Under OFFSET-based
        // chunk() the result set shrinks beneath us between pages and roughly a
        // full page of eligible candidates gets skipped every time. Keyset
        // pagination on event_responses.id is stable under that mutation.
        $this->candidateQuery($campaign->id, $windowStart, $windowEnd, $options['event'] ?? null)
            ->chunkById($chunkSize, function ($rows) use (
                $campaign, $expireDays, $dryRun, $limit,
                &$created, &$candidates, &$skippedVolume, &$touchedUsers, &$grantedThisRun
            ) {
                foreach ($rows as $row) {
                    if ($limit !== null && $created >= $limit) {
                        return false;
                    }

                    $userId = (int) $row->user_id;
                    $candidates++;

                    $alreadyGranted = $dryRun ? ($grantedThisRun[$userId] ?? 0) : 0;

                    if (! $this->withinVolumeLimits($userId, $alreadyGranted)) {
                        $skippedVolume++;

                        continue;
                    }

                    if (! $dryRun) {
                        SurveyInvitation::firstOrCreate(
                            [
                                'survey_campaign_id' => $campaign->id,
                                'user_id' => $row->user_id,
                                'subject_type' => 'event',
                                'subject_id' => $row->event_id,
                            ],
                            [
                                'token' => SurveyInvitation::generateToken(),
                                'status' => SurveyInvitation::STATUS_PENDING,
                                'source' => SurveyInvitation::SOURCE_APP,
                                'expires_at' => now()->addDays($expireDays),
                            ]
                        );

                        $touchedUsers[$userId] = true;
                    }

                    $grantedThisRun[$userId] = ($grantedThisRun[$userId] ?? 0) + 1;
                    $created++;
                }

                return true;
            }, 'event_responses.id', 'candidate_id');

        foreach (array_keys($touchedUsers) as $userId) {
            SurveyInvitation::forgetPendingCache($userId);
        }

        return [
            'created' => $created,
            'candidates' => $candidates,
            'skipped_volume' => $skippedVolume,
        ];
    }

    /**
     * Attendees of events that finished inside the window and who don't already
     * have an invitation for that event.
     *
     * @param  int|string|null  $eventFilter  event id or slug, for --single-event
     * @return \Illuminate\Database\Query\Builder
     */
    public function candidateQuery(int $campaignId, Carbon $windowStart, Carbon $windowEnd, $eventFilter = null)
    {
        // Events have no dedicated "ended at"; end_at is nullable and falls back
        // to start_at for single-point events.
        $eventEnd = 'COALESCE(events.end_at, events.start_at)';

        $query = DB::table('event_responses')
            ->join('events', 'events.id', '=', 'event_responses.event_id')
            ->join('users', 'users.id', '=', 'event_responses.user_id')
            ->join('user_statuses', 'user_statuses.id', '=', 'users.user_status_id')
            // Inner-value check on this left join deliberately excludes users
            // with no profile row (a real legacy condition, see UsersController)
            // — matching the app-wide `$user?->profile?->setting_x !== 1` guard.
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('survey_invitations', function ($join) use ($campaignId) {
                $join->on('survey_invitations.user_id', '=', 'event_responses.user_id')
                    ->on('survey_invitations.subject_id', '=', 'event_responses.event_id')
                    ->where('survey_invitations.subject_type', '=', 'event')
                    ->where('survey_invitations.survey_campaign_id', '=', $campaignId);
            })
            ->whereNull('survey_invitations.id')
            ->where('event_responses.response_type_id', ResponseType::ATTENDING)
            // Indexable pre-filter. COALESCE() alone cannot use the start_at
            // index, and without this the join full-scans event_responses.
            // The extra day on the lower bound covers multi-day events whose
            // end_at trails start_at.
            ->whereBetween('events.start_at', [$windowStart->copy()->subDay(), $windowEnd])
            ->whereRaw("$eventEnd >= ?", [$windowStart])
            ->whereRaw("$eventEnd <= ?", [$windowEnd])
            ->where('events.visibility_id', '!=', Visibility::VISIBILITY_CANCELLED)
            ->where(function ($q) {
                $q->whereNull('events.event_status_id')
                    ->orWhere('events.event_status_id', '!=', EventStatus::CANCELLED);
            })
            ->where('user_statuses.can_login', 1)
            ->whereNotNull('users.email_verified_at')
            ->where('profiles.setting_feedback_requests', 1)
            // candidate_id is the keyset cursor for chunkById; no orderBy here,
            // chunkById supplies its own.
            ->select(
                'event_responses.id as candidate_id',
                'event_responses.user_id',
                'event_responses.event_id'
            );

        if ($eventFilter !== null) {
            $query->where(function ($q) use ($eventFilter) {
                $q->where('events.slug', $eventFilter);

                if (is_numeric($eventFilter)) {
                    $q->orWhere('events.id', (int) $eventFilter);
                }
            });
        }

        return $query;
    }

    /**
     * Whether this user is under the per-user prompt volume caps.
     *
     * Without these a heavy attendee gets a prompt for every event they went to
     * over a busy weekend.
     */
    private function withinVolumeLimits(int $userId, int $alreadyGrantedThisRun = 0): bool
    {
        $maxPending = (int) config('feedback.max_pending_per_user', 1);

        if ($maxPending > 0) {
            $pending = SurveyInvitation::where('user_id', $userId)->actionable()->count()
                + $alreadyGrantedThisRun;

            if ($pending >= $maxPending) {
                return false;
            }
        }

        $minDays = (int) config('feedback.min_days_between_prompts', 7);

        if ($minDays > 0) {
            $recent = SurveyInvitation::where('user_id', $userId)
                ->where(function ($q) use ($minDays) {
                    $q->where('completed_at', '>', now()->subDays($minDays))
                        ->orWhere('dismissed_at', '>', now()->subDays($minDays));
                })
                ->exists();

            if ($recent) {
                return false;
            }
        }

        return true;
    }

    /**
     * Flip open invitations whose expiry has passed.
     */
    public function sweepExpired(): int
    {
        return SurveyInvitation::whereIn('status', SurveyInvitation::OPEN_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => SurveyInvitation::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);
    }

    /**
     * Delete invitations pointing at events that no longer exist.
     *
     * Nothing in this app soft-deletes and there is no FK on the polymorphic
     * subject columns, so a hard-deleted event leaves invitations dangling.
     */
    public function cleanupDanglingEventSubjects(): int
    {
        return SurveyInvitation::where('subject_type', 'event')
            ->whereNotIn('subject_id', DB::table('events')->select('id'))
            ->delete();
    }
}
