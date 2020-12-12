<?php

namespace App\Models;

use Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

//use App\Http\Controllers\UploadedFile;

class EventResponse extends Eloquent
{
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
        'event_id', 'user_id', 'response_type_id'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the event that the response belongs to
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events()
    {
        return $this->belongsToMany('App\Models\Event')->withTimestamps();
    }

    /**
     * Get the user that the response belongs to
     *
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User')->withTimestamps();
    }

    /**
     * Get the response type that the response belongs to
     *
     */
    public function responseType()
    {
        return $this->belongsTo('App\Models\ResponseType');
        ;
    }
}
