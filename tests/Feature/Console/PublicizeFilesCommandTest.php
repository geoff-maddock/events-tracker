<?php

namespace Tests\Feature\Console;

use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicizeFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_command_sets_each_photo_path_and_thumbnail_to_public(): void
    {
        $disk = Storage::fake('external');

        $photo = Photo::factory()->create([
            'path' => 'photos/example.jpg',
            'thumbnail' => 'photos/example-thumb.jpg',
        ]);

        $disk->put($photo->path, 'image-bytes', 'private');
        $disk->put($photo->thumbnail, 'thumb-bytes', 'private');

        $this->artisan('file:publicize')->assertExitCode(0);

        $this->assertSame('public', $disk->getVisibility($photo->path));
        $this->assertSame('public', $disk->getVisibility($photo->thumbnail));
    }

    public function test_command_runs_cleanly_with_no_photos(): void
    {
        Storage::fake('external');

        $this->artisan('file:publicize')->assertExitCode(0);
    }
}
