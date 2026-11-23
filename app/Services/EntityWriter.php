<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The one place an entity is created or changed, for the web form and the API
 * alike (#2407). Controllers keep authorization, the request and the response;
 * what a write does to the entity, its tags, aliases and roles is here.
 *
 * Input is the validated request data. Role lists hold ids; tag and alias
 * lists hold ids or the names of ones to create. Who owns an entity is not
 * part of a write: that is Entity::syncOwners().
 */
class EntityWriter
{
    /** @var array<int, string> */
    private array $added = [];

    /**
     * @param array<string, mixed>   $input
     * @param array<int, int|string> $tags
     * @param array<int, int|string> $aliases
     * @param array<int, int|string> $roles
     */
    public function create(User $user, array $input, array $tags = [], array $aliases = [], array $roles = []): Entity
    {
        $input['slug'] = Str::slug($input['slug'] ?? '-');
        // records who added the entity
        $input['created_by'] = $user->id;

        $entity = new Entity($input);
        $entity->updated_by = $user->id;
        $entity->save();

        $entity->tags()->attach($this->tagIds($tags, $user));
        $entity->aliases()->attach($this->aliasIds($aliases));
        $entity->roles()->attach($roles);

        return $this->finish($entity, $user, Action::CREATE);
    }

    /**
     * A minimal active venue or promoter from a name alone, for the event form's
     * inline "add a missing venue / promoter". The slug is made unique.
     *
     * @param string $role 'Venue' or 'Promoter'
     */
    public function quickCreate(User $user, string $name, string $role, ?string $short = null, ?string $description = null): Entity
    {
        // a name with nothing sluggable in it (all punctuation) still gets a slug
        $baseSlug = Str::slug($name) ?: 'entity';
        $slug = $baseSlug;
        $i = 1;
        // unlisted entities hold their slugs too
        while (Entity::withUnlisted()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i++;
        }

        return $this->create($user, [
            'name' => $name,
            'slug' => $slug,
            'short' => $short ?? $name,
            'description' => $description ?? $name,
            'entity_type_id' => $role === 'Venue' ? EntityType::SPACE : EntityType::GROUP,
            'entity_status_id' => EntityStatus::ACTIVE,
            'started_at' => now(),
        ], roles: Role::where('name', $role)->pluck('id')->all());
    }

    /**
     * Change the entity's own fields to the input, and its tags, aliases and
     * roles to exactly the lists given.
     *
     * @param array<string, mixed>   $input
     * @param array<int, int|string> $tags
     * @param array<int, int|string> $aliases
     * @param array<int, int|string> $roles
     */
    public function replace(Entity $entity, User $user, array $input, array $tags = [], array $aliases = [], array $roles = []): Entity
    {
        return $this->patch($entity, $user, $input, $tags, $aliases, $roles);
    }

    /**
     * Change only what is given: fields present in the input, and the tag,
     * alias and role lists when they are not null.
     *
     * @param array<string, mixed>        $input
     * @param array<int, int|string>|null $tags
     * @param array<int, int|string>|null $aliases
     * @param array<int, int|string>|null $roles
     */
    public function patch(Entity $entity, User $user, array $input, ?array $tags = null, ?array $aliases = null, ?array $roles = null): Entity
    {
        // created_by records who added the entity and is never reassigned
        unset($input['created_by']);
        $input = array_intersect_key($input, array_flip($entity->getFillable()));

        if (array_key_exists('slug', $input)) {
            $input['slug'] = Str::slug($input['slug'] ?? '-');
        }

        if ($input !== []) {
            $entity->fill($input);
            $entity->updated_by = $user->id;
            $entity->save();
        }

        if ($tags !== null) {
            $entity->tags()->sync($this->tagIds($tags, $user));
        }

        if ($aliases !== null) {
            $entity->aliases()->sync($this->aliasIds($aliases));
        }

        if ($roles !== null) {
            $entity->roles()->sync($roles);
        }

        return $this->finish($entity, $user, Action::UPDATE);
    }

    /**
     * The tags and aliases the writes so far had to create, as "tag X" /
     * "alias Y", for a confirmation message.
     *
     * @return array<int, string>
     */
    public function added(): array
    {
        return $this->added;
    }

    private function finish(Entity $entity, User $user, int $action): Entity
    {
        Activity::log($entity, $user, $action);

        // roles change after the save, so the cached option lists are dropped again here
        OptionLists::forgetEntities();

        return $entity;
    }

    /**
     * @param  array<int, int|string> $tags
     * @return array<int, int>
     */
    private function tagIds(array $tags, User $user): array
    {
        $resolved = Tag::resolveList($tags, $user);
        foreach ($resolved->filter(fn (Tag $tag) => $tag->wasRecentlyCreated) as $tag) {
            $this->added[] = 'tag '.$tag->name;
        }

        return $resolved->modelKeys();
    }

    /**
     * Ids and names both resolve to an alias; only an unknown name creates one.
     *
     * @param  array<int, int|string> $aliases
     * @return array<int, int>
     */
    private function aliasIds(array $aliases): array
    {
        $resolved = Alias::resolveList($aliases);
        foreach ($resolved->filter(fn (Alias $alias) => $alias->wasRecentlyCreated) as $alias) {
            $this->added[] = 'alias '.$alias->name;
        }

        return $resolved->modelKeys();
    }
}
