<?php

namespace App\Console\Commands;

use App\Services\PostEventFeedbackGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Creates the day's feedback survey invitations and keeps the invitation table
 * tidy (issue #1998).
 *
 * Scheduled daily in App\Console\Kernel. Safe to re-run: invitations are
 * deduped by a unique index on (campaign, user, subject).
 */
class GenerateFeedbackInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feedback:generate
                            {--dry-run : Report what would be created without writing anything}
                            {--campaign= : Restrict to one campaign slug (default: every active campaign)}
                            {--single-event= : Only consider this event slug or numeric ID}
                            {--lookback-days= : Override the configured lookback window}
                            {--limit= : Stop after creating this many invitations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate proactive feedback survey invitations, expire stale ones, and clean up invitations whose subject was deleted.';

    public function handle(PostEventFeedbackGenerator $generator): int
    {
        if (! config('feedback.enabled')) {
            $this->warn('Feedback feature is disabled (config feedback.enabled). Nothing to do.');

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $campaignFilter = $this->option('campaign');
        $singleEvent = $this->option('single-event');
        $lookbackDays = $this->option('lookback-days');
        $limit = $this->option('limit');

        if ($isDryRun) {
            $this->warn('DRY RUN — no invitations will be written.');
        }

        if ($singleEvent) {
            $this->warn("SINGLE EVENT MODE — only considering event: {$singleEvent}");
        }

        $created = 0;

        // Post-event attendee campaign.
        if ($this->shouldRunPostEvent($campaignFilter)) {
            try {
                $result = $generator->generate([
                    'dry_run' => $isDryRun,
                    'lookback_days' => $lookbackDays !== null ? (int) $lookbackDays : null,
                    'event' => $singleEvent,
                    'limit' => $limit !== null ? (int) $limit : null,
                ]);

                $created += $result['created'];

                $this->info(sprintf(
                    'Post-event attendee: %d candidate(s), %d invitation(s) %s, %d skipped by volume caps.',
                    $result['candidates'],
                    $result['created'],
                    $isDryRun ? 'would be created' : 'created',
                    $result['skipped_volume']
                ));
            } catch (Throwable $e) {
                $this->error('Post-event attendee generation failed: '.$e->getMessage());
                Log::error('feedback:generate post-event generation failed', ['exception' => $e]);

                return self::FAILURE;
            }
        } else {
            $this->line('Post-event attendee campaign skipped.');
        }

        // Housekeeping runs regardless of which campaign was targeted, but never
        // during a dry run.
        if (! $isDryRun) {
            $expired = $generator->sweepExpired();
            $this->info("Expired {$expired} stale invitation(s).");

            $dangling = $generator->cleanupDanglingEventSubjects();

            if ($dangling > 0) {
                $this->warn("Removed {$dangling} invitation(s) whose event no longer exists.");
            }

            if ($created > 0) {
                Log::info("feedback:generate created {$created} invitation(s).");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Whether the post-event campaign should run given --campaign and config.
     */
    private function shouldRunPostEvent(?string $campaignFilter): bool
    {
        if (! config('feedback.post_event.enabled', true)) {
            return false;
        }

        return $campaignFilter === null
            || $campaignFilter === \App\Models\SurveyCampaign::POST_EVENT_ATTENDEE;
    }
}
