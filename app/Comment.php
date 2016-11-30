<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model {

/**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'comments';
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['message', 'commentable_id','commentable_type'];



    /**
     * Get all of the owning commentable models.
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Get the author of the comment
     */
    public function author()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

  /**
   * Returns entities created by the user
   *
   * @ param User $user
   * 
   */
  public function scopeCreatedBy($query, User $user)
  {
    return $query->where('created_by', '=', $user ? $user->id : NULL);
  }
}
