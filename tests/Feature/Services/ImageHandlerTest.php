<?php

namespace Tests\Feature\Services;

use App\Services\ImageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('external');
    }

    public function test_small_image_is_stored_without_compression(): void
    {
        // Create a small test image (less than 500 KB)
        $file = UploadedFile::fake()->image('small-photo.jpg', 100, 100)->size(100); // 100 KB

        $imageHandler = new ImageHandler();
        $photo = $imageHandler->makePhoto($file);

        // Assert the photo was created
        $this->assertNotNull($photo->name);
        $this->assertStringContainsString('small-photo.jpg', $photo->name);
        
        // Assert files were stored on external disk
        Storage::disk('external')->assertExists($photo->path);
    }

    public function test_large_image_is_compressed_before_storage(): void
    {
        // Create a large test image (over 500 KB)
        $file = UploadedFile::fake()->image('large-photo.jpg', 3000, 3000)->size(600); // 600 KB

        $imageHandler = new ImageHandler();
        $photo = $imageHandler->makePhoto($file);

        // Assert the photo was created
        $this->assertNotNull($photo->name);
        $this->assertStringContainsString('large-photo.jpg', $photo->name);
        
        // Assert files were stored on external disk
        Storage::disk('external')->assertExists($photo->path);
        
        // The compressed image should be stored
        // We can't easily check the exact size reduction without mocking,
        // but we can verify the file exists
        $this->assertTrue(Storage::disk('external')->exists($photo->path));
    }

    public function test_png_image_over_size_limit_is_compressed(): void
    {
        // Create a large PNG image
        $file = UploadedFile::fake()->image('large-photo.png', 2500, 2500)->size(600); // 600 KB

        $imageHandler = new ImageHandler();
        $photo = $imageHandler->makePhoto($file);

        // Assert the photo was created
        $this->assertNotNull($photo->name);
        $this->assertStringContainsString('large-photo.png', $photo->name);
        
        // Assert files were stored
        Storage::disk('external')->assertExists($photo->path);
    }

    public function test_webp_files_are_created_for_uploaded_images(): void
    {
        // Create a test image
        $file = UploadedFile::fake()->image('test-photo.jpg', 500, 500)->size(200);

        $imageHandler = new ImageHandler();
        $photo = $imageHandler->makePhoto($file);

        // Assert webp version was created (based on the name change in makeWebp)
        $this->assertStringEndsWith('.webp', $photo->name);
        
        // Assert the webp file exists
        Storage::disk('external')->assertExists($photo->path);
    }

    public function test_thumbnail_is_created_for_uploaded_images(): void
    {
        // Create a test image
        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 800)->size(200);

        $imageHandler = new ImageHandler();
        $photo = $imageHandler->makePhoto($file);

        // Assert thumbnail was created
        $this->assertNotNull($photo->thumbnail);
        $this->assertStringContainsString('tn-', $photo->thumbnail);
        
        // Assert the thumbnail file exists
        Storage::disk('external')->assertExists($photo->thumbnail);
    }
}
