<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * App\Models\Photo
 *
 * @property int $id
 * @property string $name
 * @property string $thumbnail
 * @property string $path
 * @property string $caption
 * @property int $is_public
 * @property int $is_primary
 * @property int $is_event
 * @property int $is_approved
 * @property int $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property-read int|null $entities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Event[] $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Series[] $series
 * @property-read int|null $series_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
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

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'name', 'path', 'thumbnail', 'caption',
    ];

    protected $dates = ['created_at', 'updated_at'];

    protected $baseDir = 'photos';

    /**
     * Get the entity that the photo belogs to.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get the event that the photo belongs to.
     */
    public function events()
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Get the user that the photo belongs to.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Get the event series that the photo belongs to.
     */
    public function series()
    {
        return $this->belongsToMany(Series::class)->withTimestamps();
    }

    public static function fromForm(UploadedFile $file)
    {
        $name = time() . $file->getClientOriginalName(); //12131241filename

        $photo = new static();
        $photo->name = $name;
        $photo->path = $photo->baseDir . '/' . $name;
        $photo->thumbnail = $photo->baseDir . '/' . $name;
        $photo->caption = $file->getClientOriginalName();

        $file->move($photo->baseDir, $name);

        $photo->save();

        return $photo;
    }

    public static function named($name)
    {
        return (new static())->saveAs($name);
    }

    protected function saveAs($name)
    {
        $this->name = sprintf('%s_%s', time(), $name);
        $this->path = sprintf('%s/%s', $this->baseDir, $this->name);
        $this->thumbnail = sprintf('%s/tn-%s', $this->baseDir, $this->name);
        $this->caption = $name;

        return $this;
    }

    public function move(UploadedFile $file)
    {
        try {
            rename(public_path() . '/' . $this->baseDir . '/temp.jpg', public_path() . '/' . $this->baseDir . '/' . $this->name);
        } catch (FileException $fileException) {
            // do nothing
        }

        $this->makeThumbnail();

        return $this;
    }

    public function makeThumbnail()
    {
        Image::make('storage/' . $this->path)
            ->fit(200)
            ->save('storage/' . $this->thumbnail);

        return $this;
    }

    public function delete()
    {
        File::delete([
            $this->path,
            $this->thumbnail,
        ]);

        return parent::delete();
    }

    public function getTwitterPath(): string
    {
        return 'storage/' . $this->path;
    }

    public function getStoragePath(): string
    {
        return '/storage/' . $this->path;
    }

    public function getStorageThumbnail(): string
    {
        return '/storage/' . $this->thumbnail;
    }
}
