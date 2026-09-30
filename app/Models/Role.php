<?php

namespace App\Models;

use App\Models\Concerns\DeletesOnlyWhenUnused;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Eloquent
{
    use HasFactory, DeletesOnlyWhenUnused;

    protected $fillable = [
        'name', 'slug', 'short',
    ];

    /**
     * roles.short is NOT NULL without a default, but the forms and the API treat it as
     * optional; store a missing short as '' rather than failing the insert.
     */
    public function setShortAttribute(?string $value): void
    {
        $this->attributes['short'] = $value ?? '';
    }

    protected $appends = ['plural'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /**
     * Get the entities that belong to the role.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get the plural version of the role.
     */
    public function getPluralAttribute(): string
    {
        return ucfirst(strtolower($this->name.'s'));
    }

    /**
     * Convert all role names to ucfirst.
     */
    public function getNameAttribute(string $value): string
    {
        return ucfirst(strtolower($value));
    }

    /**
     * @return array<int, \Illuminate\Database\Query\Builder>
     */
    protected function usedBy(): array
    {
        return [$this->referencedIn('entity_role', 'role_id')];
    }
}
