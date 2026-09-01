<?php

namespace App\Console\Commands;

use App\Services\TempImageStore;
use Illuminate\Console\Command;

/**
 * Removes abandoned temporary images.
 *
 * The create forms stash an image as soon as it is chosen, so every abandoned
 * create form leaves a file behind. Without this the directory grows forever.
 */
class PruneTempImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:prune-temp
                            {--hours=24 : Remove temp images older than this many hours}
                            {--dry-run : Report what would be removed without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune abandoned temporary images left by the create-form image panel';

    /**
     * Execute the console command.
     */
    public function handle(TempImageStore $tempImages): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');

        if ($hours < 1) {
            $this->error('The --hours option must be at least 1.');

            return Command::FAILURE;
        }

        $count = $tempImages->prune($hours, $dryRun);

        $this->info($dryRun
            ? "Would remove {$count} temp image(s) older than {$hours} hour(s)."
            : "Removed {$count} temp image(s) older than {$hours} hour(s).");

        return Command::SUCCESS;
    }
}
