<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Deletes expired entries from the file cache (#2392).
 *
 * The file store only deletes an entry when something reads it after it has
 * expired. Keys that are never read again (the page fragments are keyed on a
 * content fingerprint, so every edit abandons the old ones) stay on disk for
 * good.
 */
class PruneExpiredCache extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cache:prune-expired
                            {--dry-run : Report what would be removed without deleting}';

    /**
     * @var string
     */
    protected $description = 'Delete expired entries from the file cache';

    public function handle(): int
    {
        $path = config('cache.stores.file.path');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_string($path) || !is_dir($path)) {
            $this->info('No file cache directory, nothing to prune.');

            return self::SUCCESS;
        }

        $now = Carbon::now()->getTimestamp();
        $kept = $removed = $bytes = $failed = 0;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $expiresAt = $this->expiresAt($file->getPathname());

            // not a cache entry (.gitignore, a lock being written), or still live
            if ($expiresAt === null || $expiresAt > $now) {
                $kept++;

                continue;
            }

            $size = (int) $file->getSize();

            // the directories are left in place: removing one could race with
            // a request that is about to write into it
            if ($dryRun || @unlink($file->getPathname())) {
                $removed++;
                $bytes += $size;
            } elseif (file_exists($file->getPathname())) {
                // still there, so it wasn't a concurrent delete: a permissions problem
                $failed++;
            }
        }

        $this->info(sprintf(
            '%s %d expired cache file(s), %.1f MB; %d kept.',
            $dryRun ? 'Would remove' : 'Removed',
            $removed,
            $bytes / 1048576,
            $kept
        ));

        if ($failed > 0) {
            $this->error("Could not delete {$failed} expired file(s): check who owns the cache directories.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The file store writes the expiry as a ten digit unix timestamp at the
     * start of the file. Null when the file doesn't start with one.
     */
    protected function expiresAt(string $pathname): ?int
    {
        $head = @file_get_contents($pathname, false, null, 0, 10);

        if ($head === false || strlen($head) !== 10 || !ctype_digit($head)) {
            return null;
        }

        return (int) $head;
    }
}
