<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 */
class Alias extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the entities that belong to the alias.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }

    /**
     * Resolve a submitted alias list (ids and/or names) to aliases.
     *
     * A whole number is an alias id when one exists with it. Anything else is a
     * name: an alias already called that (ignoring case) is reused, and only a
     * name nothing matches is created. Created aliases carry wasRecentlyCreated
     * so callers can report them. Repeats collapse and blanks are ignored.
     *
     * @param  iterable<int|string|null> $list
     * @return EloquentCollection<int, Alias>
     */
    public static function resolveList(iterable $list): EloquentCollection
    {
        $resolved = new EloquentCollection();

        foreach ($list as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $item = trim((string) $item);
            if ('' === $item) {
                continue;
            }

            // only a string of digits can be an id: "808 State" is a name
            $alias = ctype_digit($item) ? static::find((int) $item) : null;
            $alias ??= static::whereRaw('LOWER(name) = ?', [mb_strtolower($item)])->orderBy('id')->first();
            $alias ??= static::create(['name' => ucwords(strtolower($item))]);

            if (!$resolved->has($alias->id)) {
                $resolved->put($alias->id, $alias);
            }
        }

        return $resolved->values();
    }
}
