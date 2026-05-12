<?php

namespace Tests\Feature\Services;

use App\Services\ImageHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Mockery;
use Tests\TestCase;

class ImageHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Replace Intervention's Image facade with a self-chained mock so the
     * tests do not depend on real image-processing or network-style URL
     * fetching (Storage::fake('external')->url() is a placeholder).
     *
     * Each call to Image::make() returns a mock whose chained methods (fit,
     * encode, save, destroy) return the mock itself. basePath() returns a
     * real temp file path so the production code's unlink() succeeds.
     */
    private function stubImageFacade(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('fit')->andReturnSelf();
        $mock->shouldReceive('encode')->andReturnSelf();
        $mock->shouldReceive('save')->andReturnSelf();
        $mock->shouldReceive('destroy')->andReturnNull();
        $mock->shouldReceive('basePath')->andReturnUsing(function () {
            $tmp = tempnam(sys_get_temp_dir(), 'imghandler-');

            return $tmp;
        });

        Image::shouldReceive('make')->andReturn($mock);
    }

    public function test_make_photo_stores_uploaded_file_on_external_disk(): void
    {
        Storage::fake('external');
        $this->stubImageFacade();

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
        $this->stubImageFacade();

        // After makeWebp() the Photo's name is rewritten to the .webp variant
        // (see Photo::makeWebp → saveAs($webpName)). Assert the final shape.
        $file = UploadedFile::fake()->image('promo.png');
        $photo = (new ImageHandler())->makePhoto($file);

        $this->assertStringEndsWith('.webp', $photo->name);
        $this->assertSame('photos/'.$photo->name, $photo->path);
        $this->assertStringStartsWith('photos/tn-', $photo->thumbnail);
    }

    public function test_make_photo_preserves_original_extension_in_uploaded_file(): void
    {
        Storage::fake('external');
        $this->stubImageFacade();

        $file = UploadedFile::fake()->image('flyer.jpg');
        (new ImageHandler())->makePhoto($file);

        // Find what was originally uploaded; .jpg before webp re-saves over it.
        $stored = Storage::disk('external')->allFiles('photos');
        $jpgs = array_filter($stored, fn ($p) => str_ends_with($p, '_flyer.jpg'));

        $this->assertNotEmpty($jpgs, 'Expected the original .jpg upload to be stored.');
    }
}
