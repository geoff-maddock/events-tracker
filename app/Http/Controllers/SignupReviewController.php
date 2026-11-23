<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserStatus;
use App\Services\Signup\SignupRiskScorer;
use App\Services\UserAccounts;
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
    public function update(Request $request, UserAccounts $accounts): RedirectResponse
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

            $validated['action'] === 'approve' ? $accounts->approveSignup($user, $this->user) : $accounts->banAsSpam($user, $this->user);
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
}
