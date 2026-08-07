<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\SurveyResponse.
 *
 * One completed submission. Private by default — `visibility_id` defaults to
 * Visibility::VISIBILITY_PRIVATE and only flips to public when the user ticks
 * the share box (issue #1998).
 *
 * Campaign and subject are denormalized off the invitation so that admin
 * listing, filtering, export and public display never join invitations.
 *
 * @property int                             $id
 * @property int|null                        $survey_invitation_id
 * @property int                             $survey_campaign_id
 * @property int                             $user_id
 * @property string                          $subject_type
 * @property int                             $subject_id
 * @property int                             $visibility_id
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\SurveyCampaign         $campaign
 * @property \App\Models\SurveyInvitation|null  $invitation
 * @property \App\Models\User|null              $user
 * @property \App\Models\Visibility|null        $visibility
 * @property \Illuminate\Database\Eloquent\Model|null $subject
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAnswer> $answers
 *
 * @method static Builder|SurveyResponse newModelQuery()
 * @method static Builder|SurveyResponse newQuery()
 * @method static Builder|SurveyResponse public()
 * @method static Builder|SurveyResponse query()
 *
 * @mixin \Eloquent
 */
class SurveyResponse extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'survey_invitation_id', 'survey_campaign_id', 'user_id',
        'subject_type', 'subject_id', 'visibility_id', 'submitted_at',
    ];

    protected $casts = [
        'visibility_id' => 'integer',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SurveyCampaign::class, 'survey_campaign_id');
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(SurveyInvitation::class, 'survey_invitation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visibility(): BelongsTo
    {
        return $this->belongsTo(Visibility::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * Responses the submitting user chose to share publicly.
     *
     * @param  Builder<SurveyResponse>  $query
     * @return Builder<SurveyResponse>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility_id', Visibility::VISIBILITY_PUBLIC);
    }

    public function isPublic(): bool
    {
        return $this->visibility_id === Visibility::VISIBILITY_PUBLIC;
    }

    public function hasSubject(): bool
    {
        return $this->subject_type !== '' && $this->subject_id > 0;
    }
}
