<?php

namespace App\Console\Commands;

use App\Mail\DailyReminder;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\BestEffortMailer;
use App\Services\DigestBuilder;
use App\Services\DigestEngagement;
use Illuminate\Console\Command;
use Log;

class Notify extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'notify
                            {--dry-run : Report how many digests and paused notices would go out, without sending or changing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send specified notification(s).';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(DigestBuilder $digests, DigestEngagement $engagement)
    {
        $reply_email = config('app.noreplyemail');
        $admin_email = config('app.admin');
        $site = config('app.app_name');
        // templates append paths straight onto this ("{{ $url }}events/..."), so it must end in a slash
        $url = url('/').'/';
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['sent' => 0, 'notices' => 0, 'paused' => 0, 'empty' => 0];

        // one bad address or transport hiccup must not end the run for everyone after it,
        // since re-running would mail everyone before it twice (#2338)
        $mailer = new BestEffortMailer();

        // get each active user with a verified address: not suspended or banned
        // accounts, and not an address changed but not yet confirmed
        $users = User::with('profile')
            ->where('user_status_id', UserStatus::ACTIVE)
            ->whereNotNull('email_verified_at')
            ->orderBy('name', 'ASC')
            ->get();

        foreach ($users as $user) {
            // if the user does not have this setting, continue
            if ($user->profile == null || $user->profile->setting_daily_update !== 1) {
                continue;
            }

            // dormant and already told: skip before building anything (#2083)
            $decision = $engagement->decide($user);
            if ($decision === DigestEngagement::PAUSED) {
                $counts['paused']++;

                continue;
            }

            // engaged again after a pause: clear it now, not only when a digest goes
            // out, so that if they lapse again they get a fresh notice
            if ($decision === DigestEngagement::SEND && !$dryRun) {
                $engagement->clearPause($user);
            }

            $digest = $digests->daily($user);

            if ($digest->isEmpty()) {
                $counts['empty']++;
                Log::info('No daily events email was sent to '.$user->name.' at '.$user->email.'.');

                continue;
            }

            // dormant: this would have been a digest, so it is the one paused notice instead
            if ($decision === DigestEngagement::NOTICE) {
                if ($dryRun || $mailer->attempt(fn () => $engagement->pause($user), ['user_id' => $user->id, 'mailable' => 'DigestsPaused'])) {
                    $counts['notices']++;
                }

                continue;
            }

            if ($dryRun) {
                $counts['sent']++;

                continue;
            }

            if (!$mailer->send($user->email, new DailyReminder($url, $site, $admin_email, $reply_email, $user, $digest->attending, $digest->series, $digest->interests), ['user_id' => $user->id])) {
                continue;
            }
            $counts['sent']++;

            Log::info('Daily events email was sent to '.$user->name.' at '.$user->email.'.');
        }

        $this->info(($dryRun ? 'DRY RUN: would send ' : 'Sent ')."{$counts['sent']} daily digest(s), {$counts['notices']} paused notice(s); skipped {$counts['paused']} paused and {$counts['empty']} with nothing today.");

        $summary = $mailer->summary();
        if ($summary['failed'] || $summary['skipped']) {
            $this->warn("{$summary['failed']} email(s) failed to send and {$summary['skipped']} were skipped after the mail transport failed repeatedly; see the log.");
        }
        $mailer->logSummary('notify');
    }
}
