<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Photo extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    //protected $dateFormat = 'Y-m-d\\TH:i';

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

        parent::delete();
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
