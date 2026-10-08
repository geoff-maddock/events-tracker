<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\ImageHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Backfills the hero-size variant for photos uploaded before it existed
 * (#1932), and optionally rebuilds heavy legacy thumbnails.
 *
 * Only primary photos: those are the entity and event heroes, and the
 * thumbnails event cards show. Newest first, a batch at a time; a photo is
 * done once photos.large is set, so re-running picks up where it left off.
 * A photo that fails (e.g. its file is missing from the disk) is logged and
 * skipped; it stays unset and is retried on the next run.
 */
class GeneratePhotoVariants extends Command
{
    protected $signature = 'photos:generate-variants
                            {--limit=200 : How many photos to process in this run}
                            {--thumbnails : Also rebuild each thumbnail, keeping it only if it is at least 10% smaller}
                            {--dry-run : Report what would be done without writing anything}';

    protected $description = 'Generate the 1200px hero variant for primary photos that lack it, and optionally rebuild heavy legacy thumbnails (#1932).';

    /** A rebuilt thumbnail replaces the old one only when it saves at least this share. */
    private const THUMBNAIL_MIN_SAVING = 0.10;

    public function handle(ImageHandler $images, ImageManager $manager): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $thumbnails = (bool) $this->option('thumbnails');
        $limit = max(1, (int) $this->option('limit'));
        $disk = Storage::disk('external');

        $remaining = Photo::query()->where('is_primary', 1)->whereNull('large')->count();
        $photos = Photo::query()->where('is_primary', 1)->whereNull('large')->orderByDesc('id')->limit($limit)->get();

        $this->info(($dryRun ? 'DRY RUN: ' : '')."{$remaining} primary photo(s) without a hero variant; processing {$photos->count()}.");

        $counts = ['variant' => 0, 'small' => 0, 'thumbnails' => 0, 'failed' => 0];
        $bytesSaved = 0;

        foreach ($photos as $photo) {
            /** @var Photo $photo */
            try {
                $source = $disk->get($photo->path);
                if ($source === null) {
                    throw new \RuntimeException("{$photo->path} is missing from the disk");
                }

                if ($thumbnails) {
                    $bytesSaved += $this->rebuildThumbnail($photo, $source, $manager, $dryRun, $counts);
                }

                $image = $manager->read($source);
                $needsVariant = max($image->width(), $image->height()) > ImageHandler::LARGE_DIMENSION;
                $counts[$needsVariant ? 'variant' : 'small']++;

                if ($dryRun) {
                    continue;
                }

                $images->writeLarge($photo, $image);
                $photo->save();
            } catch (\Throwable $e) {
                $counts['failed']++;
                $this->warn("  FAILED photo {$photo->id}: {$e->getMessage()}");
                Log::warning('photos:generate-variants: skipped a photo', ['photo_id' => $photo->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info(sprintf(
            '%s%d hero variant(s), %d already small enough, %d thumbnail(s) rebuilt (%d KiB saved), %d failed.',
            $dryRun ? 'DRY RUN: would write ' : 'Wrote ',
            $counts['variant'],
            $counts['small'],
            $counts['thumbnails'],
            intdiv($bytesSaved, 1024),
            $counts['failed'],
        ));

        return Command::SUCCESS;
    }

    /**
     * Rebuild the square thumbnail from the main image the way makePhoto()
     * does today, and keep it only if it is meaningfully smaller: thumbnails
     * from before mid-2026 come out 30–50% smaller, newer ones don't change.
     *
     * @param array<string, int> $counts
     *
     * @return int bytes saved
     */
    private function rebuildThumbnail(Photo $photo, string $source, ImageManager $manager, bool $dryRun, array &$counts): int
    {
        $disk = Storage::disk('external');
        // only WebP thumbnails: rewriting a legacy .jpg thumbnail as WebP bytes would
        // leave the file's extension lying about its format
        if (!$photo->thumbnail || $photo->thumbnail === $photo->path
            || !str_ends_with(strtolower($photo->thumbnail), '.webp') || !$disk->exists($photo->thumbnail)) {
            return 0;
        }

        $current = $disk->size($photo->thumbnail);
        $rebuilt = (string) $manager->read($source)
            ->cover(ImageHandler::THUMBNAIL_SIZE, ImageHandler::THUMBNAIL_SIZE)
            ->toWebp(ImageHandler::WEBP_QUALITY);

        if (strlen($rebuilt) > $current * (1 - self::THUMBNAIL_MIN_SAVING)) {
            return 0;
        }

        $counts['thumbnails']++;
        if (!$dryRun) {
            $disk->put($photo->thumbnail, $rebuilt, 'public');
        }

        return $current - strlen($rebuilt);
    }
}
