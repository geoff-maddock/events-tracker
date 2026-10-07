<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\AdminMailer;
use App\Mail\EntityReminder;
use App\Mail\EntityUpdateSummary;
use App\Mail\WeeklyUpdate;
use App\Models\Contact;
use App\Models\EmailOptOut;
use App\Models\Entity;
use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\EmailPreferences;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Logged-out unsubscribe, List-Unsubscribe headers and the preference page (#2103).
 *
 * The header tests send through the real Mailer with the array transport, as
 * EmailSuppressionTest does, because Mail::fake() never builds the Symfony
 * message the headers live on.
 */
class EmailUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('app.admin', 'admin@example.com');
        $this->transport()->flush();
    }

    private function transport(): ArrayTransport
    {
        /** @var ArrayTransport $transport */
        $transport = Mail::getSymfonyTransport();

        return $transport;
    }

    private function lastSent(): Email
    {
        $sent = $this->transport()->messages()->last();
        $this->assertNotNull($sent, 'expected a message to be sent');

        /** @var Email $email */
        $email = $sent->getOriginalMessage();

        return $email;
    }

    private function subscribedUser(): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'setting_weekly_update' => 1,
            'setting_daily_update' => 1,
            'setting_instant_update' => 1,
            'setting_forum_update' => 1,
        ]);

        return $user->fresh('profile');
    }

    private function weeklyUpdate(User $user): WeeklyUpdate
    {
        return new WeeklyUpdate('https://test.app/', 'TestSite', 'admin@test.app', 'noreply@test.app', $user, new Collection(), [], []);
    }

    private function entityWithContact(string $email): Entity
    {
        $entity = Entity::factory()->create();
        $contact = Contact::create(['name' => 'Booking', 'email' => $email]);
        $entity->contacts()->attach($contact->id);

        return $entity;
    }

    private function headerValue(Email $email, string $name): ?string
    {
        return $email->getHeaders()->get($name)?->getBodyAsString();
    }

    /** The URL inside a List-Unsubscribe header's angle brackets. */
    private function headerUrl(Email $email): string
    {
        $value = (string) $this->headerValue($email, 'List-Unsubscribe');
        $this->assertMatchesRegularExpression('/^<https?:\/\/[^>]+>$/', $value);

        return substr($value, 1, -1);
    }

    public function test_the_signed_link_unsubscribes_a_logged_out_user_and_confirms(): void
    {
        $user = $this->subscribedUser();

        $this->get(EmailPreferences::unsubscribeUrl($user, EmailPreferences::WEEKLY))
            ->assertOk()
            ->assertSee("You're unsubscribed", false)
            ->assertSee('Weekly update');

        $profile = $user->profile->fresh();
        $this->assertSame(0, $profile->setting_weekly_update);
        // only the one list
        $this->assertSame(1, $profile->setting_daily_update);
        $this->assertGuest();
    }

    public function test_a_tampered_signature_is_rejected_and_changes_nothing(): void
    {
        $user = $this->subscribedUser();
        $other = $this->subscribedUser();

        // a valid link for one user, pointed at another
        $url = str_replace(
            "/email/unsubscribe/{$user->id}/",
            "/email/unsubscribe/{$other->id}/",
            EmailPreferences::unsubscribeUrl($user, EmailPreferences::WEEKLY)
        );
        $this->get($url)->assertForbidden();

        $this->get(route('email.unsubscribe', ['id' => $user->id, 'list' => 'weekly']))->assertForbidden();

        $this->assertSame(1, $user->profile->fresh()->setting_weekly_update);
        $this->assertSame(1, $other->profile->fresh()->setting_weekly_update);
    }

    public function test_an_unknown_list_is_not_found(): void
    {
        $user = $this->subscribedUser();

        $this->get(EmailPreferences::unsubscribeUrl($user, 'nonsense'))->assertNotFound();
    }

    public function test_one_click_post_unsubscribes_with_no_page_interaction(): void
    {
        $user = $this->subscribedUser();

        $this->post(EmailPreferences::unsubscribeUrl($user, EmailPreferences::DAILY), ['List-Unsubscribe' => 'One-Click'])
            ->assertNoContent();

        $this->assertSame(0, $user->profile->fresh()->setting_daily_update);
        $this->assertSame(1, $user->profile->fresh()->setting_weekly_update);
    }

    public function test_one_click_posts_are_exempt_from_csrf_but_the_preference_form_is_not(): void
    {
        // The test runner skips CSRF checks entirely, so check the exemption list itself:
        // mail clients send the one-click POST with no session or token.
        $middleware = app(VerifyCsrfToken::class);
        $inExceptArray = (new \ReflectionMethod($middleware, 'inExceptArray'))->getClosure($middleware);

        $this->assertTrue($inExceptArray(Request::create('/email/unsubscribe/5/weekly', 'POST')));
        $this->assertTrue($inExceptArray(Request::create('/email/unsubscribe/contact', 'POST')));
        $this->assertTrue($inExceptArray(Request::create('/webhooks/ses', 'POST')));
        $this->assertFalse($inExceptArray(Request::create('/email/preferences/5', 'POST')));
    }

    public function test_the_preference_page_shows_and_saves_each_list(): void
    {
        $user = $this->subscribedUser();
        $url = EmailPreferences::preferencesUrl($user);

        $this->get($url)
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Weekly update')
            ->assertSee('Forum updates');

        $this->post($url, ['lists' => ['weekly', 'forum']])->assertRedirect($url);

        $profile = $user->profile->fresh();
        $this->assertSame(1, $profile->setting_weekly_update);
        $this->assertSame(0, $profile->setting_daily_update);
        $this->assertSame(0, $profile->setting_instant_update);
        $this->assertSame(1, $profile->setting_forum_update);
    }

    public function test_unsubscribe_from_all_turns_every_list_off(): void
    {
        $user = $this->subscribedUser();

        $this->post(EmailPreferences::preferencesUrl($user), ['lists' => ['weekly'], 'unsubscribe_all' => '1']);

        $profile = $user->profile->fresh();
        foreach (EmailPreferences::LISTS as $definition) {
            $this->assertSame(0, $profile->{$definition['setting']});
        }
    }

    public function test_the_preference_page_needs_a_valid_signature(): void
    {
        $user = $this->subscribedUser();

        $this->get("/email/preferences/{$user->id}")->assertForbidden();
        $this->post("/email/preferences/{$user->id}", ['unsubscribe_all' => '1'])->assertForbidden();

        $this->assertSame(1, $user->profile->fresh()->setting_weekly_update);
    }

    public function test_digests_carry_list_unsubscribe_headers_and_a_footer_link_to_the_preference_page(): void
    {
        $user = $this->subscribedUser();

        Mail::to($user->email)->send($this->weeklyUpdate($user));
        $email = $this->lastSent();

        $this->assertSame('List-Unsubscribe=One-Click', $this->headerValue($email, 'List-Unsubscribe-Post'));
        $this->assertStringContainsString("/email/unsubscribe/{$user->id}/weekly?signature=", $this->headerUrl($email));

        $html = html_entity_decode((string) $email->getHtmlBody());
        $this->assertStringContainsString(EmailPreferences::preferencesUrl($user), $html);
        // not the old login-walled /profile footer
        $this->assertStringNotContainsString('on your profile', strip_tags($html));
        $this->assertStringContainsString(EmailPreferences::preferencesUrl($user), (string) $email->getTextBody());
    }

    public function test_the_header_link_from_a_real_digest_works_as_one_click(): void
    {
        $user = $this->subscribedUser();

        Mail::to($user->email)->send($this->weeklyUpdate($user));
        $this->post($this->headerUrl($this->lastSent()), ['List-Unsubscribe' => 'One-Click'])->assertNoContent();

        $this->assertSame(0, $user->profile->fresh()->setting_weekly_update);
    }

    public function test_transactional_mail_has_no_list_unsubscribe_header(): void
    {
        Mail::to('someone@example.com')->send(new AdminMailer('https://test.app/', 'TestSite', 'admin@test.app', 'noreply@test.app'));

        $this->assertNull($this->headerValue($this->lastSent(), 'List-Unsubscribe'));
    }

    public function test_entity_contact_mail_carries_an_opt_out_for_the_recipient_address(): void
    {
        $entity = $this->entityWithContact('Booker@Example.com');

        $exit = Artisan::call('notifyEntities', ['--single' => (string) $entity->id]);
        $output = Artisan::output();
        $this->assertSame(0, $exit, $output);
        $this->assertCount(1, $this->transport()->messages(), $output);
        $email = $this->lastSent();

        $this->assertSame('List-Unsubscribe=One-Click', $this->headerValue($email, 'List-Unsubscribe-Post'));
        $url = $this->headerUrl($email);
        $this->assertStringContainsString('/email/unsubscribe/contact?email=booker%40example.com', $url);
        $this->assertStringContainsString($url, html_entity_decode((string) $email->getHtmlBody()));
    }

    public function test_an_entity_contact_that_opts_out_gets_no_further_reminders(): void
    {
        $entity = $this->entityWithContact('booker@example.com');

        $this->get(EmailPreferences::contactUnsubscribeUrl('booker@example.com'))
            ->assertOk()
            ->assertSee("You're unsubscribed", false);
        $this->assertTrue(EmailOptOut::isOptedOut('BOOKER@example.com', EmailOptOut::LIST_ENTITY_CONTACT));

        Mail::fake();
        $this->artisan('notifyEntities', ['--single' => (string) $entity->id])->assertExitCode(0);

        Mail::assertNotSent(EntityReminder::class);
    }

    public function test_an_entity_contact_one_click_post_opts_out(): void
    {
        $this->post(EmailPreferences::contactUnsubscribeUrl('booker@example.com'), ['List-Unsubscribe' => 'One-Click'])
            ->assertNoContent();

        $this->assertTrue(EmailOptOut::isOptedOut('booker@example.com', EmailOptOut::LIST_ENTITY_CONTACT));
    }

    public function test_a_forged_contact_opt_out_is_rejected(): void
    {
        $url = str_replace('booker%40example.com', 'victim%40example.com', EmailPreferences::contactUnsubscribeUrl('booker@example.com'));

        $this->get($url)->assertForbidden();

        $this->assertFalse(EmailOptOut::isOptedOut('victim@example.com', EmailOptOut::LIST_ENTITY_CONTACT));
    }

    public function test_the_entity_update_summary_is_not_sent_to_an_opted_out_contact(): void
    {
        $entity = $this->entityWithContact('booker@example.com');
        EmailOptOut::optOut('booker@example.com', EmailOptOut::LIST_ENTITY_CONTACT);

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'super_admin'])->id);

        Mail::fake();
        $this->actingAs($admin)->get("/entities/{$entity->id}/send-update-summary");

        Mail::assertNotSent(EntityUpdateSummary::class);
    }

    public function test_opting_out_of_entity_mail_does_not_block_account_mail(): void
    {
        EmailOptOut::optOut('booker@example.com', EmailOptOut::LIST_ENTITY_CONTACT);

        Mail::raw('reset your password', fn ($m) => $m->to('booker@example.com')->subject('Password reset'));

        $this->assertCount(1, $this->transport()->messages());
    }
}
