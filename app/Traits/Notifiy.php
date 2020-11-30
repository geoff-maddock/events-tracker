<?php

namespace App\Traits;

trait Notify
{
    /**
     * @param $event
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function notifyFollowing($event)
    {
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $event->tags()->get();
        $users = [];

        // improve this so it will only sent one email to each user per event, and include a list of all tags they were following that led to the notification
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $tag, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $tag, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $tag->name . ' :: ' . $event->start_at->format('D F jS') . ' ' . $event->name);
                    });
                    $users[$user->id] = $tag->name;
                };
            };
        };

        // notify users following any of the entities
        $entities = $event->entities()->get();

        // improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
        foreach ($entities as $entity) {
            foreach ($entity->followers() as $user) {
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    Mail::send('emails.following', ['user' => $user, 'event' => $event, 'object' => $entity, 'reply_email' => $reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $event, $entity, $reply_email, $site, $url) {
                        $m->from($reply_email, $site);

                        $m->to($user->email, $user->name)->subject($site . ': ' . $entity->name . ' :: ' . $event->start_at->format('D F jS') . ' ' . $event->name);
                    });
                    $users[$user->id] = $entity->name;
                };
            };
        };

        return back();
    }
}
