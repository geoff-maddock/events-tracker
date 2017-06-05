<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    protected $fillable = ['name','label','description','level'];

	public function groups()
	{
		return $this->belongsToMany(Group::class);
	}

	/**
	 * Get a list of group ids associated with the permission
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getGroupListAttribute()
	{
		return $this->groups->lists('id')->all();
	}
}
