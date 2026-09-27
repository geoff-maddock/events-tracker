<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Carbon\Carbon;


/**
 * Processes an uploaded image file
 */
class ImageHandler
{
    const CONTAINER_LIMIT = 4;


    /** Longest side of the stored main (webp) image; larger uploads are scaled down. */
    public const MAX_DIMENSION = 2000;

    /** Side of the square thumbnail. */
    public const THUMBNAIL_SIZE = 600;

    public const WEBP_QUALITY = 75;

    /**
     * Make a photo from an uploaded file.
     *
     * Stores the untouched original, then builds the main webp image (capped at
     * MAX_DIMENSION) and the square thumbnail from the local upload, so nothing
     * is downloaded back from the external disk (#2174). The photo's name and
     * path point at the webp; the thumbnail is tn-<webp name>.
     */
    public function makePhoto(UploadedFile $file): Photo
    {
        // store the file with a unique name based on time
        $fileName = time().'_'.$file->getClientOriginalName();

        // keep the original, publicly, under its unique name and original format
        $file->storePubliclyAs('photos', $fileName, 'external');

        // sets all the photo private name and path values
        $photo = Photo::named(pathinfo($fileName, PATHINFO_FILENAME).'.webp');

        // attribute the photo to the uploader; without this the column keeps
        // its DB default of 1 and photo management breaks for everyone else
        $photo->created_by = auth()->id() ?? 1;

        // decode the upload once; read() applies the EXIF orientation
        $image = app(ImageManager::class)->read($file->getRealPath());
        $image->scaleDown(self::MAX_DIMENSION, self::MAX_DIMENSION);

        $disk = Storage::disk('external');
        $disk->put($photo->path, (string) $image->toWebp(self::WEBP_QUALITY), 'public');

        $image->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        $disk->put($photo->thumbnail, (string) $image->toWebp(self::WEBP_QUALITY), 'public');

        return $photo;
    }

    /**
     * Generate an image to use with posting to instagram.
     * Returns the path of the generated local jpg file.
     */
    public function generateCoverImage($fileName = 'week-image.jpg'): string
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
        $img = app(ImageManager::class)->create(1080, 1080)->fill($color);

        // use carbon to get a string of the first and last day of the week, using the short weekday, short month, and day
        $start = Carbon::now()->startOfWeek()->format('D M j');
        $end = Carbon::now()->endOfWeek()->format('D M j');
        $week = $start.' - '.$end;

        $img->text('Events for the Week', 200, 400, function (FontFactory $font) {
            $font->filename(public_path('fonts/LEMONMILK-MEDIUM.OTF'));
            $font->size(60);
            $font->color('#000000');
            $font->align('left');
            $font->valign('top');
        });

        $img->text($week, 200, 500, function (FontFactory $font) {
            $font->filename(public_path('fonts/LEMONMILK-MEDIUM.OTF'));
            $font->size(42);
            $font->color('#DEDEDE');
            $font->align('left');
            $font->valign('top');
        });

        // write the generated image to a local scratch file
        $localPath = sys_get_temp_dir().'/'.$fileName;
        $img->toJpeg(75)->save($localPath);

        return $localPath;
    }
}