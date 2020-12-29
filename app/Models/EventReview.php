<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;

//use App\Http\Controllers\UploadedFile;

/**
 * App\Models\EventReview
 *
 * @property int $event_id
 * @property int $user_id
 * @property int $review_type_id
 * @property int $attended
 * @property int|null $confirmed
 * @property int $expectation
 * @property int $rating
 * @property string $review
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\ReviewType $reviewType
 * @property-read \App\Models\User|null $user
 * @method static Builder|EventReview future()
 * @method static Builder|EventReview newModelQuery()
 * @method static Builder|EventReview newQuery()
 * @method static Builder|EventReview past()
 * @method static Builder|EventReview query()
 * @method static Builder|EventReview whereAttended($value)
 * @method static Builder|EventReview whereConfirmed($value)
 * @method static Builder|EventReview whereCreatedAt($value)
 * @method static Builder|EventReview whereEventId($value)
 * @method static Builder|EventReview whereExpectation($value)
 * @method static Builder|EventReview whereRating($value)
 * @method static Builder|EventReview whereReview($value)
 * @method static Builder|EventReview whereReviewTypeId($value)
 * @method static Builder|EventReview whereUpdatedAt($value)
 * @method static Builder|EventReview whereUserId($value)
 * @mixin \Eloquent
 */
class EventReview extends Eloquent
{
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
        'event_id', 'user_id', 'review_type_id', 'attended', 'confirmed', 'expectation', 'rating', 'review'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the event that the review belongs to
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }

    /**
     * Get the user that the response belongs to
     *
     */
    public function user()
    {
        return $this->hasOne('App\Models\User');
    }

    /**
     * An review is created by one user
     *
     * @ param User $user
     *
     * @ return boolean
     */
    public function ownedBy(User $user)
    {
        return $this->user_id == $user->id;
    }

    /**
     * Get the response type that the response belongs to
     *
     */
    public function reviewType()
    {
        return $this->belongsTo('App\Models\ReviewType');
        ;
    }

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'asc');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'desc');
    }
}
