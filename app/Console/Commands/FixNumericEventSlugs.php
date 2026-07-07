<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class FixNumericEventSlugs extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'events:fix-numeric-slugs
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepend a dash to event slugs that start with a digit so routes resolve by slug instead of id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Fixing event slugs that start with a digit...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $events = Event::whereRaw("slug REGEXP '^[0-9]'")->orderBy('id')->get();

        if ($events->isEmpty()) {
            $this->info('No event slugs start with a digit.');

            return Command::SUCCESS;
        }

        $this->info("Found {$events->count()} event(s) with a digit-leading slug.");

        $fixedCount = 0;
        $collisionCount = 0;
        $skippedCount = 0;

        foreach ($events as $event) {
            $newSlug = '-'.$event->slug;

            // no unique index on events.slug, so guard against collisions here
            if (Event::where('slug', $newSlug)->where('id', '!=', $event->id)->exists()) {
                $newSlug = '-'.$event->slug.'-'.$event->id;
                if (Event::where('slug', $newSlug)->where('id', '!=', $event->id)->exists()) {
                    $skippedCount++;
                    $this->warn("  ⊘ Event #{$event->id}: '{$event->slug}' - both '-{$event->slug}' and '{$newSlug}' already exist - skipping");
                    continue;
                }
                $collisionCount++;
                $this->warn("  ! Event #{$event->id}: '-{$event->slug}' already taken, using '{$newSlug}'");
            } else {
                $fixedCount++;
            }

            if ($dryRun) {
                $this->line("  Would rename event #{$event->id}: '{$event->slug}' -> '{$newSlug}'");
                continue;
            }

            $event->slug = $newSlug;
            $event->save();
            $this->info("  ✓ Event #{$event->id}: '{$newSlug}'");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Fixed: {$fixedCount}");
        $this->info("  Renamed with id suffix (collision): {$collisionCount}");
        $this->info("  Skipped: {$skippedCount}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN MODE - Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
