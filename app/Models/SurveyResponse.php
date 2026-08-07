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
 * Two independent gates control whether a response could ever be shown on the
 * site: `visibility_id` is the submitter's consent, `display_status` is the
 * admin's moderation decision. Both must agree — see scopeDisplayable(). An
 * admin approval never overrides a private response, and a user sharing
 * publicly never skips moderation.
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
 * @property string                          $display_status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null                        $approved_by
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\SurveyCampaign         $campaign
 * @property \App\Models\SurveyInvitation|null  $invitation
 * @property \App\Models\User|null              $user
 * @property \App\Models\User|null              $approver
 * @property \App\Models\Visibility|null        $visibility
 * @property \Illuminate\Database\Eloquent\Model|null $subject
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAnswer> $answers
 *
 * @method static Builder|SurveyResponse displayable()
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

    /**
     * Awaiting an admin decision — the state every response is created in.
     */
    public const DISPLAY_PENDING = 'pending';

    /**
     * An admin cleared this for display on the site.
     */
    public const DISPLAY_APPROVED = 'approved';

    /**
     * An admin decided this should never be shown.
     */
    public const DISPLAY_REJECTED = 'rejected';

    /**
     * @var array<int, string>
     */
    public const DISPLAY_STATUSES = [
        self::DISPLAY_PENDING,
        self::DISPLAY_APPROVED,
        self::DISPLAY_REJECTED,
    ];

    protected $fillable = [
        'survey_invitation_id', 'survey_campaign_id', 'user_id',
        'subject_type', 'subject_id', 'visibility_id', 'submitted_at',
        'display_status', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'visibility_id' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'display_status' => self::DISPLAY_PENDING,
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

    /**
     * The admin who last set display_status to approved.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    /**
     * Responses that may actually be rendered on the site: the submitter
     * shared them AND an admin approved them.
     *
     * This is the only query any future public display should use — neither
     * public() nor approved alone is sufficient.
     *
     * @param  Builder<SurveyResponse>  $query
     * @return Builder<SurveyResponse>
     */
    public function scopeDisplayable(Builder $query): Builder
    {
        return $query
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->where('display_status', self::DISPLAY_APPROVED);
    }

    public function isPublic(): bool
    {
        return $this->visibility_id === Visibility::VISIBILITY_PUBLIC;
    }

    public function isApproved(): bool
    {
        return $this->display_status === self::DISPLAY_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->display_status === self::DISPLAY_REJECTED;
    }

    /**
     * Whether this response would be shown if a public display existed.
     *
     * The row-level counterpart to scopeDisplayable(); keep the two in step.
     */
    public function isDisplayable(): bool
    {
        return $this->isPublic() && $this->isApproved();
    }

    /**
     * Approved by an admin, but the submitter kept it private — so it still
     * will not be shown. Worth surfacing in the admin UI, since the approval
     * looks like it did something.
     */
    public function isApprovedButNotShared(): bool
    {
        return $this->isApproved() && ! $this->isPublic();
    }

    /**
     * Record an admin's display decision, keeping the approval audit columns
     * consistent with the status.
     */
    public function setDisplayStatus(string $status, ?User $admin = null): void
    {
        $this->display_status = $status;

        if ($status === self::DISPLAY_APPROVED) {
            $this->approved_at = now();
            $this->approved_by = $admin?->id;
        } else {
            // Rejecting or resetting to pending clears the audit trail, so a
            // stale "approved by X" never sits next to a non-approved row.
            $this->approved_at = null;
            $this->approved_by = null;
        }
    }

    public function hasSubject(): bool
    {
        return $this->subject_type !== '' && $this->subject_id > 0;
    }
}
