<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class SendCustomEmailVerificationNotification
{
    /**
     * Handle the event.
     *
     * @param  Registered  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        if ($event->user instanceof MustVerifyEmail && ! $event->user->hasVerifiedEmail()) {
            // Check if there's a custom frontend URL stored for this user
            $frontendUrl = Cache::get('frontend-url:' . $event->user->id);
            
            if ($frontendUrl) {
                // Temporarily override the app URL to use the frontend URL
                $originalUrl = Config::get('app.url');
                Config::set('app.url', $frontendUrl);
                
                // Send the verification notification
                $event->user->sendEmailVerificationNotification();
                
                // Restore the original URL
                Config::set('app.url', $originalUrl);
                
                // Clean up the cache
                Cache::forget('frontend-url:' . $event->user->id);
            } else {
                // Use default behavior
                $event->user->sendEmailVerificationNotification();
            }
        }
    }
}
