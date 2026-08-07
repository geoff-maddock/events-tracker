<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * App\Models\SurveyCampaign.
 *
 * A named set of questions plus the rules for who gets asked. Campaigns are
 * seeded rows keyed by slug, so adding a new one (issue #1998 also asks for
 * "why did you skip", interest polls and promoter outreach) is a seeder block
 * rather than a migration.
 *
 * @property int                             $id
 * @property string                          $slug
 * @property string                          $name
 * @property string|null                     $intro
 * @property string|null                     $subject_type
 * @property bool                            $is_active
 * @property int                             $priority
 * @property int|null                        $expires_after_days
 * @property int                             $default_visibility_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyQuestion>   $questions
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyInvitation> $invitations
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyResponse>   $responses
 *
 * @method static Builder|SurveyCampaign active()
 * @method static Builder|SurveyCampaign newModelQuery()
 * @method static Builder|SurveyCampaign newQuery()
 * @method static Builder|SurveyCampaign query()
 *
 * @mixin \Eloquent
 */
class SurveyCampaign extends Eloquent
{
    use HasFactory;

    /**
     * Slug of the post-event attendee campaign.
     *
     * Always reference campaigns by slug — seeded ids are not stable across
     * environments.
     */
    public const POST_EVENT_ATTENDEE = 'post-event-attendee';

    protected $fillable = [
        'slug', 'name', 'intro', 'subject_type', 'is_active', 'priority',
        'expires_after_days', 'default_visibility_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'expires_after_days' => 'integer',
        'default_visibility_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The questions asked by this campaign, in display order.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort');
    }

    /**
     * Only the questions currently being asked.
     */
    public function activeQuestions(): HasMany
    {
        return $this->questions()->where('is_active', true);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SurveyInvitation::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * @param  Builder<SurveyCampaign>  $query
     * @return Builder<SurveyCampaign>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Look up a campaign by its slug. Campaign rows are read-mostly and hit on
     * every generation run, so they are worth a short cache.
     */
    public static function bySlug(string $slug): ?self
    {
        try {
            /** @var SurveyCampaign|null $campaign */
            $campaign = Cache::remember(
                'survey.campaign.'.$slug,
                3600,
                fn () => static::where('slug', $slug)->first()
            );

            return $campaign;
        } catch (Throwable $e) {
            // The cache is an optimization over a single indexed row read —
            // an unwritable cache directory must not take the feature down.
            Log::warning('Survey campaign cache unavailable, falling back to a direct query.', [
                'slug' => $slug,
                'exception' => $e->getMessage(),
            ]);

            return static::where('slug', $slug)->first();
        }
    }

    /**
     * Drop the cached copy of this campaign. Called by the seeder so a re-seed
     * is immediately visible.
     */
    public static function forgetCached(string $slug): void
    {
        try {
            Cache::forget('survey.campaign.'.$slug);
        } catch (Throwable $e) {
            // Nothing to invalidate if the cache is unavailable.
        }
    }

    /**
     * How long an invitation for this campaign stays actionable.
     */
    public function expiresAfterDays(): ?int
    {
        return $this->expires_after_days;
    }
}
