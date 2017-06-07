<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $fillable = ['name','label','description','level'];
    
	public function permissions()
	{
		return $this->belongsToMany(Permission::class);
	}

	public function users()
	{
		return $this->belongsToMany(User::class);
	}

	public function givePermissionTo(Permission $permission)
	{
		return $this->permissions()->save($permission);
	}


	public function assignPermission($permission)
	{
		return $this->permissions()->save(
			Permission::whereName($permission)->firstOrFail()
		);
	}

	/**
	 * Get a list of user ids associated with the group
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getUserListAttribute()
	{
		return $this->users->pluck('id')->all();
	}

	/**
	 * Get a list of permission ids associated with the group
	 *
	 * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function getPermissionListAttribute()
	{
		return $this->permissions->pluck('id')->all();
	}
}
