<?php

namespace App\Console\Commands;

use App\Exceptions\MonitoringAlert;
use App\Services\Integrations\Instagram;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Looks for the failures that otherwise go unnoticed and reports them.
 *
 * Runs from the scheduler every 15 minutes with a Sentry cron monitor on it,
 * so a run that doesn't happen is itself the signal that cron or the
 * scheduler has stopped (see docs/deployment_notes.md, "Monitoring").
 */
class MonitorCheck extends Command
{
    protected $signature = 'monitor:check';

    protected $description = 'Report a stalled queue or an expiring Facebook/Instagram token';

    public function handle(): int
    {
        $this->checkQueue();
        $this->checkFacebookToken();

        // a problem found is reported, not a failure of the check itself
        return self::SUCCESS;
    }

    /**
     * A job that became due long ago and still hasn't been picked up means the
     * worker is stopped or stuck. Jobs that fail are already reported by the
     * worker, so they aren't counted again here.
     */
    private function checkQueue(): void
    {
        if (config('queue.default') !== 'database') {
            $this->line('Queue: not the database driver, skipped.');

            return;
        }

        $table = config('queue.connections.database.table', 'jobs');
        $now = now()->getTimestamp();
        $retryAfter = (int) config('queue.connections.database.retry_after', 90);

        $due = DB::table($table)->whereNull('reserved_at')->where('available_at', '<=', $now);
        // A job whose worker died stays reserved; the queue offers it again
        // once retry_after has passed, so from then on it is waiting too.
        $abandoned = DB::table($table)->whereNotNull('reserved_at')->where('reserved_at', '<=', $now - $retryAfter);

        $dueSince = $due->min('available_at');
        $abandonedSince = $abandoned->min('reserved_at');

        $since = collect([$dueSince, $abandonedSince === null ? null : (int) $abandonedSince + $retryAfter])
            ->filter(fn ($timestamp) => $timestamp !== null)
            ->min();

        if ($since === null) {
            $this->line('Queue: nothing waiting.');

            return;
        }

        $waitMinutes = (int) floor(($now - (int) $since) / 60);

        if ($waitMinutes < (int) config('monitoring.queue_max_wait_minutes')) {
            $this->line("Queue: oldest job has waited {$waitMinutes} min.");

            return;
        }

        $waiting = $due->count() + $abandoned->count();

        $this->raise(
            'queue',
            "Queue is not being worked: {$waiting} job(s) waiting, the oldest for {$waitMinutes} minutes. Check the queue worker."
        );
    }

    /**
     * Instagram posting stops when the page token lapses. Asked once a day.
     */
    private function checkFacebookToken(): void
    {
        $token = config('app.facebook_system_page_access_token');

        if (blank($token)) {
            $this->line('Facebook token: not configured, skipped.');

            return;
        }

        if (!Cache::add('monitor-check:token-checked', 1, now()->addDay())) {
            $this->line('Facebook token: already checked today.');

            return;
        }

        try {
            // A form POST with method=GET, which the Graph API treats as a GET:
            // a query string would put the token in Sentry's request breadcrumbs.
            $answer = Http::asForm()->timeout(15)
                ->post('https://graph.facebook.com/'.Instagram::API_VERSION.'/debug_token', [
                    'method' => 'GET',
                    'input_token' => $token,
                    'access_token' => $token,
                ])
                ->json();
        } catch (Throwable $e) {
            // a network blip isn't worth an alert; ask again on the next run
            Cache::forget('monitor-check:token-checked');
            $this->line('Facebook token: could not be checked ('.class_basename($e).').');

            return;
        }

        // A dead token can't look itself up: the answer is an error, not is_valid=false.
        if (is_array($answer) && is_array($answer['error'] ?? null)) {
            $code = (int) ($answer['error']['code'] ?? 0);

            $this->raise('token', $code === 190
                ? 'The Facebook/Instagram page token is no longer valid. Instagram posting will fail until it is replaced.'
                : 'The Facebook/Instagram page token could not be checked: the Graph API answered with error code '.$code.'.');

            return;
        }

        $data = is_array($answer) ? ($answer['data'] ?? null) : null;

        if (!is_array($data)) {
            Cache::forget('monitor-check:token-checked');
            $this->line('Facebook token: could not be checked (unexpected answer).');

            return;
        }

        if (($data['is_valid'] ?? false) !== true) {
            $this->raise('token', 'The Facebook/Instagram page token is no longer valid. Instagram posting will fail until it is replaced.');

            return;
        }

        // 0 means the token never expires; either date ends posting
        $expiries = array_filter([(int) ($data['expires_at'] ?? 0), (int) ($data['data_access_expires_at'] ?? 0)]);

        if ($expiries === []) {
            $this->line('Facebook token: valid, no expiry.');

            return;
        }

        $expires = Carbon::createFromTimestamp(min($expiries));
        $days = (int) floor(now()->diffInDays($expires, false));

        if ($days > (int) config('monitoring.token_warn_days')) {
            $this->line("Facebook token: valid for {$days} more days.");

            return;
        }

        $this->raise('token', "The Facebook/Instagram page token expires on {$expires->toDateString()} ({$days} days). Replace it before Instagram posting stops.");
    }

    /**
     * Report a problem, at most once per repeat period while it lasts.
     */
    private function raise(string $key, string $message): void
    {
        $this->warn($message);

        if (Cache::add('monitor-check:alerted:'.$key, 1, now()->addHours((int) config('monitoring.repeat_hours')))) {
            MonitoringAlert::send($key, $message);
        }
    }
}
