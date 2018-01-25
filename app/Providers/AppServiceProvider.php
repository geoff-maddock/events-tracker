<?php namespace App\Providers;


use App\Entity;
use App\EventType;
use App\Visibility;
use App\Tag;
use App\Series;
use App\User;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;


class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        Relation::morphMap([
            'entity' => 'App\Entity',
            'events' => 'App\Event',
            'tags' => 'App\Tag',
            'post' => 'App\Post',
            'thread' => 'App\Thread',
        ]);

	    // get the user, set the theme and pass to the view
        \View::composer('app', function($view) {
            if (Auth::check() && Auth::user()->profile && Auth::user()->profile->default_theme) {
                // use the set theme if the user has one
                $theme = Auth::user()->profile->default_theme;
            } else {
                $theme = config('app.default_theme');
            };
            $view->with('theme', $theme);
        });

        // always include these values in the events edit view
        \View::composer('events.edit', function($view) {
            $view->with('venues',[''=>''] + Entity::getVenues()->pluck('name','id')->all());
            $view->with('promoters',[''=>''] + Entity::whereHas('roles', function($q)
                {
                    $q->where('name','=','Promoter');
                })->orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('eventTypes',[''=>''] + EventType::orderBy('name','ASC')->pluck('name', 'id')->all());
            $view->with('visibilities',[''=>''] + Visibility::pluck('name', 'id')->all());
            $view->with('tags',Tag::orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('entities',Entity::orderBy('name','ASC')->pluck('name','id')->all());
            $view->with('seriesList',[''=>''] + Series::orderBy('name', 'ASC')->pluck('name', 'id')->all());
            $view->with('userList',[''=>''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all());
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
            $this->app->register(DuskServiceProvider::class);
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        };
	}

}
