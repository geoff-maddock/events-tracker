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
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Schema;

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

        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Build and return your verification URL as needed
            // This example creates a signed URL to a named route 'verification.verify'
            if (isset($notifiable->frontendUrl)) {
                $base = rtrim($notifiable->frontendUrl, '/');
                // You might want to store the frontend URL in the notifiable (user) instance
                // when the user is created or during the request that triggers the email
                // verification notification.
            } else {
                $base = config('app.frontend_url', config('app.url'));
            }

            $verifyURL =  URL::temporarySignedRoute(
                'verification.verify', // Replace with your desired route name
                now()->addMinutes(60), // Link expiration time
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                false
            );

            return $base.$verifyURL;
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
