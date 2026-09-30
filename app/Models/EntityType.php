<?php

namespace App\Models;

use App\Models\Concerns\DeletesOnlyWhenUnused;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property string $name
 */
class EntityType extends Eloquent
{
    use DeletesOnlyWhenUnused;

    const SPACE = 1;

    const GROUP = 2;

    const INDIVIDUAL = 3;

    const INTEREST = 4;

    protected $fillable = [
        'name', 'slug', 'short',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /**
     * @return array<int, \Illuminate\Database\Query\Builder>
     */
    protected function usedBy(): array
    {
        return [$this->referencedIn('entities', 'entity_type_id')];
    }
}
