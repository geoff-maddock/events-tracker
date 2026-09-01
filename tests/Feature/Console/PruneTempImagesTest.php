<?php

namespace Tests\Feature\Console;

use App\Services\ImageHandler;
use App\Services\TempImageStore;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PruneTempImagesTest extends TestCase
{
    private function stash(string $name = 'flyer.jpg'): string
    {
        return (new TempImageStore(new ImageHandler()))->stash(UploadedFile::fake()->image($name));
    }

    private function tempPath(string $token): string
    {
        return storage_path('app/' . TempImageStore::TEMP_DIR . '/' . $token);
    }

    private function age(string $token, int $hours): void
    {
        touch($this->tempPath($token), now()->subHours($hours)->getTimestamp());
    }

    protected function tearDown(): void
    {
        foreach ((array) glob(storage_path('app/' . TempImageStore::TEMP_DIR . '/*')) as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_it_removes_stale_temp_images_and_keeps_fresh_ones(): void
    {
        $stale = $this->stash('stale.jpg');
        $fresh = $this->stash('fresh.jpg');
        $this->age($stale, 48);

        $this->artisan('images:prune-temp')
            ->expectsOutputToContain('Removed 1 temp image(s)')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempPath($stale));
        $this->assertFileExists($this->tempPath($fresh));
    }

    public function test_it_honours_the_hours_option(): void
    {
        $token = $this->stash('recent.jpg');
        $this->age($token, 3);

        // Older than 2h, so it goes.
        $this->artisan('images:prune-temp', ['--hours' => 2])->assertExitCode(0);

        $this->assertFileDoesNotExist($this->tempPath($token));
    }

    public function test_a_dry_run_reports_without_deleting(): void
    {
        $token = $this->stash('stale.jpg');
        $this->age($token, 48);

        $this->artisan('images:prune-temp', ['--dry-run' => true])
            ->expectsOutputToContain('Would remove 1 temp image(s)')
            ->assertExitCode(0);

        $this->assertFileExists($this->tempPath($token));
    }

    public function test_it_rejects_a_zero_hours_option(): void
    {
        $this->artisan('images:prune-temp', ['--hours' => 0])
            ->expectsOutputToContain('must be at least 1')
            ->assertExitCode(1);
    }
}
