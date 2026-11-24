<?php

namespace App\Models\Concerns;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The photos of a model and its primary one.
 *
 * Photos are not polymorphic: each model has its own pivot table
 * (event_photo, entity_photo, photo_series, blog_photo, photo_user), which
 * belongsToMany() names from the model. A model needs that table before it
 * can use this trait; threads and posts have none.
 */
trait OwnsPhotos
{
    /**
     * @return BelongsToMany<Photo, $this>
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * The primary photo, from the loaded photos when there are any (so a list
     * that eager loads them adds no query).
     */
    public function getPrimaryPhoto(): ?Photo
    {
        if ($this->relationLoaded('photos')) {
            return $this->photos->firstWhere('is_primary', 1);
        }

        /** @var Photo|null $photo */
        $photo = $this->photos()->where('photos.is_primary', '=', '1')->first();

        return $photo;
    }

    /**
     * Public URL of the primary photo.
     */
    public function getPrimaryPhotoPath(): ?string
    {
        return $this->getPrimaryPhoto()?->getPath();
    }

    /**
     * Public URL of the primary photo's thumbnail.
     */
    public function getPrimaryPhotoThumbnailPath(): ?string
    {
        return $this->getPrimaryPhoto()?->getThumbnailPath();
    }
}
