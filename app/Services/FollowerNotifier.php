<?php

namespace App\Services;

use App\Mail\FollowingPostUpdate;
use App\Mail\FollowingThreadUpdate;
use App\Mail\FollowingUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;

/**
 * Emails the followers of an event's tags/entities, a thread's tags/series, or a
 * post's thread/tags/series (#2170).
 *
 * This used to be copied into each controller (web and API) and ran inside the
 * request, one SMTP send per follower. It now runs from the queued
 * App\Jobs\NotifyFollowers job; each user is emailed at most once per item.
 */
class FollowerNotifier
{
    public function event(Event $event): void
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // Follower notification is best-effort — the event is already saved, so
        // a mail failure must not surface as a 500 on event creation.
        $mailer = new BestEffortMailer();

        // notify users following any of the tags
        $tags = $event->tags()->get();
        $users = [];

        // improve this so it will only send one email to each user per event, and include a list of all tags they were following that led to the notification
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user->profile && $user->profile->setting_instant_update !== 1) {
                    continue;
                }

                // if the user hasn't already been notified, then email them.
                // key on $user->id — followers() selects users.*, so there is
                // no user_id attribute (it was always null, collapsing every
                // follower onto one key and skipping all but the first)
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingUpdate($url, $site, $admin_email, $reply_email, $user, $event, $tag),
                        ['event_id' => $event->id, 'user_id' => $user->id, 'via' => 'tag']
                    );
                    $users[$user->id] = $tag->name;
                } else {
                    $users[$user->id] = $users[$user->id].', '.$tag->name;
                }
            }
        }

        // notify users following any of the entities
        $entities = $event->entities()->get();

        // improve this so it will only sent one email to each user per event, and include a list of entities they were following that led to the notification
        /** @var Entity $entity */
        foreach ($entities as $entity) {
            foreach ($entity->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user->profile && $user->profile->setting_instant_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingUpdate($url, $site, $admin_email, $reply_email, $user, $event, $entity),
                        ['event_id' => $event->id, 'user_id' => $user->id, 'via' => 'entity']
                    );
                    $users[$user->id] = $entity->name;
                } else {
                    $users[$user->id] = $users[$user->id].', '.$entity->name;
                }
            }
        }

        $mailer->logSummary('FollowerNotifier@event', ['event_id' => $event->id]);
    }

    public function thread(Thread $thread): void
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = [];

        // Follower notification is best-effort — the thread is already saved,
        // so a mail failure must not surface as a 500 on posting.
        $mailer = new BestEffortMailer();

        // notify users following any tags related to the thread
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                // Indirect (follow-driven) thread notifications are opt-in per issue #1853.
                if ($user?->profile?->setting_notify_threads_by_follow !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingThreadUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $tag),
                        ['thread_id' => $thread->id, 'user_id' => $user->id, 'via' => 'tag']
                    );

                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $series = $thread->series()->get();

        /** @var Series $s */

        foreach ($series as $s) {
            foreach ($s->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                if ($user?->profile?->setting_notify_threads_by_follow !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingThreadUpdate($url, $site, $admin_email, $reply_email, $user, $thread),
                        ['thread_id' => $thread->id, 'user_id' => $user->id, 'via' => 'series']
                    );
                    $users[$user->id] = $s->name;
                }
            }
        }

        $mailer->logSummary('FollowerNotifier@thread', ['thread_id' => $thread->id]);
    }

    public function post(Post $post): void
    {
        $admin_email = config('app.admin');
        $reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        $thread = $post->thread;

        // notify users following any of the tags
        $tags = $thread->tags()->get();
        $users = [];

        // Follower notification is best-effort — the post is already saved, so
        // a mail failure must not surface as a 500 on posting.
        $mailer = new BestEffortMailer();

        // notify users who are following this thread
        foreach ($thread->followers() as $user) {
            // if the user does not have this setting, continue
            if ($user?->profile?->setting_forum_update !== 1) {
                continue;
            }
            // if the user hasn't already been notified, then email them
            if (!array_key_exists($user->id, $users)) {
                $mailer->send(
                    $user->email,
                    new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post),
                    ['post_id' => $post->id, 'user_id' => $user->id, 'via' => 'thread']
                );
                $users[$user->id] = $thread->name;
            }
        }

        // notify users following any tags related to the thread
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }
                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post, $tag),
                        ['post_id' => $post->id, 'user_id' => $user->id, 'via' => 'tag']
                    );
                    $users[$user->id] = $tag->name;
                }
            }
        }

        // notify users following any of the series
        $seriess = $thread->series()->get();

        /** @var Series $series */

        foreach ($seriess as $series) {
            foreach ($series->followers() as $user) {
                // if the user does not have this setting, continue
                if ($user?->profile?->setting_forum_update !== 1) {
                    continue;
                }

                // if the user hasn't already been notified, then email them
                if (!array_key_exists($user->id, $users)) {
                    $mailer->send(
                        $user->email,
                        new FollowingPostUpdate($url, $site, $admin_email, $reply_email, $user, $thread, $post),
                        ['post_id' => $post->id, 'user_id' => $user->id, 'via' => 'series']
                    );
                    $users[$user->id] = $series->name;
                }
            }
        }

        $mailer->logSummary('FollowerNotifier@post', ['post_id' => $post->id]);
    }
}
