<?php

namespace Tests\Feature\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Services\ImageHandler;
use App\Services\TempImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Exercises the token lifecycle: stash on the local disk, redeem onto a model
 * (which pushes the real image through ImageHandler onto the faked external
 * disk), and prune what is abandoned.
 */
class TempImageStoreTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function store(): TempImageStore
    {
        return new TempImageStore(new ImageHandler());
    }

    private function tempPath(string $token): string
    {
        return storage_path('app/' . TempImageStore::TEMP_DIR . '/' . $token);
    }

    private function legacyPath(string $token): string
    {
        return storage_path('app/' . TempImageStore::LEGACY_TEMP_DIR . '/' . $token);
    }

    private function stashFake(string $name = 'flyer.jpg'): string
    {
        return $this->store()->stash(UploadedFile::fake()->image($name));
    }

    protected function tearDown(): void
    {
        foreach ([TempImageStore::TEMP_DIR, TempImageStore::LEGACY_TEMP_DIR] as $dir) {
            foreach ((array) glob(storage_path('app/' . $dir . '/*')) as $file) {
                if (is_string($file) && is_file($file)) {
                    @unlink($file);
                }
            }
        }

        parent::tearDown();
    }

    // ── stash ────────────────────────────────────────────────────────────

    public function test_stash_writes_the_file_and_returns_a_uuid_token(): void
    {
        $token = $this->stashFake();

        $this->assertMatchesRegularExpression('/^[a-f0-9\-]{36}\.(jpg|jpeg|png|gif|webp)$/', $token);
        $this->assertFileExists($this->tempPath($token));
    }

    public function test_stash_reuses_a_supplied_token_instead_of_creating_a_second_file(): void
    {
        $store = $this->store();
        $first = $store->stash(UploadedFile::fake()->image('one.jpg'));
        $second = $store->stash(UploadedFile::fake()->image('two.jpg'), $first);

        $this->assertSame($first, $second);
        $this->assertCount(1, (array) glob(storage_path('app/' . TempImageStore::TEMP_DIR . '/*')));
    }

    public function test_stash_ignores_a_malformed_reuse_token(): void
    {
        $token = $this->store()->stash(UploadedFile::fake()->image('x.jpg'), '../../etc/passwd');

        $this->assertMatchesRegularExpression('/^[a-f0-9\-]{36}\./', $token);
    }

    // ── path ─────────────────────────────────────────────────────────────

    public function test_path_returns_null_for_null_and_malformed_tokens(): void
    {
        $store = $this->store();

        $this->assertNull($store->path(null));
        $this->assertNull($store->path(''));
        $this->assertNull($store->path('../../../etc/passwd'));
        $this->assertNull($store->path('not-a-uuid.jpg'));
        // well-formed uuid, but an extension we will not hand to the pipeline
        $this->assertNull($store->path('3f1c8a5e-9b2d-4c7a-8e6f-1d2b3c4a5e6f.php'));
    }

    public function test_path_returns_null_when_the_file_is_gone(): void
    {
        $token = $this->stashFake();
        unlink($this->tempPath($token));

        $this->assertNull($this->store()->path($token));
    }

    public function test_path_falls_back_to_the_legacy_directory(): void
    {
        // A token minted before this shipped still lives under flyer_temp.
        $legacyDir = storage_path('app/' . TempImageStore::LEGACY_TEMP_DIR);

        if (!is_dir($legacyDir) && !@mkdir($legacyDir, 0755, true)) {
            $this->markTestSkipped('Could not create the legacy temp directory.');
        }

        if (!is_writable($legacyDir)) {
            // In a dev sandbox this directory is usually left behind by the
            // web server and owned by www-data, so the test user cannot write
            // to it. Production and CI both run as a single user.
            $this->markTestSkipped('The legacy temp directory is not writable by the test user.');
        }

        $token = $this->stashFake();
        rename($this->tempPath($token), $this->legacyPath($token));

        $this->assertSame($this->legacyPath($token), $this->store()->path($token));
    }

    // ── attachTo ─────────────────────────────────────────────────────────

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function modelProvider(): array
    {
        return [
            'event' => [Event::class],
            'entity' => [Entity::class],
            'series' => [Series::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modelProvider')]
    public function test_attach_to_creates_a_primary_photo_and_removes_the_temp_file(string $modelClass): void
    {
        Storage::fake('external');

        $model = $modelClass::factory()->create();
        $token = $this->stashFake();

        $photo = $this->store()->attachTo($token, $model);

        $this->assertNotNull($photo);
        $this->assertSame(1, (int) $photo->is_primary);
        $this->assertTrue($model->fresh()->photos->contains($photo->id));
        $this->assertFileDoesNotExist($this->tempPath($token), 'The temp file should be cleaned up after attaching.');
    }

    public function test_attach_to_can_create_a_non_primary_photo(): void
    {
        Storage::fake('external');

        $entity = Entity::factory()->create();
        $photo = $this->store()->attachTo($this->stashFake(), $entity, false);

        $this->assertNotNull($photo);
        $this->assertSame(0, (int) $photo->is_primary);
    }

    public function test_attach_to_is_a_no_op_for_a_missing_or_bogus_token(): void
    {
        Storage::fake('external');

        $entity = Entity::factory()->create();
        $store = $this->store();

        $this->assertNull($store->attachTo(null, $entity));
        $this->assertNull($store->attachTo('../../etc/passwd', $entity));
        $this->assertNull($store->attachTo('3f1c8a5e-9b2d-4c7a-8e6f-1d2b3c4a5e6f.jpg', $entity));
        $this->assertCount(0, $entity->fresh()->photos);
    }

    public function test_attach_to_swallows_pipeline_failures_and_still_removes_the_temp_file(): void
    {
        Storage::fake('external');

        $entity = Entity::factory()->create();
        $token = $this->stashFake();

        // Not a decodable image, so the ImageHandler pipeline throws.
        file_put_contents($this->tempPath($token), 'definitely not an image');

        $photo = $this->store()->attachTo($token, $entity);

        $this->assertNull($photo, 'A failed attach must not throw, so a photo failure cannot fail the create.');
        $this->assertCount(0, $entity->fresh()->photos);
        $this->assertFileDoesNotExist($this->tempPath($token));
    }

    // ── request helpers ──────────────────────────────────────────────────

    public function test_token_from_request_prefers_the_current_field_and_accepts_the_legacy_one(): void
    {
        $store = $this->store();

        $this->assertSame('abc', $store->tokenFromRequest(new Request(['image_temp_token' => 'abc'])));
        $this->assertSame('legacy', $store->tokenFromRequest(new Request(['flyer_temp_token' => 'legacy'])));
        $this->assertSame('new', $store->tokenFromRequest(
            new Request(['image_temp_token' => 'new', 'flyer_temp_token' => 'old'])
        ));
        $this->assertNull($store->tokenFromRequest(new Request()));
        $this->assertNull($store->tokenFromRequest(new Request(['image_temp_token' => ''])));
    }

    // ── prune ────────────────────────────────────────────────────────────

    public function test_prune_removes_only_files_older_than_the_cutoff(): void
    {
        $old = $this->stashFake('old.jpg');
        $fresh = $this->stashFake('fresh.jpg');

        touch($this->tempPath($old), now()->subHours(48)->getTimestamp());

        $removed = $this->store()->prune(24);

        $this->assertSame(1, $removed);
        $this->assertFileDoesNotExist($this->tempPath($old));
        $this->assertFileExists($this->tempPath($fresh));
    }

    public function test_prune_dry_run_reports_without_deleting(): void
    {
        $old = $this->stashFake('old.jpg');
        touch($this->tempPath($old), now()->subHours(48)->getTimestamp());

        $removed = $this->store()->prune(24, true);

        $this->assertSame(1, $removed);
        $this->assertFileExists($this->tempPath($old), 'A dry run must not delete anything.');
    }
}
