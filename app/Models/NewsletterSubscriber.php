<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\NewsletterSubscriber.
 *
 * @property int                             $id
 * @property string                          $email
 * @property string                          $token
 * @property ?string                         $source
 * @property ?\Illuminate\Support\Carbon     $confirmed_at
 * @property ?\Illuminate\Support\Carbon     $unsubscribed_at
 * @property ?\Illuminate\Support\Carbon     $created_at
 * @property ?\Illuminate\Support\Carbon     $updated_at
 */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'source',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Only subscribers who have confirmed and not unsubscribed.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
