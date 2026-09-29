<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Link.
 *
 * @property int                                                           $id
 * @property string|null                                                   $url
 * @property string|null                                                   $text
 * @property string|null                                                   $image
 * @property string|null                                                   $api
 * @property string|null                                                   $title
 * @property int                                                           $confirm
 * @property int                                                           $is_primary
 * @property \Illuminate\Support\Carbon|null                               $created_at
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property mixed                                                         $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Link newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Link newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Link query()
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereApi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereConfirm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Link whereUrl($value)
 * @mixin \Eloquent
 */
class Link extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     */
    protected $table = 'links';

    protected $fillable = ['text', 'url', 'title', 'is_primary'];

    /**
     * Trim a link URL and give a scheme-less one ("www.example.com",
     * "//example.com") https://, so what's stored is an absolute URL (#2220).
     * A value that already has a scheme is returned as-is for validation to judge.
     */
    public static function normalizeUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }

        $url = trim($url);

        // "scheme:" (but not "host:port") means the URL already names its scheme
        if ('' === $url || preg_match('#^[a-z][a-z0-9+.-]*:(?!\d)#i', $url)) {
            return $url;
        }

        return str_starts_with($url, '//') ? 'https:'.$url : 'https://'.$url;
    }

    public function setUrlAttribute(?string $value): void
    {
        $this->attributes['url'] = self::normalizeUrl($value);
    }

    /**
     * Whether a URL is fit to be a link target: an http(s) scheme, a host, and
     * no control characters (#2220). Deliberately looser than FILTER_VALIDATE_URL,
     * which rejects real links such as a Discogs URL with "ö" in the path or a
     * host with an underscore; Blade escapes the rest when it's rendered.
     */
    public static function isWebUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ('' === $url || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        return false !== $parts
            && in_array($scheme, ['http', 'https'], true)
            && '' !== $host
            && !preg_match('/\s/u', $host);
    }

    /**
     * The URL for use as a link target: null unless isWebUrl(), so a stored
     * value with any other scheme is never rendered as an href (#2220).
     * Mirrors Location::safeMapUrl().
     */
    public function safeUrl(): ?string
    {
        $url = trim((string) $this->url);

        return self::isWebUrl($url) ? $url : null;
    }

    /**
     * Get the entities that belong to the link.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }
}
