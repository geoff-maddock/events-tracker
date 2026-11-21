<?php

namespace App\Console\Commands;

use App\Models\ClickTrack;
use Illuminate\Console\Command;

/**
 * Deletes ticket-link clicks older than the retention period (#2392).
 *
 * The owner dashboard reads its totals from the daily rollup
 * (entity_stats_daily), which is kept, and the click list covers at most the
 * last 90 days, so nothing on the site reads a click this old.
 */
class PruneClickTracks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'clicks:prune
                            {--dry-run : Report what would be removed without deleting}';

    /**
     * @var string
     */
    protected $description = 'Delete ticket-link clicks older than '.ClickTrack::RETENTION_DAYS.' days';

    public function handle(): int
    {
        $cutoff = ClickTrack::retentionCutoff();
        // one definition of what is pruned, for the dry run and the delete
        $expired = fn () => ClickTrack::where('clicked_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $this->info("Would remove {$expired()->count()} click(s) from before {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        // in batches, so the table is never locked for long
        $count = 0;
        do {
            $deleted = $expired()->limit(5000)->delete();
            $count += $deleted;
        } while ($deleted > 0);

        $this->info("Removed {$count} click(s) from before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
