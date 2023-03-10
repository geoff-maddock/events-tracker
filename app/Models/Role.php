<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'short',
    ];

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
}
