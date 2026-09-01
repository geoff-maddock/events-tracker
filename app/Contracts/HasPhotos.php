<?php

namespace App\Contracts;

use App\Models\Photo;

/**
 * A model that can adopt a Photo.
 *
 * Photos are not polymorphic in this application — Event, Entity and Series
 * each own a separate pivot — so this interface is what lets shared code
 * (TempImageStore) attach an image without caring which one it has.
 */
interface HasPhotos
{
    public function addPhoto(Photo $photo): void;
}
