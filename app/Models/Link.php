<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Link
 *
 * @property int $id
 * @property string|null $url
 * @property string|null $text
 * @property string|null $image
 * @property string|null $api
 * @property string|null $title
 * @property int $confirm
 * @property int $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property-read int|null $entities_count
 * @property-read mixed $tag
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
     *
     * @var string
     */
    protected $table = 'links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['text', 'url', 'title', 'is_primary'];

    /**
     * Get the entities that belong to the link
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a full link tag
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getTagAttribute()
    {
        return sprintf('<a href="%s" title="%s" target="_">%s</a>', $this->url, $this->title, $this->text);
    }
}
