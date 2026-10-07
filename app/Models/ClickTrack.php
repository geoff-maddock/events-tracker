<?php

namespace App\Models;

use App\Helpers\BotDetector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\ClickTrack
 *
 * @property int $id
 * @property int|null $event_id
 * @property int|null $user_id
 * @property int|null $venue_id
 * @property int|null $promoter_id
 * @property string|null $tags
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string|null $ip_address
 * @property string|null $country_code
 * @property string|null $city
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\User|null $user
 */
class ClickTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'venue_id',
        'promoter_id',
        'tags',
        'user_agent',
        'referrer',
        'ip_address',
        'country_code',
        'city',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    /**
     * Why a stored click is left out of entity stats, or null when it counts
     * (#2293). Bots are mostly not recorded at all, but older rows predate the
     * current bot list, so it's applied again here. Keep in step with
     * applyCountable(), which is the same rules in SQL.
     */
    public function exclusionReason(): ?string
    {
        if (empty($this->user_agent)) {
            return 'No user agent';
        }

        if (BotDetector::isBot($this->user_agent)) {
            return 'Bot or AI agent';
        }

        if ($this->event && $this->clicked_at && $this->clicked_at->gte(self::countableUntil($this->event))) {
            return 'Event was over';
        }

        return null;
    }

    /**
     * Clicks on an event's ticket link count until the event ends: its end
     * time or the end of its start day, whichever is later. An end time equal
     * to the start (allowed by the form) doesn't cut off door sales that night.
     */
    public static function countableUntil(Event $event): Carbon
    {
        $endOfStartDay = Carbon::parse($event->start_at)->addDay()->startOfDay();

        return $event->end_at ? Carbon::parse($event->end_at)->max($endOfStartDay) : $endOfStartDay;
    }

    /**
     * Limit a click_tracks query (aliased $alias) to the clicks that count in
     * entity stats: a user agent that isn't a bot, and not after the event ended.
     */
    public static function applyCountable(Builder $query, string $alias = 'click_tracks'): Builder
    {
        $userAgent = "LOWER({$alias}.user_agent)";

        return $query
            ->whereNotNull("{$alias}.user_agent")
            ->where("{$alias}.user_agent", '!=', '')
            ->where(function (Builder $query) use ($userAgent) {
                foreach (BotDetector::patterns() as $pattern) {
                    $query->whereRaw("{$userAgent} NOT LIKE ?", ['%'.$pattern.'%']);
                }
            })
            ->whereNotExists(function (Builder $query) use ($alias) {
                $query->selectRaw('1')
                    ->from('events as past_event')
                    ->whereColumn('past_event.id', "{$alias}.event_id")
                    ->whereRaw("{$alias}.clicked_at >= GREATEST(COALESCE(past_event.end_at, past_event.start_at), DATE_ADD(DATE(past_event.start_at), INTERVAL 1 DAY))");
            });
    }

    /**
     * Get the event that owns this click track.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the venue that owns this click track.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'venue_id');
    }

    /**
     * Get the promoter that owns this click track.
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'promoter_id');
    }

    /**
     * Get the user that owns this click track.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
