<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\ImageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Hero images at display size (#1932): the 1200px variant written on upload
 * and by photos:generate-variants, the fallback for photos without one, the
 * hero srcset, and the optional rebuild of heavy legacy thumbnails.
 */
class HeroImageVariantTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Storage::fake('external');
    }

    /** WebP bytes of a noisy image, so encoder quality makes a real size difference. */
    private function webp(int $width, int $height, int $quality = ImageHandler::WEBP_QUALITY): string
    {
        $gd = imagecreatetruecolor($width, $height);
        mt_srand(1932);
        for ($y = 0; $y < $height; $y += 4) {
            for ($x = 0; $x < $width; $x += 4) {
                imagefilledrectangle($gd, $x, $y, $x + 3, $y + 3, mt_rand(0, 0xFFFFFF));
            }
        }
        ob_start();
        imagewebp($gd, null, $quality);

        return (string) ob_get_clean();
    }

    /** A primary photo stored the way makePhoto() stores one, before #1932. */
    private function legacyPhoto(string $name, int $width, int $height, bool $primary = true, ?string $thumbBytes = null): Photo
    {
        $photo = Photo::factory()->create([
            'name' => "{$name}.webp",
            'path' => "photos/{$name}.webp",
            'thumbnail' => "photos/tn-{$name}.webp",
            'large' => null,
            'is_primary' => $primary ? 1 : 0,
        ]);
        Storage::disk('external')->put($photo->path, $this->webp($width, $height));
        Storage::disk('external')->put($photo->thumbnail, $thumbBytes ?? $this->webp(600, 600));

        return $photo;
    }

    private function dimensions(string $path): array
    {
        $size = getimagesizefromstring((string) Storage::disk('external')->get($path));

        return [$size[0], $size[1]];
    }

    // --- upload ---

    public function test_a_large_upload_gets_a_1200px_hero_variant(): void
    {
        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('poster.jpg', 3000, 1500));

        $this->assertSame(sprintf('photos/lg-%s', $photo->name), $photo->large);
        $this->assertTrue($photo->hasLargeVariant());
        $this->assertSame([ImageHandler::LARGE_DIMENSION, ImageHandler::LARGE_DIMENSION / 2], $this->dimensions($photo->large));
        $this->assertSame('image/webp', getimagesizefromstring((string) Storage::disk('external')->get($photo->large))['mime']);

        // the main image and the thumbnail are as before
        $this->assertSame([ImageHandler::MAX_DIMENSION, ImageHandler::MAX_DIMENSION / 2], $this->dimensions($photo->path));
        $this->assertSame([ImageHandler::THUMBNAIL_SIZE, ImageHandler::THUMBNAIL_SIZE], $this->dimensions($photo->thumbnail));
    }

    public function test_a_small_upload_gets_no_extra_file(): void
    {
        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('small.jpg', 1000, 800));

        $this->assertSame($photo->path, $photo->large);
        $this->assertFalse($photo->hasLargeVariant());
        // original, main and thumbnail only
        $this->assertCount(3, Storage::disk('external')->allFiles('photos'));
    }

    // --- model ---

    public function test_a_photo_without_the_variant_falls_back_to_the_main_image(): void
    {
        $photo = new Photo(['path' => 'photos/old.jpg', 'thumbnail' => 'photos/tn-old.jpg', 'large' => null]);

        $this->assertSame('photos/old.jpg', $photo->getStorageLarge());
        $this->assertFalse($photo->hasLargeVariant());
    }

    public function test_deleting_a_photo_removes_its_hero_variant(): void
    {
        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('poster.jpg', 2400, 1600));
        $photo->save();
        $large = $photo->large;
        Storage::disk('external')->assertExists($large);

        $photo->delete();

        Storage::disk('external')->assertMissing([$large, $photo->path, $photo->thumbnail]);
    }

    // --- hero markup ---

    public function test_the_entity_hero_serves_the_variant_with_a_srcset(): void
    {
        $entity = Entity::factory()->create();
        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('venue.jpg', 2400, 1600));
        $photo->is_primary = 1;
        $photo->save();
        $entity->photos()->attach($photo->id);

        $html = $this->withHeader('User-Agent', self::BROWSER)->get(route('entities.show', $entity))->assertOk()->getContent();

        $large = Storage::disk('external')->url($photo->large);
        $main = Storage::disk('external')->url($photo->path);
        $this->assertStringContainsString('src="'.$large.'"', $html);
        $this->assertStringContainsString('srcset="'.$large.' 1200w, '.$main.' 2000w"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
        // the lightbox still opens the full image
        $this->assertStringContainsString('href="'.$main.'"', $html);
    }

    public function test_the_event_hero_serves_the_variant_with_a_srcset(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $photo = (new ImageHandler())->makePhoto(UploadedFile::fake()->image('flyer.jpg', 1600, 2400));
        $photo->is_primary = 1;
        $photo->save();
        $event->photos()->attach($photo->id);

        $html = $this->withHeader('User-Agent', self::BROWSER)->get(route('events.show', $event))->assertOk()->getContent();

        $large = Storage::disk('external')->url($photo->large);
        $this->assertStringContainsString('src="'.$large.'"', $html);
        $this->assertStringContainsString($large.' 1200w', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
    }

    public function test_a_hero_without_the_variant_uses_the_main_image_and_no_srcset(): void
    {
        $entity = Entity::factory()->create();
        $photo = $this->legacyPhoto('legacy-hero', 900, 600);
        $entity->photos()->attach($photo->id);

        $html = $this->withHeader('User-Agent', self::BROWSER)->get(route('entities.show', $entity))->assertOk()->getContent();

        $this->assertStringContainsString('src="'.Storage::disk('external')->url($photo->path).'"', $html);
        $this->assertStringNotContainsString(' 1200w', $html);
    }

    // --- backfill ---

    public function test_the_backfill_writes_the_variant_for_primary_photos(): void
    {
        $big = $this->legacyPhoto('big', 2000, 1000);
        $small = $this->legacyPhoto('small', 800, 600);
        $notHero = $this->legacyPhoto('gallery', 2000, 1000, primary: false);

        $this->artisan('photos:generate-variants')
            ->expectsOutputToContain('Wrote 1 hero variant(s), 1 already small enough')
            ->assertExitCode(0);

        $big->refresh();
        $this->assertSame('photos/lg-big.webp', $big->large);
        $this->assertSame([1200, 600], $this->dimensions($big->large));
        $this->assertSame($small->path, $small->fresh()->large);
        $this->assertNull($notHero->fresh()->large);
    }

    public function test_the_backfill_is_safe_to_re_run(): void
    {
        $this->legacyPhoto('big', 2000, 1000);
        $this->artisan('photos:generate-variants');

        $this->artisan('photos:generate-variants')
            ->expectsOutputToContain('0 primary photo(s) without a hero variant')
            ->assertExitCode(0);
    }

    public function test_the_backfill_dry_run_writes_nothing(): void
    {
        $photo = $this->legacyPhoto('big', 2000, 1000);
        $before = Storage::disk('external')->allFiles('photos');

        $this->artisan('photos:generate-variants', ['--dry-run' => true, '--thumbnails' => true])
            ->expectsOutputToContain('DRY RUN: would write 1 hero variant(s)')
            ->assertExitCode(0);

        $this->assertNull($photo->fresh()->large);
        $this->assertSame($before, Storage::disk('external')->allFiles('photos'));
    }

    public function test_a_photo_missing_from_the_disk_is_skipped_and_retried_later(): void
    {
        $missing = $this->legacyPhoto('missing', 2000, 1000);
        Storage::disk('external')->delete($missing->path);
        $fine = $this->legacyPhoto('fine', 2000, 1000);

        $this->artisan('photos:generate-variants')
            ->expectsOutputToContain('1 failed')
            ->assertExitCode(0);

        $this->assertNull($missing->fresh()->large);
        $this->assertNotNull($fine->fresh()->large);
    }

    public function test_the_limit_caps_a_batch(): void
    {
        foreach (range(1, 3) as $i) {
            $this->legacyPhoto("p{$i}", 2000, 1000);
        }

        $this->artisan('photos:generate-variants', ['--limit' => 2]);

        $this->assertSame(1, Photo::query()->where('is_primary', 1)->whereNull('large')->count());
    }

    public function test_a_heavy_legacy_thumbnail_is_rebuilt_smaller(): void
    {
        // the same picture at quality 100: what an over-heavy legacy thumbnail looks like
        $photo = $this->legacyPhoto('heavy', 600, 600, thumbBytes: $this->webp(600, 600, 100));
        $before = Storage::disk('external')->size($photo->thumbnail);

        $this->artisan('photos:generate-variants', ['--thumbnails' => true])
            ->expectsOutputToContain('1 thumbnail(s) rebuilt')
            ->assertExitCode(0);

        $after = Storage::disk('external')->size($photo->thumbnail);
        $this->assertLessThan($before * 0.9, $after);
        $this->assertSame([600, 600], $this->dimensions($photo->thumbnail));
    }

    public function test_a_thumbnail_that_is_already_lean_is_left_alone(): void
    {
        $lean = (string) app(ImageManager::class)->read($this->webp(600, 600))->cover(600, 600)->toWebp(ImageHandler::WEBP_QUALITY);
        $photo = $this->legacyPhoto('lean', 600, 600, thumbBytes: $lean);

        $this->artisan('photos:generate-variants', ['--thumbnails' => true])
            ->expectsOutputToContain('0 thumbnail(s) rebuilt');

        $this->assertSame($lean, Storage::disk('external')->get($photo->thumbnail));
    }

    public function test_thumbnails_are_not_rebuilt_without_the_flag(): void
    {
        $heavy = $this->webp(600, 600, 100);
        $photo = $this->legacyPhoto('heavy', 600, 600, thumbBytes: $heavy);

        $this->artisan('photos:generate-variants');

        $this->assertSame($heavy, Storage::disk('external')->get($photo->thumbnail));
    }
}
