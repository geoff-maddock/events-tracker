<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An address we must not email again.
 *
 * @property int         $id
 * @property string      $email
 * @property string      $reason
 * @property string      $source
 * @property string|null $code
 * @property string|null $error
 * @property mixed       $suppressed_at
 */
class EmailSuppression extends Model
{
    use HasFactory;

    public const REASON_BOUNCE = 'bounce';

    public const REASON_COMPLAINT = 'complaint';

    public const REASON_UNSUBSCRIBE = 'unsubscribe';

    public const REASON_MANUAL = 'manual';

    protected $fillable = ['email', 'reason', 'source', 'code', 'error', 'suppressed_at'];

    protected $casts = [
        'suppressed_at' => 'datetime',
    ];

    /**
     * The single definition of "the same address". Everything that writes or
     * reads this table goes through here so the unique index and the send-time
     * lookup cannot disagree.
     */
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Is this address suppressed? One indexed lookup — deliberately not
     * memoised, because the queue worker is long-lived and a cached "not
     * suppressed" would outlive the bounce that changed the answer.
     */
    public static function isSuppressed(string $email): bool
    {
        return static::query()->where('email', static::normalize($email))->exists();
    }

    /**
     * Suppress an address, keeping the earliest known reason rather than
     * letting a later import overwrite why it was first blocked.
     *
     * @param array<string, mixed> $attributes
     */
    public static function suppress(string $email, string $reason, string $source, array $attributes = []): self
    {
        return static::query()->firstOrCreate(
            ['email' => static::normalize($email)],
            array_merge(['reason' => $reason, 'source' => $source], $attributes)
        );
    }

    /** @param Builder<EmailSuppression> $query */
    public function scopeReason(Builder $query, string $reason): void
    {
        $query->where('reason', $reason);
    }
}
