<?php

namespace App\Services\Signup;

use App\Models\Activity;
use App\Models\User;
use App\Models\UserStatus;
use App\Rules\NotDisposableEmail;
use App\Rules\UntrustedLinks;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scores how likely a signup is to be a spam account (#2312). Each signal
 * adds its weight; a total at or above signup.risk_threshold keeps the
 * account Pending after it verifies, and lists it in the admin signup review.
 *
 * Registration scores everything, including the network signals (an IP
 * burst, StopForumSpam). A later profile save rescores only the content and
 * keeps the network reasons found at registration.
 */
class SignupRiskScorer
{
    public const WEIGHTS = [
        'profile_links' => 2,
        'spam_keywords' => 2,
        'name_pattern' => 1,
        'suspicious_tld' => 2,
        'disposable_domain' => 3,
        'ip_burst' => 2,
        'stopforumspam' => 3,
    ];

    public const LABELS = [
        'profile_links' => 'Links in profile',
        'spam_keywords' => 'Spam keywords',
        'name_pattern' => 'Generated-looking name',
        'suspicious_tld' => 'Unusual email domain',
        'disposable_domain' => 'Disposable email',
        'ip_burst' => 'Several signups from one IP',
        'stopforumspam' => 'Listed on StopForumSpam',
    ];

    // reasons that can only be found at registration, kept on rescoring
    private const NETWORK_REASONS = ['ip_burst', 'stopforumspam'];

    /**
     * Score a new registration and store the result. Call before the
     * registration's own activity is logged, so the IP burst counts only
     * earlier signups.
     */
    public function assessRegistration(User $user, ?string $ip): void
    {
        $reasons = $this->contentReasons($user);

        if ($ip !== null && $this->recentSignupsFrom($ip) >= (int) config('signup.ip_burst_threshold')) {
            $reasons[] = 'ip_burst';
        }

        if ($this->listedOnStopForumSpam($user->email, $ip)) {
            $reasons[] = 'stopforumspam';
        }

        $this->store($user, $reasons);
    }

    /**
     * Rescore after the user edits their own profile. An account that is not
     * trusted yet and now scores as risky goes back to Pending.
     */
    public function assessProfileChange(User $user): void
    {
        if ($user->isTrusted()) {
            return;
        }

        $network = array_values(array_intersect($user->risk_reasons ?? [], self::NETWORK_REASONS));
        $this->store($user, array_merge($this->contentReasons($user), $network));

        if ($this->isRisky($user) && (int) $user->user_status_id === UserStatus::ACTIVE) {
            $user->user_status_id = UserStatus::PENDING;
            $user->save();
        }
    }

    public function isRisky(User $user): bool
    {
        return $user->risk_score !== null && $user->risk_score >= (int) config('signup.risk_threshold');
    }

    /**
     * @return list<string>
     */
    public function contentReasons(User $user): array
    {
        $reasons = [];
        $bio = (string) $user->profile?->bio;

        // links anywhere in the profile text: an older account may still have them
        $profileText = implode(' ', [$user->name, $user->profile?->alias, $user->profile?->location, $bio]);
        if (UntrustedLinks::countLinks($profileText) > 0) {
            $reasons[] = 'profile_links';
        }

        if ($this->hasSpamKeyword($user->name.' '.$bio)) {
            $reasons[] = 'spam_keywords';
        }

        if (self::looksGenerated(trim((string) $user->name))) {
            $reasons[] = 'name_pattern';
        }

        $domain = strtolower((string) substr(strrchr((string) $user->email, '@') ?: '', 1));
        $tld = substr(strrchr($domain, '.') ?: '', 1);
        if ($tld !== '' && in_array($tld, (array) config('signup.suspicious_tlds'), true)) {
            $reasons[] = 'suspicious_tld';
        }

        // registration refuses these; an admin-created or older account may still have one
        if (NotDisposableEmail::isDisposable((string) $user->email)) {
            $reasons[] = 'disposable_domain';
        }

        return $reasons;
    }

    /**
     * The classic generated name: a capitalised first name run into a random
     * suffix, one word of 9+ letters ("Kevinexods", "JessicalStync"). All
     * lowercase or all caps, as many DJ and band names are, doesn't count.
     * Weak alone, so it only adds a point.
     */
    public static function looksGenerated(string $name): bool
    {
        return mb_strlen($name) >= 9 && preg_match('/^\p{Lu}\p{Ll}+(?:\p{Lu}\p{Ll}+)?$/u', $name) === 1;
    }

    /**
     * @param list<string> $reasons
     */
    private function store(User $user, array $reasons): void
    {
        $reasons = array_values(array_unique($reasons));

        $user->risk_reasons = $reasons;
        $user->risk_score = array_sum(array_map(fn (string $r) => self::WEIGHTS[$r] ?? 0, $reasons));
        $user->save();
    }

    private function hasSpamKeyword(string $text): bool
    {
        $text = mb_strtolower($text);
        foreach ((array) config('signup.spam_keywords') as $keyword) {
            if (preg_match('/\b'.preg_quote(mb_strtolower($keyword), '/').'\b/u', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrations logged from this IP in the last day. Both register
     * controllers log a create activity on the new User.
     */
    private function recentSignupsFrom(string $ip): int
    {
        return Activity::query()
            ->where('object_table', 'User')
            ->where('action_id', 1)
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Fails open: a slow or failing lookup never blocks a signup.
     */
    private function listedOnStopForumSpam(string $email, ?string $ip): bool
    {
        if (!config('signup.stopforumspam.enabled')) {
            return false;
        }

        try {
            $query = ['json' => 1, 'emailhash' => md5(strtolower(trim($email)))];
            if ($ip !== null) {
                $query['ip'] = $ip;
            }

            $response = Http::timeout((int) config('signup.stopforumspam.timeout'))
                ->get('https://api.stopforumspam.org/api', $query);

            if (!$response->successful() || (int) $response->json('success') !== 1) {
                return false;
            }

            if ((int) $response->json('emailhash.appears') > 0) {
                return true;
            }

            return (int) $response->json('ip.appears') > 0
                && (float) $response->json('ip.confidence') >= (float) config('signup.stopforumspam.ip_min_confidence');
        } catch (Throwable $e) {
            Log::warning('StopForumSpam lookup failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
