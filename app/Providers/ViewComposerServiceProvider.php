<?php

namespace App\Providers;

use App\Models\Forum;
use App\Models\Menu;
use App\Models\Role;
use App\Services\FeedbackPromptService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
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
        $this->composeFeedbackPrompt();
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
     * Share the pending feedback prompt, if any, with the main layout.
     *
     * Scoped to the one layout rather than '*' on purpose: AppServiceProvider
     * already registers a wildcard composer, and a second one would fire on
     * every partial render. FeedbackPromptService short-circuits on the
     * feature flag before touching the database.
     */
    private function composeFeedbackPrompt(): void
    {
        view()->composer('layouts.app-tw', function ($view) {
            $service = app(FeedbackPromptService::class);
            $user = Auth::user();

            $invitation = $service->mayDisplayInRequest(request(), $user)
                ? $service->pendingFor($user)
                : null;

            $view->with('feedbackPrompt', $invitation ? $service->payload($invitation) : null);
        });
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
