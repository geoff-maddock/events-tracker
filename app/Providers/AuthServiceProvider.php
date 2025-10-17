<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Post;
use App\Models\Thread;
use App\Policies\PostPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Schema;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

//use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        Thread::class => ThreadPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before(function ($user) {
            if ($user->hasGroup('admin')) {
                return true;
            }
        });

        // adds a gate for each permission name - checks whether the user has a group that matches one of the permission groups
        // disabling because this is causing an issue with php artisan when the database isn't initialized
        foreach ($this->getPermissions() as $permission) {
            /** @var \App\Models\Permission $permission */
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasGroup($permission->groups);
            });
        }

        Gate::before(function ($user) {
            if ($user->hasGroup('admin')) {
                return true;
            }
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            // Prefer the per-request value your API accepts, fallback to config/app.php
            $base = request()->input('frontend-url') ?? config('app.frontend_url', config('app.url'));
            $base = rtrim($base, '/');

            return $base.'/password/reset/'.$token.'?email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        // I just need to override the URL generation, not the whole email
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Prefer the per-request value your API accepts, fallback to config/app.php
            $base = $notifiable->frontendUrl ?? config('app.frontend_url', config('app.url'));

            $path = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                false
            );

            $verificationUrl = rtrim($base, '/').$path;

            return $verificationUrl;
        });
    }

    protected function getPermissions(): Collection
    {
        // doing this check to make sure the table exists
        // since it's in a provider, it might be called by php artisan before the db is migrated

        if (!Schema::hasTable('permissions')) {
            return new Collection([]);
        }

        return Permission::with('groups')->get();
    }
}
