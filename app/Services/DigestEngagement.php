<?php

namespace App\Services;

use App\Mail\DigestsPaused;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The engagement gate on the daily and weekly digests (#2083).
 *
 * Most digest volume went to accounts nobody had used in years. A user counts
 * as engaged if, in the last 90 days, they signed up, did something on the
 * site, used an API token, or clicked "resume" in the paused notice. A dormant
 * user is told once that their digests are paused, then skipped until they
 * come back.
 *
 * Resolve one instance per command run: the set of engaged users is loaded
 * once, in two queries, rather than once per user.
 */
class DigestEngagement
{
    public const DORMANT_AFTER_DAYS = 90;

    /** Send the digest. */
    public const SEND = 'send';

    /** Dormant and not yet told: send the paused notice instead. */
    public const NOTICE = 'notice';

    /** Dormant and already told: send nothing. */
    public const PAUSED = 'paused';

    /**
     * What a person does themselves. Leaves out what the system logs against
     * them: NOTIFICATION (notifyWeekly logs one for every digest it sends, so
     * counting it would keep everyone engaged forever), FAILED_LOGIN and
     * PASSWORD_RESET_REQUEST (anyone can trigger those for any address), and
     * admin actions, which are logged against the admin.
     */
    public const ENGAGED_ACTIONS = [
        Action::CREATE,
        Action::UPDATE,
        Action::DELETE,
        Action::LOGIN,
        Action::LOGOUT,
        Action::FOLLOW,
        Action::UNFOLLOW,
        Action::ATTENDING,
        Action::UNATTENDING,
        Action::PASSWORD_RESET,
        Action::EXPORT,
    ];

    /** @var array<int, true>|null */
    private ?array $engagedIds = null;

    public function decide(User $user): string
    {
        if ($this->isEngaged($user)) {
            return self::SEND;
        }

        return $user->profile?->digests_paused_at ? self::PAUSED : self::NOTICE;
    }

    public function isEngaged(User $user): bool
    {
        $cutoff = $this->cutoff();

        if ($user->created_at && $user->created_at->gte($cutoff)) {
            return true;
        }

        $resumed = $user->profile?->digests_resumed_at;
        if ($resumed && $resumed->gte($cutoff)) {
            return true;
        }

        return isset($this->engagedUserIds()[$user->id]);
    }

    /** Tell a dormant user their digests are paused, and remember that we did. */
    public function pause(User $user): void
    {
        $profile = $user->profile;
        if (!$profile) {
            return;
        }

        Mail::to($user->email)->send(new DigestsPaused(
            config('app.url'),
            config('app.app_name'),
            config('app.noreplyemail'),
            $user
        ));

        $profile->digests_paused_at = now();
        $profile->save();

        Log::info('DigestEngagement: paused digests for a dormant user', ['user_id' => $user->id]);
    }

    /**
     * An engaged user who was paused before, e.g. they logged in again
     * without using the resume link. Clearing it means that if they lapse
     * again they get a fresh notice.
     */
    public function clearPause(User $user): void
    {
        $profile = $user->profile;
        if ($profile?->digests_paused_at) {
            $profile->digests_paused_at = null;
            $profile->save();
        }
    }

    /** The resume link in the paused notice. */
    public static function resume(User $user): void
    {
        $profile = $user->profile;
        if (!$profile) {
            return;
        }

        $profile->digests_resumed_at = now();
        $profile->digests_paused_at = null;
        $profile->save();

        Log::info('DigestEngagement: user resumed their digests', ['user_id' => $user->id]);
    }

    private function cutoff(): Carbon
    {
        return Carbon::now()->subDays(self::DORMANT_AFTER_DAYS);
    }

    /**
     * Users with qualifying activity or a recently used API token. Token use
     * matters because token and basic-auth API requests never fire the Login
     * event, so someone using the site only through the frontend app has no
     * LOGIN rows; Sanctum updates last_used_at on every request.
     *
     * @return array<int, true>
     */
    private function engagedUserIds(): array
    {
        if ($this->engagedIds === null) {
            $cutoff = $this->cutoff();

            $active = Activity::query()
                ->whereIn('action_id', self::ENGAGED_ACTIONS)
                ->where('created_at', '>=', $cutoff)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');

            $tokens = DB::table('personal_access_tokens')
                ->where('tokenable_type', (new User())->getMorphClass())
                ->where('last_used_at', '>=', $cutoff)
                ->distinct()
                ->pluck('tokenable_id');

            $this->engagedIds = array_fill_keys($active->merge($tokens)->map(fn ($id) => (int) $id)->all(), true);
        }

        return $this->engagedIds;
    }
}
