<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $fillable = ['name','label','description','level'];
    
	public function permissions()
	{
		return $this->belongsToMany(Permission::class);
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
	
}
