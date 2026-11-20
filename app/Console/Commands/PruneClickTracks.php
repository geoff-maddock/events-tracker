<?php

namespace App\Console\Commands;

use App\Models\ClickTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

        if ($this->option('dry-run')) {
            $count = ClickTrack::where('clicked_at', '<', $cutoff)->count();
            $this->info("Would remove {$count} click(s) from before {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        // in batches, so the table is never locked for long
        $count = 0;
        do {
            $deleted = ClickTrack::where('clicked_at', '<', $cutoff)->limit(5000)->delete();
            $count += $deleted;
        } while ($deleted > 0);

        $this->info("Removed {$count} click(s) from before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
