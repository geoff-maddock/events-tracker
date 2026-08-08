<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * A Discord channel that events are reposted into, via an incoming webhook
 * (issue #2058).
 *
 * MATCHING SEMANTICS. An event reaches a target when it passes the base
 * eligibility gate (public, future, not cancelled, not do_not_repost, and —
 * when requires_photo is set — has a primary photo) AND satisfies the
 * target's criteria:
 *
 *   - within one criteria_type, includes are OR'd    (tag A or tag B)
 *   - across criteria_types, they are AND'd          (tag A and venue X)
 *   - any exclude match vetoes, whatever else matched
 *   - no criteria at all matches NOTHING unless match_all is set
 *
 * That last rule is deliberate: a half-configured target should go quiet, not
 * firehose every event on the site into someone's channel.
 *
 * SECURITY. webhook_url is a bearer credential — anyone holding it can post as
 * this channel. It is encrypted at rest, hidden from serialisation, and must
 * never be logged, rendered, or returned by an API. Use maskedUrl() for
 * display. In particular, never pass a DiscordTarget to Activity::log(): that
 * assigns the whole model to activities.changes, which is admin-readable.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $webhook_url
 * @property string|null $webhook_fingerprint
 * @property string|null $webhook_id
 * @property bool $is_enabled
 * @property bool $match_all
 * @property bool $requires_photo
 * @property bool $post_on_create
 * @property bool $post_reminders
 * @property bool $post_digest
 * @property bool $allow_manual
 * @property array<int, int>|null $reminder_offsets
 * @property int $digest_day
 * @property int $digest_hour
 * @property int $digest_window_days
 * @property string|null $mention
 * @property string|null $username
 * @property string|null $avatar_url
 * @property int|null $embed_color
 * @property int $consecutive_failures
 * @property DateTime|null $last_success_at
 * @property DateTime|null $last_failure_at
 * @property string|null $last_error
 * @property DateTime|null $disabled_at
 * @property string|null $disabled_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class DiscordTarget extends Eloquent
{
    use HasFactory;
    use SoftDeletes;

    /** Posting modes a target can opt into. */
    public const MODE_CREATE = 'create';

    public const MODE_REMINDER = 'reminder';

    public const MODE_DIGEST = 'digest';

    public const MODE_MANUAL = 'manual';

    /** Maps a posting mode to the column that opts a target into it. */
    public const MODE_COLUMNS = [
        self::MODE_CREATE => 'post_on_create',
        self::MODE_REMINDER => 'post_reminders',
        self::MODE_DIGEST => 'post_digest',
        self::MODE_MANUAL => 'allow_manual',
    ];

    protected $fillable = [
        'name',
        'slug',
        'webhook_url',
        'is_enabled',
        'match_all',
        'requires_photo',
        'post_on_create',
        'post_reminders',
        'post_digest',
        'allow_manual',
        'reminder_offsets',
        'digest_day',
        'digest_hour',
        'digest_window_days',
        'mention',
        'username',
        'avatar_url',
        'embed_color',
    ];

    /**
     * Defence in depth. The webhook token must not leak through toArray(),
     * toJson(), an API resource, or a var_dump in a log line.
     *
     * @var list<string>
     */
    protected $hidden = [
        'webhook_url',
        'webhook_fingerprint',
    ];

    protected $casts = [
        'webhook_url' => 'encrypted',
        'reminder_offsets' => 'array',
        'is_enabled' => 'boolean',
        'match_all' => 'boolean',
        'requires_photo' => 'boolean',
        'post_on_create' => 'boolean',
        'post_reminders' => 'boolean',
        'post_digest' => 'boolean',
        'allow_manual' => 'boolean',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'disabled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (DiscordTarget $target) {
            // Encryption is non-deterministic, so the URL column can't carry a
            // unique index. Fingerprint it instead, so adding the same channel
            // twice is caught by the database rather than by nobody.
            if ($target->isDirty('webhook_url')) {
                $url = $target->webhook_url;
                $target->webhook_fingerprint = hash('sha256', $url);
                $target->webhook_id = static::extractWebhookId($url);
            }

            if (empty($target->slug)) {
                $target->slug = Str::slug($target->name);
            }

            // created_by / updated_by are deliberately not fillable.
            if (Auth::check()) {
                if (null === $target->created_by) {
                    $target->created_by = Auth::id();
                }
                $target->updated_by = Auth::id();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * The filters deciding which events this target receives.
     *
     * @return HasMany<DiscordTargetCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(DiscordTargetCriterion::class);
    }

    /**
     * The ledger of everything sent to this target.
     *
     * @return HasMany<DiscordPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(DiscordPost::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<DiscordTarget>  $query
     * @return Builder<DiscordTarget>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Targets that are enabled AND opted into the given posting mode.
     *
     * @param  Builder<DiscordTarget>  $query
     * @return Builder<DiscordTarget>
     */
    public function scopeForMode(Builder $query, string $mode): Builder
    {
        $column = self::MODE_COLUMNS[$mode] ?? null;

        if (null === $column) {
            // An unknown mode must match nothing rather than everything.
            return $query->whereRaw('1 = 0');
        }

        return $query->where('is_enabled', true)->where($column, true);
    }

    /**
     * A display-safe rendering of the webhook: keeps the id segment, drops the
     * token. Use this anywhere a URL would otherwise be shown or logged.
     */
    public function maskedUrl(): string
    {
        $id = $this->webhook_id ?: 'unknown';

        return 'https://discord.com/api/webhooks/'.$id.'/'.str_repeat('*', 12);
    }

    /**
     * Reminder offsets in days before start_at, falling back to config.
     *
     * @return array<int, int>
     */
    public function effectiveReminderOffsets(): array
    {
        $offsets = $this->reminder_offsets;

        if (empty($offsets)) {
            /** @var array<int, int> $offsets */
            $offsets = (array) config('discord.default_reminder_offsets', []);
        }

        $offsets = array_map('intval', (array) $offsets);
        $offsets = array_values(array_unique(array_filter($offsets, fn (int $d): bool => $d >= 0)));

        rsort($offsets);

        return $offsets;
    }

    /**
     * Clear the failure streak after anything lands successfully.
     */
    public function recordSuccess(): void
    {
        $this->forceFill([
            'consecutive_failures' => 0,
            'last_success_at' => now(),
            'last_error' => null,
        ])->save();
    }

    /**
     * Only permanent failures count toward auto-disable. Counting transient
     * ones would take every channel offline during a single Discord outage.
     *
     * $immediate is for a webhook that no longer exists (401/403/404) — there
     * is nothing to wait for, so it disables on the first occurrence.
     */
    public function recordFailure(string $error, bool $permanent = false, bool $immediate = false): void
    {
        $attributes = [
            'last_failure_at' => now(),
            'last_error' => Str::limit($error, 250),
        ];

        if ($permanent) {
            $attributes['consecutive_failures'] = $this->consecutive_failures + 1;
        }

        $threshold = (int) config('discord.auto_disable_after_failures', 5);
        $streak = (int) ($attributes['consecutive_failures'] ?? $this->consecutive_failures);

        if ($immediate || ($permanent && $threshold > 0 && $streak >= $threshold)) {
            $attributes['is_enabled'] = false;
            $attributes['disabled_at'] = now();
            $attributes['disabled_reason'] = Str::limit($error, 250);
        }

        $this->forceFill($attributes)->save();
    }

    /**
     * The numeric id segment of a Discord webhook URL, which is safe to
     * display. Returns null for anything that isn't shaped like one.
     */
    public static function extractWebhookId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        return preg_match('#/webhooks/(\d+)/#', $url, $matches) ? $matches[1] : null;
    }
}
