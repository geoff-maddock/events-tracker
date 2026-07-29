<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupJunkTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tags:cleanup {--dry-run : Report what would be deleted without changing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete junk tag rows (numeric names, URLs stored as tags, empty slugs) and report junk event types';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $junk = Tag::query()
            ->where(function ($query) {
                $query->whereRaw("name REGEXP '^[0-9]+$'")
                    ->orWhere('name', 'like', 'http://%')
                    ->orWhere('name', 'like', 'https://%')
                    ->orWhere('name', 'like', 'www.%')
                    ->orWhereRaw("name REGEXP '\\\\.(com|net|org|io|co)(/|$)'")
                    ->orWhere('slug', '');
            })
            ->orderBy('name')
            ->get();

        if ($junk->isEmpty()) {
            $this->info('No junk tags found.');
        } else {
            $this->table(
                ['id', 'name', 'slug', 'events', 'entities', 'series', 'threads'],
                $junk->map(fn (Tag $tag) => [
                    $tag->id,
                    $tag->name,
                    $tag->slug,
                    $tag->events()->count(),
                    $tag->entities()->count(),
                    $tag->series()->count(),
                    $tag->threads()->count(),
                ])
            );

            if ($dryRun) {
                $this->warn(sprintf('[dry-run] Would delete %d tags. Re-run without --dry-run to apply.', $junk->count()));
            } else {
                foreach ($junk as $tag) {
                    $tag->events()->detach();
                    $tag->entities()->detach();
                    $tag->series()->detach();
                    $tag->threads()->detach();
                    $tag->delete();
                }
                $this->info(sprintf('Deleted %d junk tags and their pivot rows.', $junk->count()));
            }
        }

        // Report-only: event types share the same garbage-in problem, but rows
        // are referenced by events.event_type_id, so deletion needs a manual
        // decision about re-pointing. Emit suggestions instead of acting.
        $junkTypes = DB::table('event_types')
            ->where(function ($query) {
                $query->whereRaw("name REGEXP '^[0-9]+$'")
                    ->orWhere('name', 'like', 'http://%')
                    ->orWhere('name', 'like', 'https://%')
                    ->orWhere('name', 'like', 'www.%')
                    ->orWhereRaw("name REGEXP '\\\\.(com|net|org|io|co)(/|$)'");
            })
            ->orderBy('name')
            ->get();

        if ($junkTypes->isEmpty()) {
            $this->info('No junk event types found.');
        } else {
            $this->warn('Junk event_types rows found (not deleted — events reference them):');
            foreach ($junkTypes as $type) {
                $count = DB::table('events')->where('event_type_id', $type->id)->count();
                $this->line(sprintf('  id=%d name="%s" (%d events)', $type->id, $type->name, $count));
                $this->line(sprintf('    -- UPDATE events SET event_type_id = <target> WHERE event_type_id = %d; DELETE FROM event_types WHERE id = %d;', $type->id, $type->id));
            }
        }

        return self::SUCCESS;
    }
}
