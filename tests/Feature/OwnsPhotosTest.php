<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The photos relation and primary photo helpers are one trait, OwnsPhotos (#2409).
 */
class OwnsPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_each_model_finds_its_primary_photo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertPrimaryPhoto(Event::factory()->create(), Event::factory()->create());
        $this->assertPrimaryPhoto(Entity::factory()->create(), Entity::factory()->create());
        $this->assertPrimaryPhoto(Series::factory()->create(), Series::factory()->create());
        $this->assertPrimaryPhoto(Blog::factory()->create(), Blog::factory()->create());
        $this->assertPrimaryPhoto(User::factory()->create(), User::factory()->create());
    }

    private function assertPrimaryPhoto(Event|Entity|Series|Blog|User $empty, Event|Entity|Series|Blog|User $model): void
    {
        $this->assertNull($empty->getPrimaryPhoto());
        $this->assertNull($empty->getPrimaryPhotoPath());
        $this->assertNull($empty->getPrimaryPhotoThumbnailPath());

        $other = Photo::factory()->create(['is_primary' => 0]);
        $primary = Photo::factory()->create(['is_primary' => 1]);
        $model->addPhoto($other);
        $model->addPhoto($primary);

        // by query
        $this->assertTrue($primary->is($model->getPrimaryPhoto()));
        $this->assertSame($primary->getPath(), $model->getPrimaryPhotoPath());
        $this->assertSame($primary->getThumbnailPath(), $model->getPrimaryPhotoThumbnailPath());

        // from the loaded relation, with no further query
        $model->load('photos');
        $this->assertCount(2, $model->photos);
        DB::enableQueryLog();
        $this->assertTrue($primary->is($model->getPrimaryPhoto()));
        $this->assertSame($primary->getPath(), $model->getPrimaryPhotoPath());
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
}
