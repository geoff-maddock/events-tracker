<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Forum;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\ServiceProvider;

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
            $view->with('roles', Role::orderBy('name', 'ASC')->get());
            $view->with('hasForum', Forum::latest()->count());
            $view->with('menus', Menu::orderBy('name', 'ASC')->visible()->get());
        });
    }
}
