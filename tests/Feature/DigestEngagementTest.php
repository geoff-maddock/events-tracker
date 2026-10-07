<?php

namespace Tests\Feature;

use App\Mail\DailyReminder;
use App\Mail\DigestsPaused;
use App\Mail\WeeklyUpdate;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\DigestBuilder;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Email volume levers from #2083: the 90-day engagement gate on both digests,
 * the weekly series date check, new-signup defaults and the adminTest schedule.
 */
class DigestEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /** A subscriber whose account is a year old, so only activity can make them engaged. */
    private function subscriber(int $weekly = 1, int $daily = 0, int $accountAgeDays = 365): User
    {
        $user = User::factory()->create([
            'user_status_id' => UserStatus::ACTIVE,
            'created_at' => Carbon::now()->subDays($accountAgeDays),
        ]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_weekly_update' => $weekly,
            'setting_daily_update' => $daily,
        ]);

        return $user->fresh('profile');
    }

    private function logActivity(User $user, int $action, int $daysAgo): void
    {
        (new Activity())->forceFill([
            'user_id' => $user->id,
            'object_id' => $user->id,
            'object_table' => 'User',
            'object_name' => $user->name,
            'action_id' => $action,
            'created_at' => Carbon::now()->subDays($daysAgo),
            'updated_at' => Carbon::now()->subDays($daysAgo),
        ])->save();
    }

    /** Something for the digest to list: an event today the user is attending. */
    private function attendToday(User $user): void
    {
        $event = Event::factory()->create(['start_at' => Carbon::today()->setTime(12, 0), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        DB::table('event_responses')->insert([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => DB::table('response_types')->where('name', 'Attending')->value('id'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function test_a_dormant_subscriber_gets_one_paused_notice_instead_of_the_digest(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);

        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertNotSent(WeeklyUpdate::class);
        Mail::assertSent(DigestsPaused::class, fn (DigestsPaused $mail) => $mail->hasTo($user->email));
        $this->assertNotNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_after_the_notice_a_dormant_subscriber_gets_nothing(): void
    {
        $user = $this->subscriber();
        $this->attendToday($user);
        $this->artisan('notifyWeekly');

        Mail::fake();
        $this->artisan('notifyWeekly')->assertExitCode(0);

        // nothing to them; a seeded subscriber may get the Essential Events fallback
        $this->assertTrue(Mail::sent(fn (\Illuminate\Mail\Mailable $mail) => $mail->hasTo($user->email))->isEmpty());
    }

    public function test_the_resume_link_turns_digests_back_on(): void
    {
        $user = $this->subscriber();
        $this->attendToday($user);
        Mail::fake();
        $this->artisan('notifyWeekly');

        $resumeUrl = null;
        Mail::assertSent(DigestsPaused::class, function (DigestsPaused $mail) use (&$resumeUrl) {
            $mail->build();
            $resumeUrl = $mail->viewData['resumeUrl'];

            return true;
        });

        $this->get($resumeUrl)->assertOk()->assertSee('Your updates are back on');

        $profile = $user->profile->fresh();
        $this->assertNull($profile->digests_paused_at);
        $this->assertNotNull($profile->digests_confirmed_at);

        Mail::fake();
        $this->artisan('notifyWeekly');
        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_the_resume_link_needs_a_valid_signature(): void
    {
        $user = $this->subscriber();
        $user->profile->forceFill(['digests_paused_at' => now()])->save();

        $this->get("/email/digests/resume/{$user->id}")->assertForbidden();

        $this->assertNotNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_a_subscriber_who_logged_in_recently_gets_the_digest(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $this->logActivity($user, Action::LOGIN, 30);

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertNotSent(DigestsPaused::class);
    }

    public function test_activity_older_than_90_days_does_not_count(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $this->logActivity($user, Action::LOGIN, 91);

        $this->artisan('notifyWeekly');

        Mail::assertNotSent(WeeklyUpdate::class);
        Mail::assertSent(DigestsPaused::class);
    }

    public function test_the_digest_itself_and_failed_logins_do_not_count_as_activity(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        // notifyWeekly logs a NOTIFICATION against the recipient for every digest it sends
        $this->logActivity($user, Action::NOTIFICATION, 7);
        $this->logActivity($user, Action::FAILED_LOGIN, 3);
        $this->logActivity($user, Action::PASSWORD_RESET_REQUEST, 3);

        $this->artisan('notifyWeekly');

        Mail::assertNotSent(WeeklyUpdate::class);
        Mail::assertSent(DigestsPaused::class);
    }

    public function test_recent_api_token_use_counts_as_activity(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $user->createToken('frontend')->accessToken->forceFill(['last_used_at' => Carbon::now()->subDays(2)])->save();

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_a_new_account_is_engaged_without_any_activity(): void
    {
        Mail::fake();
        $user = $this->subscriber(accountAgeDays: 10);
        $this->attendToday($user);

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_a_paused_subscriber_who_comes_back_in_an_empty_week_is_unpaused(): void
    {
        // nothing in their digest this week, so nothing is sent, but the pause must still clear
        $user = $this->subscriber();
        $user->profile->forceFill(['digests_paused_at' => Carbon::now()->subDays(30)])->save();
        $this->logActivity($user, Action::LOGIN, 1);

        $this->artisan('notifyWeekly');

        $this->assertNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_the_footer_links_work_without_a_trailing_slash_on_app_url(): void
    {
        config()->set('app.url', 'http://localhost');
        $html = (new \App\Mail\AdminMailer('http://localhost', 'TestSite', 'admin@test.app', 'noreply@test.app'))->render();

        $this->assertStringContainsString('localhost/privacy', $html);
        $this->assertStringNotContainsString('localhostprivacy', $html);
    }

    public function test_a_paused_subscriber_who_comes_back_gets_digests_and_a_fresh_notice_next_time(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $user->profile->forceFill(['digests_paused_at' => Carbon::now()->subDays(30)])->save();
        $this->logActivity($user, Action::LOGIN, 1);

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class);
        $this->assertNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_the_daily_digest_is_gated_too_and_shares_the_one_notice(): void
    {
        Mail::fake();
        $user = $this->subscriber(weekly: 1, daily: 1);
        $this->attendToday($user);

        $this->artisan('notify')->assertExitCode(0);
        $this->artisan('notifyWeekly')->assertExitCode(0);

        Mail::assertNotSent(DailyReminder::class);
        Mail::assertNotSent(WeeklyUpdate::class);
        Mail::assertSent(DigestsPaused::class, 1);
    }

    public function test_an_active_daily_subscriber_still_gets_the_daily(): void
    {
        Mail::fake();
        $user = $this->subscriber(weekly: 0, daily: 1);
        $this->attendToday($user);
        $this->logActivity($user, Action::ATTENDING, 5);

        $this->artisan('notify');

        Mail::assertSent(DailyReminder::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_no_paused_notice_for_a_digest_that_would_have_been_empty(): void
    {
        // a dormant subscriber who was getting no digest isn't told it stopped
        Mail::fake();
        $user = $this->subscriber();

        $this->artisan('notifyWeekly');

        Mail::assertNothingSent();
        $this->assertNull($user->profile->fresh()->digests_paused_at);
    }

    public function test_dry_run_sends_and_changes_nothing_and_reports_counts(): void
    {
        Mail::fake();
        $dormant = $this->subscriber();
        $this->attendToday($dormant);
        $active = $this->subscriber();
        $this->attendToday($active);
        $this->logActivity($active, Action::LOGIN, 2);

        $this->artisan('notifyWeekly', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN: would send 1 weekly digest(s)')
            ->expectsOutputToContain('1 paused notice(s)')
            ->assertExitCode(0);
        $this->artisan('notify', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull($dormant->profile->fresh()->digests_paused_at);
    }

    public function test_the_paused_notice_renders_with_resume_and_preference_links(): void
    {
        $user = $this->subscriber(weekly: 1, daily: 1);

        $html = (new DigestsPaused('https://test.app/', 'TestSite', 'noreply@test.app', $user))->render();

        $this->assertStringContainsString('weekly update and daily reminder', $html);
        $this->assertStringContainsString("/email/digests/resume/{$user->id}?signature=", $html);
        $this->assertStringContainsString("/email/preferences/{$user->id}?signature=", $html);
    }

    public function test_the_preference_page_offers_resume_while_paused(): void
    {
        $user = $this->subscriber();
        $user->profile->forceFill(['digests_paused_at' => now()])->save();

        $this->get(URL::signedRoute('email.preferences', ['id' => $user->id]))
            ->assertOk()
            ->assertSee('Resume digests');
    }

    // --- confirmation and email clicks: readers who never log in ---

    public function test_a_confirmed_reader_is_never_paused_for_inactivity(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        // resumed long ago and hasn't logged in since
        $user->profile->forceFill(['digests_confirmed_at' => Carbon::now()->subDays(400)])->save();

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertNotSent(DigestsPaused::class);
    }

    public function test_saving_the_preference_page_with_a_digest_on_confirms_it(): void
    {
        $user = $this->subscriber();

        $this->post(URL::signedRoute('email.preferences', ['id' => $user->id]), ['lists' => ['weekly']]);

        $this->assertNotNull($user->profile->fresh()->digests_confirmed_at);
    }

    public function test_unsubscribing_from_everything_does_not_confirm(): void
    {
        $user = $this->subscriber();

        $this->post(URL::signedRoute('email.preferences', ['id' => $user->id]), ['unsubscribe_all' => '1']);

        $this->assertNull($user->profile->fresh()->digests_confirmed_at);
    }

    public function test_a_recent_email_click_counts_as_activity(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $user->profile->forceFill(['email_clicked_at' => Carbon::now()->subDays(20)])->save();

        $this->artisan('notifyWeekly');

        Mail::assertSent(WeeklyUpdate::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_an_email_click_older_than_90_days_does_not_count(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->attendToday($user);
        $user->profile->forceFill(['email_clicked_at' => Carbon::now()->subDays(120)])->save();

        $this->artisan('notifyWeekly');

        Mail::assertNotSent(WeeklyUpdate::class);
        Mail::assertSent(DigestsPaused::class);
    }

    private function sentWeekly(User $user): \Symfony\Component\Mime\Email
    {
        /** @var \Illuminate\Mail\Transport\ArrayTransport $transport */
        $transport = Mail::getSymfonyTransport();
        $transport->flush();

        $url = rtrim((string) config('app.url'), '/').'/';
        Mail::to($user->email)->send(new WeeklyUpdate($url, 'TestSite', 'admin@test.app', 'noreply@test.app', $user, $user->getAttendingFuture(), [], []));

        /** @var \Symfony\Component\Mime\Email $email */
        $email = $transport->messages()->last()->getOriginalMessage();

        return $email;
    }

    public function test_site_links_in_a_digest_go_through_the_click_tracker(): void
    {
        $user = $this->subscriber();
        $this->attendToday($user);
        $event = $user->getAttendingFuture()->first();

        $email = $this->sentWeekly($user);
        $html = html_entity_decode((string) $email->getHtmlBody());
        $text = (string) $email->getTextBody();

        $this->assertStringContainsString("/email/click/{$user->id}?to=", $html);
        $this->assertStringContainsString('to=%2Fevents%2F'.rawurlencode($event->slug), $html);
        $this->assertStringContainsString("/email/click/{$user->id}?to=", $text);

        // signed links are left alone so they keep working
        $this->assertStringContainsString(URL::signedRoute('email.preferences', ['id' => $user->id]), $html);
        // links elsewhere are left alone
        $this->assertStringContainsString('mailto:admin@test.app', $html);
    }

    public function test_following_a_tracked_link_records_the_click_and_redirects(): void
    {
        $user = $this->subscriber();
        $this->attendToday($user);
        $event = $user->getAttendingFuture()->first();

        // the event's own link, not the first tracked one (the header logo goes to /)
        $pattern = '#href="([^"]*/email/click/[^"]*to=%2Fevents%2F'.preg_quote(rawurlencode($event->slug), '#').'&[^"]*)"#';
        preg_match($pattern, (string) $this->sentWeekly($user)->getHtmlBody(), $m);
        $this->assertNotEmpty($m, 'expected a tracked link to the event in the digest');
        $link = html_entity_decode($m[1]);

        $this->get($link)->assertRedirect(url('/events/'.$event->slug));

        $this->assertNotNull($user->profile->fresh()->email_clicked_at);
    }

    public function test_a_tampered_click_link_still_redirects_but_credits_nobody(): void
    {
        $user = $this->subscriber();
        $other = $this->subscriber();
        $link = str_replace("/email/click/{$user->id}?", "/email/click/{$other->id}?", URL::signedRoute('email.click', ['id' => $user->id, 'to' => '/events']));

        $this->get($link)->assertRedirect(url('/events'));

        $this->assertNull($other->profile->fresh()->email_clicked_at);
    }

    public function test_the_click_tracker_only_redirects_within_the_site(): void
    {
        $user = $this->subscriber();

        foreach (['https://evil.example/x', '//evil.example/x', '/\\evil.example', 'javascript:alert(1)'] as $to) {
            $this->get('/email/click/'.$user->id.'?to='.urlencode($to))->assertRedirect(url('/'));
        }
    }

    // --- weekly series date check ---

    private function followedSeries(User $user, string $occurrence, Carbon $foundedAt): Series
    {
        $series = Series::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => DB::table('occurrence_types')->where('name', $occurrence)->value('id'),
            'founded_at' => $foundedAt,
            'cancelled_at' => null,
        ]);
        Follow::create(['user_id' => $user->id, 'object_type' => 'series', 'object_id' => $series->id]);

        return $series;
    }

    public function test_the_weekly_digest_lists_a_followed_series_occurring_this_week(): void
    {
        $user = $this->subscriber();
        $series = $this->followedSeries($user, 'Weekly', Carbon::now()->subDays(8));

        $listed = array_map(fn (Series $s) => $s->id, app(DigestBuilder::class)->weekly($user)->series);

        $this->assertSame([$series->id], $listed);
    }

    public function test_a_followed_series_with_no_occurrence_this_week_sends_no_weekly(): void
    {
        Mail::fake();
        $user = $this->subscriber();
        $this->logActivity($user, Action::LOGIN, 1);
        // next yearly occurrence is ~10 months away
        $this->followedSeries($user, 'Yearly', Carbon::now()->subDays(60));

        $this->assertSame([], app(DigestBuilder::class)->weekly($user)->series);

        $this->artisan('notifyWeekly');
        Mail::assertNotSent(WeeklyUpdate::class);
    }

    // --- new-signup defaults ---

    public function test_a_new_web_signup_gets_only_the_weekly_digest(): void
    {
        Validator::extend('captcha', fn () => true);
        \Illuminate\Support\Facades\Notification::fake();

        $this->post('/register', [
            'name' => 'Defaults Tester',
            'email' => 'defaults-tester@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'g-recaptcha-response' => 'valid-captcha-token',
        ]);

        $profile = User::where('email', 'defaults-tester@example.com')->sole()->profile;
        $this->assertNotNull($profile, 'web registration creates a profile');
        $profile->refresh();
        $this->assertSame(1, (int) $profile->setting_weekly_update);
        $this->assertSame(0, (int) $profile->setting_daily_update);
        $this->assertSame(0, (int) $profile->setting_instant_update);
        $this->assertSame(0, (int) $profile->setting_forum_update);
    }

    public function test_an_empty_profile_defaults_to_weekly_only(): void
    {
        // every other signup path (api/register, users.store, profile page) saves an empty profile
        $user = User::factory()->create();
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();
        $profile->refresh();

        $this->assertSame(1, (int) $profile->setting_weekly_update);
        $this->assertSame(0, (int) $profile->setting_daily_update);
        $this->assertSame(0, (int) $profile->setting_instant_update);
        $this->assertSame(0, (int) $profile->setting_forum_update);
    }

    // --- adminTest ---

    public function test_admin_test_runs_weekly_not_daily(): void
    {
        $events = array_values(array_filter(
            app(Schedule::class)->events(),
            fn ($e) => str_contains((string) $e->command, 'adminTest')
        ));

        $this->assertCount(1, $events);
        $this->assertSame('0 12 * * 1', $events[0]->expression);
    }
}
