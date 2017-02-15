<?php namespace App;

use Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
//use App\Http\Controllers\UploadedFile;

class Follow extends Eloquent {

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

	/**
	 * @var Array
	 *
	 **/
	protected $fillable = [
	'object_id','user_id','object_type'
	];

 
	protected $dates = ['created_at','updated_at'];


	/**
	 * Get the user that the response belongs to
	 *
	 */
	public function users()
	{
		return $this->belongsToMany('App\User')->withTimestamps();
	}

	/**
	 * Get the object being followed
	 *
	 */
	public function getObject()
	{
		// how can i derive this class from a string?
		if (!$object = call_user_func("App\\".ucfirst($this->getObjectType())."::find", $this->getObjectId())) // Tag::find($id)) 
		{
			return $object;
		};
	}

	public function followable()
	{
		return $this->morphTo();
	}
}
