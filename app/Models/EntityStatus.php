<?php

namespace App\Models;

use App\Models\Concerns\DeletesOnlyWhenUnused;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\Models\EntityStatus.
 *
 * @property int                                                           $id
 * @property string                                                        $name
 * @property \Illuminate\Support\Carbon|null                               $created_at
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EntityStatus extends Eloquent
{
    use DeletesOnlyWhenUnused;

    CONST DRAFT = 1;
    CONST ACTIVE = 2;
    CONST INACTIVE = 3;
    CONST UNLISTED = 4;

    protected $fillable = [
        'name',
    ];

    /**
     * Validation rules limiting which status the user may give an entity.
     * Unlisted hides the entity from everyone but admins, so only they set it.
     *
     * @return array<int, \Illuminate\Validation\Rules\NotIn>
     */
    public static function assignableRule(?User $user): array
    {
        return $user?->isAdmin() ? [] : [\Illuminate\Validation\Rule::notIn([self::UNLISTED])];
    }

    /**
     * The statuses the user may choose in the entity form, as id => name.
     *
     * @return array<int, string>
     */
    public static function formOptionsFor(?User $user): array
    {
        return self::orderBy('name', 'ASC')
            ->when(!$user?->isAdmin(), fn ($query) => $query->whereKeyNot(self::UNLISTED))
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * An event status can have many events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entities()
    {
        return $this->hasMany('App\Models\Entity');
    }

    /**
     * Returns the display class related to the status.
     */
    public function getDisplayClass(): string
    {
        $class = '';
        switch ($this->name) {
            case 'Draft':
                $class = 'warning';
                break;
            case 'Inactive':
                $class = 'muted';
                break;
            default:
                $class = 'primary';
        }

        return $class;
    }

    /**
     * @return array<int, \Illuminate\Database\Query\Builder>
     */
    protected function usedBy(): array
    {
        return [$this->referencedIn('entities', 'entity_status_id')];
    }
}
