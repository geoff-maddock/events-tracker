<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateSeriesEvents extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'series:create-events
                            {--dry-run : List the events that would be created without creating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the next event for each active series';

    /**
     * Execute the console command.
     *
     * Not scheduled: generation is off by choice and run by hand (#2112).
     * Start with --dry-run.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting series event creation...');

        // Get all active series (not cancelled)
        $series = Series::active()
            ->whereHas('occurrenceType', function ($query) {
                $query->where('name', '!=', 'No Schedule');
            })
            ->whereDoesntHave('eventType', function ($query) {
                $query->where('name', 'Radio Show');
            })
            ->with(['entities', 'tags', 'photos', 'creator'])
            ->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($series as $s) {
            try {
                // Check if an event already exists for the next occurrence
                if ($s->nextEvent()) {
                    $this->info("Series '{$s->name}' (ID: {$s->id}) already has a future event");
                    $skipped++;
                    continue;
                }

                // Check if the series has a next occurrence date
                $nextDate = $s->nextOccurrenceDate();
                
                if (!$nextDate) {
                    $this->warn("Series '{$s->name}' (ID: {$s->id}) has no next occurrence date");
                    $skipped++;
                    continue;
                }

                $slug = $s->slug . '-' . $nextDate->format('Y-m-d');

                // events.slug isn't unique, and URLs resolve to the first match: an
                // event already using this slug is almost certainly this night,
                // entered by hand without the series link, so don't add a twin
                if (Event::where('slug', $slug)->exists()) {
                    $this->warn("Series '{$s->name}' (ID: {$s->id}): an event with slug '{$slug}' already exists");
                    $skipped++;
                    continue;
                }

                // a deleted creator leaves series.created_by null, but events need an
                // owner: the admin, as when a deleted user's events are reassigned
                $ownerId = $s->created_by ?? User::adminOwnerId();

                if (null === $ownerId) {
                    $this->warn("Series '{$s->name}' (ID: {$s->id}) has no creator and no admin is configured (app.superuser)");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would create '{$s->name}' on {$nextDate->format('Y-m-d H:i')} (slug {$slug}) for series ID {$s->id}");
                    $created++;
                    continue;
                }

                // Create the event from the series template
                $endDate = $nextDate->copy()->addHours($s->length ?? 0);

                $event = Event::create([
                    'name' => $s->name,
                    'slug' => $slug,
                    'short' => $s->short,
                    'description' => $s->description,
                    'venue_id' => $s->venue_id,
                    'series_id' => $s->id,
                    'event_type_id' => $s->event_type_id,
                    'promoter_id' => $s->promoter_id,
                    'soundcheck_at' => $s->soundcheck_at ? $nextDate->copy()->setTimeFromTimeString($s->soundcheck_at->format('H:i:s')) : null,
                    'door_at' => $s->door_at ? $nextDate->copy()->setTimeFromTimeString($s->door_at->format('H:i:s')) : null,
                    'start_at' => $nextDate,
                    'end_at' => $endDate,
                    'presale_price' => $s->presale_price,
                    'door_price' => $s->door_price,
                    'min_age' => $s->min_age,
                    'visibility_id' => $s->visibility_id,
                    'primary_link' => $s->primary_link,
                    'ticket_link' => $s->ticket_link,
                    'is_benefit' => $s->is_benefit,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);

                // Sync entities
                if ($s->entities->count() > 0) {
                    $entityIds = $s->entities->pluck('id')->toArray();
                    $event->entities()->sync($entityIds);
                }

                // Sync tags
                if ($s->tags->count() > 0) {
                    $tagIds = $s->tags->pluck('id')->toArray();
                    $event->tags()->sync($tagIds);
                }

                // Sync photos
                if ($s->photos->count() > 0) {
                    $photoIds = $s->photos->pluck('id')->toArray();
                    $event->photos()->sync($photoIds);
                }

                // Log the activity
                if ($s->creator) {
                    Activity::log($event, $s->creator, 1);
                }

                $this->info("Created event '{$event->name}' (ID: {$event->id}) for series '{$s->name}' (ID: {$s->id})");
                Log::info("Created event '{$event->name}' (ID: {$event->id}) for series '{$s->name}' (ID: {$s->id}) starting at {$nextDate}");
                
                $created++;
            } catch (\Exception $e) {
                $this->error("Failed to create event for series '{$s->name}' (ID: {$s->id}): " . $e->getMessage());
                Log::error("Failed to create event for series '{$s->name}' (ID: {$s->id}): " . $e->getMessage());
                $failed++;
            }
        }

        if ($dryRun) {
            $this->info("Dry run: would create {$created}, skip {$skipped}. Nothing was written.");

            return self::SUCCESS;
        }

        $this->info("Series event creation complete. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");
        Log::info("Series event creation complete. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");

        // a failed series is worth noticing in cron output or a manual run
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
