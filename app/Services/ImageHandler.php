<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;


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
    

    /**
     * Generate an image to use with posting to instagram
     */
    public function generateCoverImage($fileName = 'week-image.jpg'): mixed
    {
        // create an array of 12 hex color strings with a key of the month number
        $colors = [
            1 => '#2980B9',
            2 => '#3498DB',
            3 => '#1ABC9C',
            4 => '#16A085',
            5 => '#27AE60',
            6 => '#2ECC71',
            7 => '#F1C40F',
            8 => '#F39C12',
            9 => '#E67E22',
            10 => '#D35400 ',
            11 => '#C0392B',
            12 => '#8E44AD',
        ];

        // set a color based on the month of the first day of the week
        $color = $colors[Carbon::now()->month];
        $img = Image::canvas(1080, 1080, $color);

        // use carbon to get a string of the first and last day of the week, using the short weekday, short month, and day
        $start = Carbon::now()->startOfWeek()->format('D M j');
        $end = Carbon::now()->endOfWeek()->format('D M j');
        $week = $start.' - '.$end;

        $img->text('Events for the Week', 200, 400, function($font) {
            $font->file('fonts/LEMONMILK-MEDIUM.OTF');
            $font->size(60);
            $font->color('#000000');
            $font->align('left');
            $font->valign('top');
        });

        $img->text($week, 200, 500, function($font) {
            $font->file('fonts/LEMONMILK-MEDIUM.OTF');
            $font->size(42);
            $font->color('#DEDEDE');
            $font->align('left');
            $font->valign('top');
        });

        // creates a valid name for a jpg file
        //$fileName = 'week-image.jpg';
        $filePath = sprintf('%s/%s', 'photos', $fileName);

        // builds an image given the path of the file on the external disk, then creates a version
        $image = $img->encode('jpg', 75)->save('storage/'.$filePath);

        return $image;
    }
}