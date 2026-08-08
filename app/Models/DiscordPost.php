<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Discord post ledger (issue #2058).
 *
 * This is the idempotency mechanism, not merely a report. Every send claims a
 * row first, under the unique index on
 * (discord_target_id, event_id, reason, reason_key) — so a listener and a
 * scheduled command firing at the same instant cannot both post, and a job
 * retried after a partial success cannot double-post.
 *
 * reason_key is what distinguishes legitimate repeats of the same reason:
 *
 *   create    ''                     one announcement per event per target
 *   reminder  '7'                    one per offset per event per target
 *   digest    '2026-W32'             one per ISO week per target (event_id 0)
 *   manual    '3:202608071230'       user + minute, so a deliberate repost works
 *   test      ''                     one connection test row per target
 *
 * @property int $id
 * @property int $discord_target_id
 * @property int $event_id
 * @property string $reason
 * @property string $reason_key
 * @property string $status
 * @property string|null $message_id
 * @property string|null $channel_id
 * @property string|null $payload_hash
 * @property int $attempts
 * @property string|null $error
 * @property DateTime|null $posted_at
 * @property int|null $created_by
 */
class DiscordPost extends Eloquent
{
    use HasFactory;

    public const REASON_CREATE = 'create';

    public const REASON_REMINDER = 'reminder';

    public const REASON_DIGEST = 'digest';

    public const REASON_MANUAL = 'manual';

    public const REASON_TEST = 'test';

    /** @var array<int, string> */
    public const REASONS = [
        self::REASON_CREATE,
        self::REASON_REMINDER,
        self::REASON_DIGEST,
        self::REASON_MANUAL,
        self::REASON_TEST,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /** Nothing to send — recorded so the attempt isn't retried every run. */
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'discord_target_id',
        'event_id',
        'reason',
        'reason_key',
        'status',
        'message_id',
        'channel_id',
        'payload_hash',
        'attempts',
        'error',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'attempts' => 'integer',
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<DiscordTarget, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(DiscordTarget::class, 'discord_target_id');
    }

    /**
     * Null for digest and test rows, which carry event_id 0 rather than NULL
     * so the dedupe index keeps working.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSent(): bool
    {
        return self::STATUS_SENT === $this->status;
    }

    /**
     * @param  Builder<DiscordPost>  $query
     * @return Builder<DiscordPost>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }
}
