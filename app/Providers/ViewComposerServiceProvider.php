<?php

namespace App\Providers;

use App\Models\Event;
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
     * Compose navigation bar
     */
    private function composeNavigation()
    {
        view()->composer('partials.nav', function ($view) {
            $view->with('latest', Event::latest()->first());
            $view->with('roles', Role::orderBy('name', 'ASC')->get());
            $view->with('menus', Menu::orderBy('name', 'ASC')->visible(auth()->user())->get());
        });
    }
}
