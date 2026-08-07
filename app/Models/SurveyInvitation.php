<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * App\Models\SurveyInvitation.
 *
 * A request for one user to answer one campaign, optionally about one subject.
 * Generated in batches by `feedback:generate`; most never become a response.
 *
 * Routed by `token` rather than id so the handle is unguessable and directly
 * reusable by a future emailed feedback link.
 *
 * @property int                             $id
 * @property int                             $survey_campaign_id
 * @property int                             $user_id
 * @property string                          $subject_type
 * @property int                             $subject_id
 * @property string                          $token
 * @property string                          $status
 * @property string                          $source
 * @property \Illuminate\Support\Carbon|null $shown_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $dismissed_at
 * @property \Illuminate\Support\Carbon|null $snoozed_until
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\SurveyCampaign      $campaign
 * @property \App\Models\User|null           $user
 * @property \Illuminate\Database\Eloquent\Model|null $subject
 * @property \App\Models\SurveyResponse|null $response
 *
 * @method static Builder|SurveyInvitation actionable()
 * @method static Builder|SurveyInvitation newModelQuery()
 * @method static Builder|SurveyInvitation newQuery()
 * @method static Builder|SurveyInvitation query()
 *
 * @mixin \Eloquent
 */
class SurveyInvitation extends Eloquent
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SHOWN = 'shown';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_EXPIRED = 'expired';

    /**
     * Statuses that can still lead to a response.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_SHOWN];

    public const SOURCE_APP = 'app';

    public const SOURCE_EMAIL = 'email';

    protected $fillable = [
        'survey_campaign_id', 'user_id', 'subject_type', 'subject_id',
        'token', 'status', 'source', 'shown_at', 'completed_at',
        'dismissed_at', 'snoozed_until', 'expires_at',
    ];

    protected $casts = [
        'shown_at' => 'datetime',
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Mint a token for any invitation created without one.
        static::creating(function (SurveyInvitation $invitation): void {
            if (empty($invitation->token)) {
                $invitation->token = static::generateToken();
            }
        });

        // The prompt lookup is cached per user on every authenticated page
        // load; any write to an invitation has to drop that cache or a
        // dismissed prompt keeps reappearing for up to the TTL.
        static::saved(fn (SurveyInvitation $invitation) => static::forgetPendingCache($invitation->user_id));
        static::deleted(fn (SurveyInvitation $invitation) => static::forgetPendingCache($invitation->user_id));
    }

    /**
     * Bind route parameters by token, never by id.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public static function generateToken(): string
    {
        return Str::random(40);
    }

    /**
     * Cache key holding the id of a user's current prompt (or 0 for none).
     */
    public static function pendingCacheKey(int $userId): string
    {
        return 'feedback.pending.'.$userId;
    }

    public static function forgetPendingCache(int $userId): void
    {
        try {
            Cache::forget(static::pendingCacheKey($userId));
        } catch (\Throwable $e) {
            // Nothing to invalidate if the cache is unavailable. Must not throw
            // — this runs from model save/delete hooks on the write path.
        }
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SurveyCampaign::class, 'survey_campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What the invitation is about — an Event today, an Entity or nothing for
     * campaigns added later. Uses the singular lowercase morph map registered
     * in AppServiceProvider.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(SurveyResponse::class);
    }

    /**
     * Invitations that can still be shown and answered.
     *
     * @param  Builder<SurveyInvitation>  $query
     * @return Builder<SurveyInvitation>
     */
    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    /**
     * Whether this invitation can still be shown and answered right now.
     */
    public function isActionable(): bool
    {
        if (! in_array($this->status, self::OPEN_STATUSES, true)) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->snoozed_until !== null && $this->snoozed_until->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * True when the invitation has no subject (a general poll).
     */
    public function hasSubject(): bool
    {
        return $this->subject_type !== '' && $this->subject_id > 0;
    }

    public function markShown(): void
    {
        // Only the first view sets shown_at; status stays 'shown' thereafter.
        if ($this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_SHOWN;
            $this->shown_at = now();
            $this->save();
        }
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    public function markDismissed(): void
    {
        $this->status = self::STATUS_DISMISSED;
        $this->dismissed_at = now();
        $this->save();
    }
}
