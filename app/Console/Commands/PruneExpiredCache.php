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

            $result = $this->prune($file->getPathname(), $now, $dryRun);

            if ($result === null) {
                $kept++;
            } elseif ($result === false) {
                $failed++;
            } else {
                $removed++;
                $bytes += $result;
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
     * Delete one file if it is an expired cache entry. Returns the bytes
     * freed, null when the file is kept, false when it could not be deleted.
     *
     * The file is locked the way the file store locks it and the expiry is
     * read under that lock, so an entry (or a cache lock) that a request has
     * just rewritten is seen as live. A request that has opened the file but
     * not yet locked it can still lose its write; nothing short of leaving
     * the files alone closes that.
     */
    protected function prune(string $pathname, int $now, bool $dryRun): int|false|null
    {
        $handle = @fopen($pathname, 'r');

        // gone already: another process read it after it expired
        if ($handle === false) {
            return null;
        }

        try {
            // in use right now, so not abandoned
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                return null;
            }

            $head = (string) fread($handle, 10);

            // not a cache entry (.gitignore), or still live. The file store
            // writes the expiry as a ten digit unix timestamp at the start.
            if (strlen($head) !== 10 || !ctype_digit($head) || (int) $head > $now) {
                return null;
            }

            $size = (int) (fstat($handle)['size'] ?? 0);

            // the directories are left in place: removing one could race with
            // a request that is about to write into it
            if ($dryRun || @unlink($pathname)) {
                return $size;
            }

            // still there, so not a concurrent delete: a permissions problem
            return file_exists($pathname) ? false : null;
        } finally {
            fclose($handle);
        }
    }
}
