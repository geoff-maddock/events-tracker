<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Signup\SignupRiskScorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin review of recent signups (#2312): who signed up, why an account
 * looks risky, and one step to approve it or ban it as spam.
 */
class SignupReviewController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'show' => ['nullable', 'in:all,flagged,untrusted'],
        ]);

        $days = (int) ($validated['days'] ?? config('signup.review_days'));
        $show = $validated['show'] ?? 'all';

        $count = fn (string $table, string $column) => DB::table($table)
            ->selectRaw('count(*)')
            ->whereColumn("{$table}.{$column}", 'users.id');

        $query = User::query()
            ->with(['profile', 'status'])
            ->select('users.*')
            ->addSelect([
                'events_count' => $count('events', 'created_by'),
                'entities_count' => $count('entities', 'created_by'),
                'comments_count' => $count('comments', 'created_by'),
                'responses_count' => $count('event_responses', 'user_id'),
                'follows_count' => $count('follows', 'user_id'),
            ])
            ->where('users.created_at', '>=', now()->subDays($days))
            ->orderByDesc('users.created_at');

        if ($show === 'flagged') {
            $query->where('risk_score', '>=', (int) config('signup.risk_threshold'));
        } elseif ($show === 'untrusted') {
            // not trusted by any route (approval, admin, or established), and not already banned
            $query->whereNot(fn (Builder $query) => $query->trusted())
                ->where('users.user_status_id', '!=', UserStatus::BANNED);
        }

        return view('users.signups-tw', [
            'signups' => $query->paginate(self::PER_PAGE)->appends($request->query()),
            'days' => $days,
            'show' => $show,
            'threshold' => (int) config('signup.risk_threshold'),
            'labels' => SignupRiskScorer::LABELS,
        ]);
    }

    /**
     * Approve or ban the selected accounts.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,ban'],
            'ids' => ['required', 'array', 'max:'.self::PER_PAGE],
            'ids.*' => ['integer'],
        ]);

        $users = User::with('profile')->whereIn('id', $validated['ids'])->get();
        $names = [];

        foreach ($users as $user) {
            // admins are never banned from here, nor is the admin doing it
            if ($validated['action'] === 'ban' && ($user->isAdmin() || $user->id === $this->user?->id)) {
                continue;
            }

            $validated['action'] === 'approve' ? $this->approve($user) : $this->ban($user);
            $names[] = $user->name;
        }

        if ($names === []) {
            flash()->error('Error', 'No accounts were changed.');
        } else {
            $verb = $validated['action'] === 'approve' ? 'Approved' : 'Banned as spam';
            flash()->success('Success', $verb.': '.implode(', ', $names));
        }

        return back();
    }

    /**
     * Trusted from now on: active, verified, and its profile shown to everyone.
     */
    private function approve(User $user): void
    {
        $user->trusted_at = now();
        $user->user_status_id = UserStatus::ACTIVE;
        $user->email_verified_at ??= now()->toDateTimeString();
        $user->save();

        Activity::log($user, $this->user, 10, sprintf('Approved signup: %s', $user->name));
    }

    /**
     * Banned, with the spam cleared from its profile. The row stays so the
     * email address can't simply register again; no ban email is sent to a
     * spam account.
     */
    private function ban(User $user): void
    {
        $user->user_status_id = UserStatus::BANNED;
        $user->trusted_at = null;
        $user->save();

        $user->profile?->fill([
            'bio' => null,
            'facebook_username' => null,
            'twitter_username' => null,
            'instagram_username' => null,
        ])->save();

        Activity::log($user, $this->user, 11, sprintf('Banned as spam: %s', $user->name));
    }
}
