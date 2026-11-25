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

    public function test_find_export_path_ignores_a_copy_left_in_public_storage(): void
    {
        // exports used to be copied here and served without a signature
        $filename = 'cleanup-test-find-' . uniqid() . '.zip';
        $path = $this->publicExportDir . '/' . $filename;
        file_put_contents($path, 'present');

        $this->assertNull((new DataExportService())->findExportPath($filename));

        @unlink($path);
    }

    public function test_find_export_path_takes_a_file_name_only(): void
    {
        $this->assertNull((new DataExportService())->findExportPath('../../.env'));
        $this->assertNull((new DataExportService())->findExportPath('tmp/anything.json'));
    }

    public function test_the_download_link_is_always_the_signed_route(): void
    {
        $filename = 'cleanup-test-' . uniqid() . '.zip';
        $path = $this->publicExportDir . '/' . $filename;
        file_put_contents($path, 'content');

        foreach ([$filename, 'absent-' . uniqid() . '.zip'] as $name) {
            $url = (new DataExportService())->getDownloadUrl($name);

            $this->assertStringNotContainsString('/storage/exports/', $url);
            $this->assertStringContainsString('/exports/download/' . $name, $url);
            $this->assertStringContainsString('signature=', $url);
            $this->assertStringContainsString('expires=', $url);
        }

        @unlink($path);
    }
}
