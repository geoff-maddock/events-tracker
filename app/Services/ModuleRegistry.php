<?php

namespace App\Services;

use App\Models\User;

/**
 * Resolves the site's top-level "modules" (pages/features) from config/modules.php,
 * filtered by what a given user is allowed to see, and provides a lightweight
 * keyword search over their titles and descriptions.
 *
 * @phpstan-type Module array{name:string, url:string, icon:string, description:string}
 */
class ModuleRegistry
{
    /**
     * Modules the given user is permitted to see:
     *   - public + policy: everyone
     *   - auth: any authenticated user
     *   - admin: users in the "admin" group
     *
     * @return list<array{name:string, url:string, icon:string, description:string}>
     */
    public function visibleTo(?User $user): array
    {
        $modules = array_merge(
            config('modules.public', []),
            config('modules.policy', []),
        );

        if ($user !== null) {
            $modules = array_merge($modules, config('modules.auth', []));

            if ($user->hasGroup('admin')) {
                $modules = array_merge($modules, config('modules.admin', []));
            }
        }

        return array_values($modules);
    }

    /**
     * Case-insensitive search over the visible modules' name and description.
     * Empty keyword returns no results.
     *
     * @return list<array{name:string, url:string, icon:string, description:string}>
     */
    public function search(string $keyword, ?User $user): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $matches = array_filter($this->visibleTo($user), function (array $module) use ($keyword) {
            return stripos($module['name'], $keyword) !== false
                || stripos($module['description'], $keyword) !== false;
        });

        return array_values($matches);
    }
}
