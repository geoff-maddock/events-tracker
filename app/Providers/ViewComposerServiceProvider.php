<?php

namespace App\Providers;

use App\Models\Forum;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->composeNavigation();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Compose navigation bar.
     */
    private function composeNavigation(): void
    {
        view()->composer('partials.nav', function ($view) {
            $view->with('roles', Cache::remember('roles', 3600, function () {
                return Role::orderBy('name', 'ASC')->get();
            }));
            $view->with('hasForum', Cache::remember('hasForum', 3600, function () {
                return Forum::latest()->count();
            }));
            $view->with('menus', Cache::remember('menus', 3600, function () {
                return Menu::orderBy('name', 'ASC')->visible()->get();
            }));
        });
    }
}
