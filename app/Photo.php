<?php namespace App;

use Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
//use App\Http\Controllers\UploadedFile;

class Photo extends Eloquent {


    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    //protected $dateFormat = 'Y-m-d\\TH:i';

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'name', 'path', 'thumbnail', 'caption'
	];

 
	protected $dates = ['created_at','updated_at'];


	protected $baseDir = 'photos';
	
	/**
	 * Get the entity that the photo belogs to
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function entities()
	{
		return $this->belongsToMany('App\Entity')->withTimestamps();
	}

	/**
	 * Get the event that the photo belongs to
	 *
	 */
	public function events()
	{
		return $this->belongsToMany('App\Event')->withTimestamps();
	}

	/**
	 * Get the user that the photo belongs to
	 *
	 */
	public function users()
	{
		return $this->belongsToMany('App\User')->withTimestamps();
	}

	/**
	 * Get the event series that the photo belongs to
	 *
	 */
	public function series()
	{
		return $this->belongsToMany('App\Series')->withTimestamps();
	}

	public static function fromForm(UploadedFile $file)
	{
		$name = time() . $file->getClientOriginalName(); //12131241filename

		$photo = new static;
		$photo->name = $name;
		$photo->path = $photo->baseDir.'/'. $name ;
		$photo->thumbnail = $photo->baseDir.'/'. $name;
		$photo->caption = $file->getClientOriginalName();

		$file->move($photo->baseDir, $name);

		$photo->save();
		
		return $photo;
	}


	public static function named($name)
	{
		return (new static)->saveAs($name);
	}

	protected function saveAs($name)
	{
		$this->name = sprintf("%s-%s", time(), $name);
		$this->path = sprintf("%s/%s", $this->baseDir, $this->name);
		$this->thumbnail = sprintf("%s/tn-%s", $this->baseDir, $this->name);
		$this->caption = $name;
		return $this;
	}

	public function move(UploadedFile $file)
	{
		$file->move($this->baseDir, $this->name);

		$this->makeThumbnail();

		return $this;
	}

	protected function makeThumbnail()
	{
		Image::make($this->path)
			->fit(200)
			->save($this->thumbnail);

		return $this;
	}

	public function delete()
	{
		\File::delete([
				$this->path,
				$this->thumbnail
			]);

		parent::delete();
	}
}
