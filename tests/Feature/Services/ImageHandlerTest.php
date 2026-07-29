<?php

namespace Tests\Feature\Services;

use App\Services\ImageHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Exercises the real Intervention Image v3 pipeline (GD driver) against the
 * faked external disk: Photo::makeWebp / Photo::makeThumbnail read the stored
 * bytes via Storage::get(), re-encode locally, and upload the variants back.
 */
class ImageHandlerTest extends TestCase
{
    public function test_make_photo_stores_uploaded_file_on_external_disk(): void
    {
        Storage::fake('external');

        $file = UploadedFile::fake()->image('event.png');
        (new ImageHandler())->makePhoto($file);

        // The original upload lands on the external disk under photos/ with a
        // timestamp_event.png filename (the webp/thumb variants are rewrites
        // performed by Photo::makeWebp / Photo::makeThumbnail).
        $stored = Storage::disk('external')->allFiles('photos');
        $original = array_filter($stored, fn ($p) => (bool) preg_match('#^photos/\d+_event\.png$#', $p));

        $this->assertNotEmpty($original, 'Expected the timestamped original upload to be on the external disk.');
    }

    public function test_make_photo_returns_photo_with_path_and_thumbnail_set(): void
    {
        Storage::fake('external');

        // After makeWebp() the Photo's name is rewritten to the .webp variant
        // (see Photo::makeWebp → saveAs($webpName)). Assert the final shape.
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

        // Find what was originally uploaded; .jpg before webp re-saves over it.
        $stored = Storage::disk('external')->allFiles('photos');
        $jpgs = array_filter($stored, fn ($p) => str_ends_with($p, '_flyer.jpg'));

        $this->assertNotEmpty($jpgs, 'Expected the original .jpg upload to be stored.');
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
