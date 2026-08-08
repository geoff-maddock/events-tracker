<?php

namespace App\Console\Commands;

use App\Jobs\Discord\PostEventToDiscord;
use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Queues "the show is soon" reminders (issue #2058).
 *
 * Catches events created months in advance, whose announcement scrolled out of
 * the channel long before anyone could act on it.
 *
 * The command only dispatches jobs; it never posts inline. That is the
 * deliberate difference from AutomateInstagramPosts, which reimplements its
 * poster's logic inside the command and runs it in the scheduler process.
 */
class PostDiscordReminders extends Command
{
    protected $signature = 'discord:reminders
                            {--dry-run : List what would be sent without queueing anything}
                            {--target= : Restrict to a single Discord target id}';

    protected $description = 'Queue Discord reminder posts for events starting soon';

    public function handle(DiscordTargetMatcher $matcher): int
    {
        if (! config('discord.enabled')) {
            $this->info('Discord posting is disabled (DISCORD_ENABLED). Nothing to do.');

            return Command::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $targets = DiscordTarget::forMode(DiscordTarget::MODE_REMINDER)->with('criteria');

        if ($targetId = $this->option('target')) {
            $targets->whereKey($targetId);
        }

        $queued = 0;

        foreach ($targets->get() as $target) {
            foreach ($target->effectiveReminderOffsets() as $offset) {
                $queued += $this->remindersFor($matcher, $target, $offset, $dryRun);
            }
        }

        $this->info(($dryRun ? 'Would queue ' : 'Queued ').$queued.' Discord reminder(s).');

        return Command::SUCCESS;
    }

    /**
     * One target, one offset.
     */
    private function remindersFor(
        DiscordTargetMatcher $matcher,
        DiscordTarget $target,
        int $offset,
        bool $dryRun,
    ): int {
        // A whole-day window against a once-daily run: an event cannot fall
        // between two runs, and cannot be caught by two.
        $from = now()->addDays($offset)->startOfDay();
        $to = now()->addDays($offset)->endOfDay();

        $events = $matcher->eventsFor($target, $from, $to)->get();
        $queued = 0;

        foreach ($events as $event) {
            if ($this->postedTooRecently($target, $event)) {
                continue;
            }

            $this->line(sprintf(
                '  %s → %s (T-%dd)%s',
                $target->name,
                $event->name,
                $offset,
                $dryRun ? ' [dry run]' : ''
            ));

            if (! $dryRun) {
                // The ledger's unique index on (target, event, 'reminder', offset)
                // makes a re-run within the same day a no-op.
                PostEventToDiscord::dispatch(
                    $event->id,
                    DiscordPost::REASON_REMINDER,
                    [$target->id],
                    (string) $offset,
                );
            }

            $queued++;
        }

        return $queued;
    }

    /**
     * Suppress a reminder that would land on top of a recent post for the same
     * event — an event added at T-8d would otherwise be announced, then
     * reminded about the following day.
     */
    private function postedTooRecently(DiscordTarget $target, Event $event): bool
    {
        $gapHours = (int) config('discord.reminder_min_gap_hours', 36);

        if ($gapHours <= 0) {
            return false;
        }

        $recent = DiscordPost::where('discord_target_id', $target->id)
            ->where('event_id', $event->id)
            ->sent()
            ->where('posted_at', '>=', now()->subHours($gapHours))
            ->exists();

        if ($recent) {
            Log::info('Discord: reminder suppressed, posted recently', [
                'target_id' => $target->id,
                'event_id' => $event->id,
            ]);
        }

        return $recent;
    }
}
