<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;

/**
 * Processes an uploaded image file
 */
class ImageHandler
{
    const CONTAINER_LIMIT = 4;


    // Make a photo based on the passed in file
    public function makePhoto(UploadedFile $file): Photo
    {
        // store the file with a unique name based on time
        $fileName = time().'_'.$file->getClientOriginalName();

        // from here, this file has been stored publicly under it's unique name and original format
        $filePath = $file->storePubliclyAs('photos', $fileName, 'external');
        
        // sets all the photo private name and path values
        $photo = Photo::named($fileName);

        // make a webp version of the image 
        $webp = $photo->makeWebp();

        return $photo->makeThumbnail();
    }
    
}