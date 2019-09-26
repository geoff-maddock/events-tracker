<?php namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use App\Blog;

class Menu extends Eloquent {


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
	'name', 'slug', 'menu_parent_id', 'visibility_id'
	];

 
	protected $dates = ['created_at','updated_at'];

	
	/**
	 * A menu has at most one menu parent
	 *
	 */
	public function menuParent()
	{
		return $this->hasOne('App\Menu','id','menu_parent_id');
	}

	/**
	 * A location has one visibility
	 *
	 */
	public function visibility()
	{
		return $this->hasOne('App\Visibility','id','visibility_id');
	}


    /**
     * Returns visible events
     *
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name','=','Public')->first();

        $query->where('visibility_id','=', $public ? $public->id : NULL );

    }


    /**
     * The blogs that belong to the menu
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
