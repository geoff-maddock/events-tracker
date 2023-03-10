<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ReviewType extends Eloquent
{
    protected $fillable = [
        'name',
    ];

    /**
     * A review type can belong to many event reviews.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventReviews()
    {
        return $this->belongsTo(EventReview::class);
    }
}
