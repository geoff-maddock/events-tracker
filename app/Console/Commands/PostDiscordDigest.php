<?php

namespace App\Console\Commands;

use App\Exceptions\DiscordWebhookException;
use App\Models\DiscordTarget;
use App\Services\Integrations\Discord\DiscordEventPoster;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Posts the weekly roundup (issue #2058).
 *
 * Scheduled HOURLY, but each run serves only the targets whose configured
 * digest day and hour match right now. That gives every channel its own
 * schedule without a cron entry per channel, and without a scheduler that has
 * to be edited whenever someone adds a target.
 *
 * Unlike the reminder command this posts inline rather than queueing: a digest
 * is one message per target per week, the volume is trivial, and doing it here
 * keeps the roundup's event list consistent with the moment it was built.
 */
class PostDiscordDigest extends Command
{
    protected $signature = 'discord:digest
                            {--dry-run : Show what would be sent without sending it}
                            {--target= : Restrict to a single Discord target id}
                            {--force : Ignore the configured day and hour}';

    protected $description = 'Post the weekly Discord digest to any target scheduled for this hour';

    public function handle(DiscordTargetMatcher $matcher, DiscordEventPoster $poster): int
    {
        if (! config('discord.enabled')) {
            $this->info('Discord posting is disabled (DISCORD_ENABLED). Nothing to do.');
            Log::info('Discord digest: skipped, integration disabled (DISCORD_ENABLED).');

            return Command::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $now = now();

        $query = DiscordTarget::forMode(DiscordTarget::MODE_DIGEST)->with('criteria');

        if ($targetId = $this->option('target')) {
            $query->whereKey($targetId);
        }

        if (! $force) {
            $query->where('digest_day', $now->dayOfWeek)->where('digest_hour', $now->hour);
        }

        // ISO year-week. A second run in the same week is a no-op, which is
        // what makes the hourly schedule safe.
        $weekKey = $now->format('o-\WW');
        $sent = 0;
        $targets = $query->get();

        // 23 runs out of 24 match nothing, so the routine "no target is due"
        // case is debug — but it still has to be written somewhere, because
        // "did the scheduler even fire?" is otherwise unanswerable after the
        // fact: cron discards the command's stdout.
        Log::log($targets->isEmpty() ? 'debug' : 'info', 'Discord digest: run started', [
            'week' => $weekKey,
            'hour' => $now->hour,
            'day_of_week' => $now->dayOfWeek,
            'targets_due' => $targets->count(),
            'dry_run' => $dryRun,
            'forced' => $force,
        ]);

        foreach ($targets as $target) {
            $from = $now->copy();
            $to = $now->copy()->addDays($target->digest_window_days);

            $events = $matcher->eventsFor($target, $from, $to)
                ->with(['venue'])
                ->limit((int) config('discord.digest.max_events', 25))
                ->get();

            $this->line(sprintf(
                '  %s → %d event(s) through %s%s',
                $target->name,
                $events->count(),
                $to->format('M j'),
                $dryRun ? ' [dry run]' : ''
            ));

            if ($dryRun) {
                continue;
            }

            try {
                $post = $poster->postDigest($target, $events, $weekKey, $from, $to);

                if ($post->isSent()) {
                    $sent++;
                }

                // The ledger status is the answer to "why was the channel
                // quiet this week" — 'skipped' on an empty window reads very
                // differently from a digest that was already sent.
                Log::info('Discord digest: target processed', [
                    'target_id' => $target->id,
                    'target' => $target->name,
                    'week' => $weekKey,
                    'events' => $events->count(),
                    'status' => $post->status,
                ]);
            } catch (DiscordWebhookException $e) {
                // Recorded and logged by the poster; one bad channel must not
                // stop the rest of the week's digests.
                $this->error('  '.$target->name.': '.$e->getMessage());
            }
        }

        $this->info($sent.' digest(s) posted for '.$weekKey.'.');

        if (! $targets->isEmpty()) {
            Log::info('Discord digest: run finished', [
                'week' => $weekKey,
                'targets_due' => $targets->count(),
                'sent' => $sent,
                'dry_run' => $dryRun,
            ]);
        }

        return Command::SUCCESS;
    }
}
