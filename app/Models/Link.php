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
     * Get the entities that belong to the link.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a full link tag.
     */
    public function getTagAttribute(): string
    {
        return sprintf('<a href="%s" title="%s" target="_">%s</a>', $this->url, $this->title, $this->text);
    }
}
