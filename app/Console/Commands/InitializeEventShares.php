<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeEventShares extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'instagram:initialize-shares 
                            {--platform=instagram : The platform to initialize shares for}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize event_shares for existing future events to mark them as already shared';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $platform = $this->option('platform');
        $dryRun = $this->option('dry-run');
        
        $this->info('Initializing event shares...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $today = Carbon::today();

        // Get all public future events
        $events = Event::where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->where('start_at', '>=', $today)
            ->whereHas('eventType')
            ->orderBy('start_at', 'ASC')
            ->get();

        if ($events->isEmpty()) {
            $this->info('No future public events found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$events->count()} future public event(s).");

        $initializedCount = 0;
        $skippedCount = 0;
        $alreadySharedCount = 0;

        foreach ($events as $event) {
            // Check if this event already has shares on this platform
            $existingShares = EventShare::where('event_id', $event->id)
                ->where('platform', $platform)
                ->where('posted_at', '!=', null)
                ->count();

            if ($existingShares > 0) {
                $alreadySharedCount++;
                $this->line("  ⊘ Event #{$event->id} '{$event->name}' already has {$existingShares} share(s) - skipping");
                continue;
            }

            // Check if event has a primary photo (required for Instagram)
            if (!$event->getPrimaryPhoto()) {
                $skippedCount++;
                $this->line("  ⊘ Event #{$event->id} '{$event->name}' has no primary photo - skipping");
                continue;
            }

            if (!$dryRun) {
                // Create a share record with posted_at set to indicate it was already shared
                // Use the event's creation date or today, whichever is earlier
                $postedAt = $event->created_at->lt($today) ? $event->created_at : $today;
                
                EventShare::create([
                    'event_id' => $event->id,
                    'platform' => $platform,
                    'platform_id' => 'initialized', // Mark as initialized, not a real platform ID
                    'created_by' => null, // System-initialized
                    'posted_at' => $postedAt,
                ]);
            }

            $initializedCount++;
            $eventDate = $event->start_at->format('M j, Y');
            $this->info("  ✓ Event #{$event->id} '{$event->name}' ({$eventDate})");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Initialized: {$initializedCount}");
        $this->info("  Already shared: {$alreadySharedCount}");
        $this->info("  Skipped (no photo): {$skippedCount}");
        
        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN MODE - Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
