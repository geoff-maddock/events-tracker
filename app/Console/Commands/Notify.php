<?php

namespace App\Console\Commands;

use App\Mail\DailyReminder;
use App\Models\User;
use App\Services\DigestBuilder;
use App\Services\DigestEngagement;
use Illuminate\Console\Command;
use Log;
use Mail;

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
        $url = config('app.url');
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['sent' => 0, 'notices' => 0, 'paused' => 0, 'empty' => 0];

        // get each user
        $users = User::with('profile')->orderBy('name', 'ASC')->get();

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

            $digest = $digests->daily($user);

            if ($digest->isEmpty()) {
                $counts['empty']++;
                Log::info('No daily events email was sent to '.$user->name.' at '.$user->email.'.');

                continue;
            }

            // dormant: this would have been a digest, so it is the one paused notice instead
            if ($decision === DigestEngagement::NOTICE) {
                $counts['notices']++;
                if (!$dryRun) {
                    $engagement->pause($user);
                }

                continue;
            }

            $counts['sent']++;
            if ($dryRun) {
                continue;
            }

            $engagement->clearPause($user);

            Mail::to($user->email)
                ->send(new DailyReminder($url, $site, $admin_email, $reply_email, $user, $digest->attending, $digest->series, $digest->interests));

            Log::info('Daily events email was sent to '.$user->name.' at '.$user->email.'.');
        }

        $this->info(($dryRun ? 'DRY RUN: would send ' : 'Sent ')."{$counts['sent']} daily digest(s), {$counts['notices']} paused notice(s); skipped {$counts['paused']} paused and {$counts['empty']} with nothing today.");
    }
}
