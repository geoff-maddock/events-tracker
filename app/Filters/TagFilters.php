<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TagFilters extends QueryFilter
{
    public function id(?string $value = null): Builder
    {
        $values = array_filter(explode(',', (string) $value));

        if (count($values) > 1) {
            return $this->builder->whereIn('tags.id', $values);
        }

        if (isset($value)) {
            return $this->builder->where('tags.id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('tags.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function description(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('tags.description', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function tag_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('tagType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    /**
     * Narrow to the tags the signed-in user follows or created (or doesn't); a no-op for guests.
     */
    public function display_type(?string $value = null): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $this->builder;
        }

        $followedByUser = fn ($q) => $q->where('follows.object_type', '=', 'tag')
            ->where('follows.user_id', '=', $user->id);

        return match ($value) {
            'following' => $this->builder->whereHas('follows', $followedByUser),
            'not_following' => $this->builder->whereDoesntHave('follows', $followedByUser),
            'created' => $this->builder->where('tags.created_by', '=', $user->id),
            // older tags have no creator, and those weren't created by this user either
            'not_created' => $this->builder->where(fn ($q) => $q->whereNull('tags.created_by')
                ->orWhere('tags.created_by', '!=', $user->id)),
            default => $this->builder,
        };
    }
}
