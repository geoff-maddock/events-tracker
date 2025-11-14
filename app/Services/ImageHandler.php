<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


/**
 * Processes an uploaded image file
 */
class ImageHandler
{
    const CONTAINER_LIMIT = 4;

    // Maximum file size in bytes (500 KB)
    const MAX_FILE_SIZE = 500 * 1024;

    // Make a photo based on the passed in file
    public function makePhoto(UploadedFile $file): Photo
    {
        // store the file with a unique name based on time
        $fileName = time().'_'.$file->getClientOriginalName();

        // check if the file size exceeds the limit
        $fileSize = $file->getSize();
        
        if ($fileSize > self::MAX_FILE_SIZE) {
            // compress the image before storing
            $file = $this->compressImage($file, $fileName);
        }

        // from here, this file has been stored publicly under it's unique name and original format
        $filePath = $file->storePubliclyAs('photos', $fileName, 'external');
        
        // sets all the photo private name and path values
        $photo = Photo::named($fileName);

        // make a webp version of the image 
        $webp = $photo->makeWebp();

        return $photo->makeThumbnail();
    }

    /**
     * Compress an image to reduce file size
     * 
     * @param UploadedFile $file The file to compress
     * @param string $fileName The target filename
     * @return File The compressed image file
     */
    private function compressImage(UploadedFile $file, string $fileName): File
    {
        // Load the image using Intervention Image
        $image = Image::make($file->getRealPath());
        
        // Get original dimensions
        $width = $image->width();
        $height = $image->height();
        
        // Calculate new dimensions to reduce file size
        // Reduce to 80% if dimensions are large
        $maxDimension = 2000;
        if ($width > $maxDimension || $height > $maxDimension) {
            $image->resize($maxDimension, $maxDimension, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Determine the file extension and format
        $extension = strtolower($file->getClientOriginalExtension());
        $quality = 80;
        
        // Create a temporary file path
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        
        // Encode and save the image with compression
        if (in_array($extension, ['jpg', 'jpeg'])) {
            $image->encode('jpg', $quality)->save($tempPath);
        } elseif ($extension === 'png') {
            // PNG uses compression level 0-9, convert quality percentage
            $image->encode('png', 8)->save($tempPath);
        } elseif ($extension === 'webp') {
            $image->encode('webp', $quality)->save($tempPath);
        } else {
            // For other formats, convert to JPEG
            $image->encode('jpg', $quality)->save($tempPath);
        }
        
        // Clean up the intervention image
        $image->destroy();
        
        // Return a new File instance for the compressed image
        return new File($tempPath);
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