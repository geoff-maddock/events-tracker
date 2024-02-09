<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\File;
use Illuminate\Http\File as HttpFile;
use Intervention\Image\Facades\Image;
use Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * App\Models\Photo.
 *
 * @property int                                                           $id
 * @property string                                                        $name
 * @property string                                                        $thumbnail
 * @property string                                                        $path
 * @property string                                                        $caption
 * @property int                                                           $is_public
 * @property int                                                           $is_primary
 * @property int                                                           $is_event
 * @property int                                                           $is_approved
 * @property int                                                           $created_by
 * @property int|null                                                      $updated_by
 * @property \Illuminate\Support\Carbon|null                               $created_at
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]  $events
 * @property int|null                                                      $events_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Series[] $series
 * @property int|null                                                      $series_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\User[]   $users
 * @property int|null                                                      $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Photo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Photo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Photo query()
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereThumbnail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Photo whereUpdatedBy($value)
 * @mixin \Eloquent
 */
class Photo extends Eloquent
{
    use HasFactory;

    const BASEDIR = 'photos';

    const STORAGEDIR = 'storage';

    private string $thumbName = '';

    protected $fillable = [
        'name', 'path', 'thumbnail', 'caption',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected string $baseDir = 'photos';

    /**
     * Get the entity that the photo belogs to.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get the event that the photo belongs to.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Get the user that the photo belongs to.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Get the event series that the photo belongs to.
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class)->withTimestamps();
    }

    public static function fromForm(UploadedFile $file): Photo
    {
        $name = time().$file->getClientOriginalName();

        $photo = new static();
        $photo->name = $name;
        $photo->path = $photo->baseDir.'/'.$name;
        $photo->thumbnail = $photo->baseDir.'/'.$name;
        $photo->caption = $file->getClientOriginalName();

        $file->move($photo->baseDir, $name);

        $photo->save();

        return $photo;
    }

    public function move(UploadedFile $file): Photo
    {
        try {
            rename(public_path().'/'.$this->baseDir.'/temp.jpg', public_path().'/'.$this->baseDir.'/'.$this->name);
        } catch (FileException $fileException) {
            // do nothing
        }

        $this->makeThumbnail();

        return $this;
    }


    public static function named(string $name): Photo
    {
        return (new static())->saveAs($name);
    }

    // Sets properies on the photo
    protected function saveAs(string $name): Photo
    {
        $this->name = sprintf('%s', $name);
        $this->path = sprintf('%s/%s', $this->baseDir, $name);
        $this->thumbnail = sprintf('%s/tn-%s', $this->baseDir, $name);
        $this->thumbName = sprintf('tn-%s', $name);
        $this->caption = $name;

        return $this;
    }


    public function makeThumbnail(): Photo
    {
        // builds an image given the path of the file on the external disk, then creates a version
        $image = Image::make(Storage::disk('external')->url($this->path))
            ->fit(200)
            ->save('storage/'.$this->thumbnail);

        $saved_image_uri = $image->basePath();

        $path = Storage::disk('external')->putFileAs('photos', new HttpFile($saved_image_uri), $this->thumbName, 'public');

        // clean up local files
        $image->destroy();
        unlink($saved_image_uri);

        return $this;
    }

    public function makeWebp(int $quality = 75): Photo
    {
        // extracts the parts of the file name
        $parts = pathinfo($this->name);

        // creates a valid name for a webp file
        $webpName = $parts['filename'].'.webp';
        $webpPath = sprintf('%s/%s', $this->baseDir, $webpName);

        // builds an image given the path of the file on the external disk, then creates a version
        $image = Image::make(Storage::disk('external')->url($this->path))
            // ->scaleDown(width: 200)
            ->encode('webp', $quality)
            ->save('storage/'.$webpPath);

        $saved_image_uri = $image->basePath();

        $path = Storage::disk('external')->putFileAs('photos', new HttpFile($saved_image_uri), $webpName, 'public');

        // clean up local files
        $image->destroy();
        unlink($saved_image_uri);

        // save the webp file as the name
        $this->saveAs($webpName);

        return $this;
    }

    public function makeJpg(int $quality = 75): Photo
    {
        // extracts the parts of the file name
        $parts = pathinfo($this->name);

        // creates a valid name for a jpg file
        $webpName = $parts['filename'].'.jpg';
        $webpPath = sprintf('%s/%s', $this->baseDir, $webpName);

        // builds an image given the path of the file on the external disk, then creates a version
        $image = Image::make(Storage::disk('external')->url($this->path))
            ->encode('jpg', $quality)
            ->save('storage/'.$webpPath);

        $saved_image_uri = $image->basePath();

        $path = Storage::disk('external')->putFileAs('photos', new HttpFile($saved_image_uri), $webpName, 'public');

        // clean up local files
        $image->destroy();
        unlink($saved_image_uri);

        // save the webp file as the name
        $this->saveAs($webpName);

        return $this;
    }

    public function delete()
    {
        Storage::disk('external')->delete([$this->path, $this->thumbnail]);

        return parent::delete();
    }

    public function getTwitterPath(): string
    {
        // return 'storage/'.$this->path;
        return $this->path;
    }

    public function getStoragePath(): string
    {
        // changed for external / s3 storage
        return $this->path;
        //return '/storage/'.$this->path;
    }

    public function getStorageThumbnail(): string
    {
        // changed for external / s3 storage
        return $this->thumbnail;
    }
}
