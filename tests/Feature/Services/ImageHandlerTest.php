<?php

namespace Tests\Feature\Services;

use App\Services\ImageHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Exercises the real Intervention Image v3 pipeline (GD driver) against the
 * faked external disk: makePhoto() stores the original, then encodes the webp
 * and thumbnail variants from the local upload and writes them alongside.
 */
class ImageHandlerTest extends TestCase
{
    public function test_make_photo_stores_uploaded_file_on_external_disk(): void
    {
        Storage::fake('external');

        $file = UploadedFile::fake()->image('event.png');
        (new ImageHandler())->makePhoto($file);

        // The original upload lands on the external disk under photos/ with a
        // timestamp_event.png filename, next to its webp and thumbnail variants.
        $stored = Storage::disk('external')->allFiles('photos');
        $original = array_filter($stored, fn ($p) => (bool) preg_match('#^photos/\d+_event\.png$#', $p));

        $this->assertNotEmpty($original, 'Expected the timestamped original upload to be on the external disk.');
    }

    public function test_make_photo_returns_photo_with_path_and_thumbnail_set(): void
    {
        Storage::fake('external');

        // The Photo's name is the .webp variant. Assert the final shape.
        $file = UploadedFile::fake()->image('promo.png');
        $photo = (new ImageHandler())->makePhoto($file);

        $this->assertStringEndsWith('.webp', $photo->name);
        $this->assertSame('photos/'.$photo->name, $photo->path);
        $this->assertStringStartsWith('photos/tn-', $photo->thumbnail);
    }

    public function test_make_photo_uploads_webp_and_thumbnail_variants(): void
    {
        Storage::fake('external');

        $file = UploadedFile::fake()->image('flyer.jpg', 800, 600);
        $photo = (new ImageHandler())->makePhoto($file);

        Storage::disk('external')->assertExists($photo->path);
        Storage::disk('external')->assertExists($photo->thumbnail);

        // The webp variant should hold genuinely re-encoded webp bytes.
        $webpBytes = Storage::disk('external')->get($photo->path);
        $this->assertSame('RIFF', substr($webpBytes, 0, 4), 'Expected the .webp variant to be RIFF/WEBP encoded.');
    }

    public function test_make_photo_preserves_original_extension_in_uploaded_file(): void
    {
        Storage::fake('external');

        $file = UploadedFile::fake()->image('flyer.jpg');
        (new ImageHandler())->makePhoto($file);

        // Find what was originally uploaded; the original is kept as-is.
        $stored = Storage::disk('external')->allFiles('photos');
        $jpgs = array_filter($stored, fn ($p) => str_ends_with($p, '_flyer.jpg'));

        $this->assertNotEmpty($jpgs, 'Expected the original .jpg upload to be stored.');
    }

    public function test_make_photo_caps_the_main_image_and_squares_the_thumbnail(): void
    {
        Storage::fake('external');

        $file = UploadedFile::fake()->image('poster.jpg', 3000, 1500);
        $photo = (new ImageHandler())->makePhoto($file);

        $main = getimagesizefromstring(Storage::disk('external')->get($photo->path));
        $this->assertSame([ImageHandler::MAX_DIMENSION, ImageHandler::MAX_DIMENSION / 2], [$main[0], $main[1]]);

        $thumb = getimagesizefromstring(Storage::disk('external')->get($photo->thumbnail));
        $this->assertSame([ImageHandler::THUMBNAIL_SIZE, ImageHandler::THUMBNAIL_SIZE], [$thumb[0], $thumb[1]]);
        $this->assertSame('image/webp', $thumb['mime']);
    }

    public function test_make_photo_does_not_enlarge_small_images(): void
    {
        Storage::fake('external');

        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('small.png', 400, 300));

        $main = getimagesizefromstring(Storage::disk('external')->get($photo->path));
        $this->assertSame([400, 300], [$main[0], $main[1]]);
    }

    public function test_make_photo_writes_three_files_and_reads_nothing_back(): void
    {
        Storage::fake('external');

        // the variants are built from the local upload; reading the stored
        // original back from the external disk was two downloads per upload
        $disk = Mockery::mock(Storage::disk('external'))->makePartial();
        $disk->shouldNotReceive('get', 'readStream');
        Storage::set('external', $disk);

        (new ImageHandler())->makePhoto(UploadedFile::fake()->image('flyer.jpg', 800, 600));

        $this->assertCount(3, Storage::disk('external')->allFiles('photos'));
    }

    public function test_the_cover_image_can_be_generated_in_every_month(): void
    {
        // each month has its own fill colour; October's had a trailing space
        // that the image library couldn't parse, so the cover failed all month
        foreach (range(1, 12) as $month) {
            $this->travelTo(\Carbon\Carbon::create(2026, $month, 15, 12));
            $path = (new ImageHandler())->generateCoverImage('test-month-cover.jpg');
            $this->assertSame('image/jpeg', getimagesize($path)['mime'], "month {$month}");
            unlink($path);
        }
    }

    public function test_generate_cover_image_writes_local_jpeg(): void
    {
        $path = (new ImageHandler())->generateCoverImage('test-week-image.jpg');

        $this->assertFileExists($path);
        $info = getimagesize($path);
        $this->assertSame(1080, $info[0]);
        $this->assertSame(1080, $info[1]);
        $this->assertSame('image/jpeg', $info['mime']);

        unlink($path);
    }
}
