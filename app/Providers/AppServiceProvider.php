<?php

namespace App\Providers;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\SessionStore\ListParameterStore;
use Illuminate\Session\Store;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Relation::morphMap([
            'entity' => Entity::class,
            'events' => Event::class,
            'tags' => Tag::class,
            'post' => Post::class,
            'thread' => Thread::class,
        ]);

        // get the user, set the theme and pass to the view
        View::composer('*', function ($view) {
            if (Auth::check() && Auth::user()->profile && Auth::user()->profile->default_theme) {
                // use the set theme if the user has one
                $theme = Auth::user()->profile->default_theme;
            } else {
                $theme = config('app.default_theme');
            }
            $view->with('theme', $theme);
        });

        View::composer('series.createOccurrence', function ($view) {
            $view->with('userList', ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all());
        });

        View::composer('events.createSeries', function ($view) {
            $view->with('userList', ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all());
        });
    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Illuminate\Contracts\Auth\Registrar',
            'App\Services\Registrar'
        );

        if ($this->app->environment('local', 'testing')) {
        }

        $this->app->bind(SessionInterface::class, Store::class);
        $this->app->bind(ListParameterStore::class, ListParameterSessionStore::class);

        // make sure there is only one instance of the param session store
        $this->app->singleton(ListParameterSessionStore::class, function ($app) {
            return new ListParameterSessionStore($app->make(Store::class));
        });
    }
}
