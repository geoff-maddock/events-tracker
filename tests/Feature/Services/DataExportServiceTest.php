<?php

namespace Tests\Feature\Services;

use App\Services\DataExportService;
use Tests\TestCase;

class DataExportServiceTest extends TestCase
{
    private string $exportDir;
    private string $publicExportDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportDir = storage_path('app/exports');
        $this->publicExportDir = storage_path('app/public/exports');

        @mkdir($this->exportDir, 0755, true);
        @mkdir($this->publicExportDir, 0755, true);

        // Clear any pre-existing test exports so assertions are deterministic.
        foreach (array_filter(glob($this->exportDir . '/cleanup-test-*') ?: []) as $f) {
            @unlink($f);
        }
        foreach (array_filter(glob($this->publicExportDir . '/cleanup-test-*') ?: []) as $f) {
            @unlink($f);
        }
    }

    public function test_cleanup_old_exports_deletes_files_older_than_threshold(): void
    {
        $oldFile = $this->exportDir . '/cleanup-test-old.zip';
        $newFile = $this->exportDir . '/cleanup-test-new.zip';

        file_put_contents($oldFile, 'old');
        file_put_contents($newFile, 'new');

        // Make the "old" file 30 days old; the "new" file remains fresh.
        touch($oldFile, time() - 60 * 60 * 24 * 30);

        (new DataExportService())->cleanupOldExports(7);

        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($newFile);

        @unlink($newFile);
    }

    public function test_cleanup_also_traverses_public_exports_directory(): void
    {
        $oldPublic = $this->publicExportDir . '/cleanup-test-old-public.zip';
        file_put_contents($oldPublic, 'old');
        touch($oldPublic, time() - 60 * 60 * 24 * 30);

        (new DataExportService())->cleanupOldExports(7);

        $this->assertFileDoesNotExist($oldPublic);
    }

    public function test_find_export_path_returns_null_when_missing(): void
    {
        $this->assertNull((new DataExportService())->findExportPath('does-not-exist-zz.zip'));
    }

    public function test_find_export_path_locates_file_in_public_exports(): void
    {
        $filename = 'cleanup-test-find-' . uniqid() . '.zip';
        $path = $this->publicExportDir . '/' . $filename;
        file_put_contents($path, 'present');

        $resolved = (new DataExportService())->findExportPath($filename);

        $this->assertSame($path, $resolved);

        @unlink($path);
    }

    public function test_get_download_url_returns_storage_url_when_public_file_exists(): void
    {
        $filename = 'cleanup-test-' . uniqid() . '.zip';
        $path = $this->publicExportDir . '/' . $filename;
        file_put_contents($path, 'content');

        $url = (new DataExportService())->getDownloadUrl($filename);

        $this->assertStringContainsString('/storage/exports/' . $filename, $url);

        @unlink($path);
    }

    public function test_get_download_url_returns_signed_route_when_file_is_missing(): void
    {
        $filename = 'absent-' . uniqid() . '.zip';

        $url = (new DataExportService())->getDownloadUrl($filename);

        // A missing file should not be served from /storage/exports/.
        $this->assertStringNotContainsString('/storage/exports/' . $filename, $url);
        // The fallback is a temporarySignedRoute, which always includes a signature.
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString($filename, $url);
    }
}
