<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

/**
 * One-time (idempotent) backfill that stamps a long-lived Cache-Control header on
 * existing objects in the DigitalOcean Spaces bucket. New uploads already get the
 * header via the 'options' on the 'external' disk (config/filesystems.php); this
 * fixes the historical objects that were stored with no Cache-Control (Lighthouse:
 * "Use efficient cache lifetimes").
 *
 * Mechanism: an S3 server-side self-copy with MetadataDirective=REPLACE. That copy
 * does NOT preserve the existing Content-Type or ACL, so we explicitly carry the
 * current Content-Type forward (via HeadObject) and re-apply the public-read ACL —
 * otherwise images would either download instead of display, or start returning 403.
 *
 * Defaults to a dry run; pass --execute to actually mutate objects.
 */
class BackfillSpacesCacheControl extends Command
{
    protected $signature = 'spaces:backfill-cache-control
        {--prefix=photos/ : Key prefix under the disk root to process}
        {--limit=0 : Max objects to process (0 = no limit)}
        {--execute : Apply changes (otherwise dry run)}';

    protected $description = 'Stamp Cache-Control on existing DigitalOcean Spaces objects (idempotent)';

    public function handle(): int
    {
        $disk = config('filesystems.disks.external');
        $cacheControl = $disk['options']['CacheControl'] ?? 'public, max-age=31536000, immutable';
        $root = trim((string) ($disk['root'] ?? ''), '/');
        $prefix = ltrim((string) $this->option('prefix'), '/');
        $fullPrefix = trim($root.'/'.$prefix, '/'); // e.g. prod/photos/
        $limit = (int) $this->option('limit');
        $execute = (bool) $this->option('execute');

        if (empty($disk['key']) || empty($disk['secret'])) {
            $this->error('Spaces credentials are not configured for the external disk.');

            return Command::FAILURE;
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => $disk['region'],
            'endpoint' => $disk['endpoint'],
            'use_path_style_endpoint' => false,
            'credentials' => [
                'key' => $disk['key'],
                'secret' => $disk['secret'],
            ],
        ]);
        $bucket = $disk['bucket'];

        $this->info(($execute ? 'EXECUTING' : 'DRY RUN').' — bucket='.$bucket.' prefix='.$fullPrefix);
        $this->line('Target Cache-Control: '.$cacheControl);

        $scanned = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $paginator = $client->getPaginator('ListObjectsV2', [
            'Bucket' => $bucket,
            'Prefix' => $fullPrefix,
        ]);

        foreach ($paginator as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                if ($limit > 0 && $scanned >= $limit) {
                    break 2;
                }
                $key = $object['Key'];

                // Skip "directory" placeholder objects (zero-byte keys ending in '/').
                if (str_ends_with($key, '/')) {
                    continue;
                }
                $scanned++;

                try {
                    $head = $client->headObject(['Bucket' => $bucket, 'Key' => $key]);

                    // Idempotency: skip if already stamped with the target value.
                    if (trim((string) ($head['CacheControl'] ?? '')) === $cacheControl) {
                        $skipped++;
                        continue;
                    }

                    $contentType = $head['ContentType'] ?? $this->guessContentType($key);

                    if (! $execute) {
                        $this->line(sprintf('would update: %s (type=%s, current cc=%s)',
                            $key, $contentType, $head['CacheControl'] ?? '<none>'));
                        $updated++;
                        continue;
                    }

                    $client->copyObject([
                        'Bucket' => $bucket,
                        'Key' => $key,
                        'CopySource' => $bucket.'/'.rawurlencode($key),
                        'MetadataDirective' => 'REPLACE',
                        'CacheControl' => $cacheControl,
                        'ContentType' => $contentType,
                        'ACL' => 'public-read',
                    ]);
                    $updated++;

                    if ($updated % 100 === 0) {
                        $this->info("...updated {$updated} so far");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn('failed: '.$key.' — '.$e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info(sprintf('Done. scanned=%d %s=%d skipped(already set)=%d failed=%d',
            $scanned, $execute ? 'updated' : 'would-update', $updated, $skipped, $failed));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function guessContentType(string $key): string
    {
        return match (strtolower(pathinfo($key, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            default => 'application/octet-stream',
        };
    }
}
