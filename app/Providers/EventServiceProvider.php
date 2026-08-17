<?php

namespace App\Providers;

use App\Events\EventCreated;
use App\Events\EventPhotoAdded;
use App\Events\EventUpdated;
use App\Listeners\ActivateVerifiedUserListener;
use App\Listeners\BlockSuppressedRecipients;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\QueueDiscordEventPost;
use App\Listeners\RouterMatchedListener;
use App\Listeners\SendCustomEmailVerificationNotification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Routing\Events\RouteMatched;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        RouteMatched::class => [
            RouterMatchedListener::class,
        ],

        // Suppression enforcement. MessageSending is the only hook that covers
        // every send path, including the console digests that bypass
        // BestEffortMailer — returning false from the listener cancels the send.
        MessageSending::class => [
            BlockSuppressedRecipients::class,
        ],
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
        Registered::class => [
            SendCustomEmailVerificationNotification::class,
        ],
        Verified::class => [
            ActivateVerifiedUserListener::class,
        ],

        // Discord auto-repost (issue #2058). EventPhotoAdded is the primary
        // trigger — an event usually gains its flyer after creation, and an
        // announcement without one is easy to scroll past. The other two are
        // cheap secondaries; PostEventToDiscord is ShouldBeUnique, so the
        // burst of all three collapses into a single delayed job.
        EventPhotoAdded::class => [
            QueueDiscordEventPost::class,
        ],
        EventCreated::class => [
            QueueDiscordEventPost::class,
        ],
        EventUpdated::class => [
            QueueDiscordEventPost::class,
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
