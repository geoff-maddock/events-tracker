<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    protected $fillable = ['name','label','description','level'];

	public function groups()
	{
		return $this->belongsToMany(Group::class);
	}
}
