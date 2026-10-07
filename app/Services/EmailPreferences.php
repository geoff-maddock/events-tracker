<?php

namespace App\Services;

use App\Models\EmailOptOut;
use App\Models\EmailSuppression;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Logged-out unsubscribe links and the lists they act on (#2103).
 *
 * Every link is a Laravel signed URL with no expiry, so an unsubscribe link in
 * a months-old digest still works. The signature is the only credential: the
 * pages behind these links need no login.
 */
class EmailPreferences
{
    public const WEEKLY = 'weekly';

    public const DAILY = 'daily';

    public const INSTANT = 'instant';

    public const FORUM = 'forum';

    /**
     * The user mailing lists, each backed by a profile flag.
     *
     * @var array<string, array{setting: string, label: string, description: string}>
     */
    public const LISTS = [
        self::WEEKLY => [
            'setting' => 'setting_weekly_update',
            'label' => 'Weekly update',
            'description' => 'A Monday email with the week ahead for what you follow.',
        ],
        self::DAILY => [
            'setting' => 'setting_daily_update',
            'label' => 'Daily reminder',
            'description' => 'A morning email listing today\'s events you are attending or following.',
        ],
        self::INSTANT => [
            'setting' => 'setting_instant_update',
            'label' => 'New event alerts',
            'description' => 'An email when an event is added for an artist, venue or tag you follow.',
        ],
        self::FORUM => [
            'setting' => 'setting_forum_update',
            'label' => 'Forum updates',
            'description' => 'An email when someone posts in a thread or tag you follow.',
        ],
    ];

    public static function isList(string $list): bool
    {
        return array_key_exists($list, self::LISTS);
    }

    /** The preference page, linked from the footer of every list email. */
    public static function preferencesUrl(User $user): string
    {
        return URL::signedRoute('email.preferences', ['id' => $user->id]);
    }

    /** One list's unsubscribe link, used for the List-Unsubscribe header. */
    public static function unsubscribeUrl(User $user, string $list): string
    {
        return URL::signedRoute('email.unsubscribe', ['id' => $user->id, 'list' => $list]);
    }

    /** The opt-out link for an entity contact address, which has no user. */
    public static function contactUnsubscribeUrl(string $email): string
    {
        return URL::signedRoute('email.unsubscribe.contact', ['email' => EmailSuppression::normalize($email)]);
    }

    /**
     * The user's profile, or an unsaved one describing what a user without a
     * profile actually receives. Both digests and forum mail skip them, but
     * FollowerNotifier still sends new-event alerts, so that list starts on.
     * The flags are set explicitly because the columns default to 1.
     */
    public static function profileFor(User $user): Profile
    {
        if ($user->profile) {
            return $user->profile;
        }

        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->setting_weekly_update = 0;
        $profile->setting_daily_update = 0;
        $profile->setting_instant_update = 1;
        $profile->setting_forum_update = 0;

        return $profile;
    }

    /** Turn one list off. */
    public static function unsubscribe(User $user, string $list): void
    {
        if (!self::isList($list)) {
            return;
        }

        $profile = self::profileFor($user);
        $profile->{self::LISTS[$list]['setting']} = 0;
        $profile->save();

        Log::info('EmailPreferences: user unsubscribed from a list', ['user_id' => $user->id, 'list' => $list]);
    }

    public static function unsubscribeContact(string $email): void
    {
        EmailOptOut::optOut($email, EmailOptOut::LIST_ENTITY_CONTACT);

        Log::info('EmailPreferences: entity contact opted out', ['email' => EmailSuppression::normalize($email)]);
    }
}
