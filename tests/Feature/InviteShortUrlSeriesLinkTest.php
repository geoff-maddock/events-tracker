<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\ShortUrl;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invite is admin-only and validated; short URLs only point back into the site;
 * a new series can only pull in events the user may edit, and only admins set
 * its owner (#2165).
 */
class InviteShortUrlSeriesLinkTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    // Invite

    public function test_invite_is_admin_only_and_validated(): void
    {
        $this->post(route('pages.invite'), ['email' => 'someone@example.com'])->assertRedirect(route('login'));
        $this->actingAs($this->makeUser())->post(route('pages.invite'), ['email' => 'someone@example.com'])->assertForbidden();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('pages.invite'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_invite_existing_user_check_is_an_exact_match(): void
    {
        $this->makeUser()->forceFill(['email' => 'alice@example.com'])->save();
        $admin = $this->makeUser('admin');

        // "ice@example.com" used to LIKE-match alice@example.com and report "already exists"
        $this->actingAs($admin)->post(route('pages.invite'), ['email' => 'ice@example.com'])
            ->assertSessionMissing('flash_message.message', 'No email sent - a user with the address - ice@example.com - already exists on the site.');
        $this->assertStringNotContainsString('already exists', (string) session('flash_message.message'));

        $this->actingAs($admin)->post(route('pages.invite'), ['email' => 'alice@example.com']);
        $this->assertStringContainsString('already exists', (string) session('flash_message.message'));
    }

    // Short URLs

    public function test_only_links_to_this_site_can_be_shortened(): void
    {
        $this->postJson(route('short-url.shorten'), ['url' => 'https://evil.example/phish'])->assertStatus(422);
        $this->postJson(route('short-url.shorten'), ['url' => 'https://arcane.city.evil.example/x'])->assertStatus(422);

        $code = $this->postJson(route('short-url.shorten'), ['url' => config('app.url').'events?filters[tag]=punk'])
            ->assertOk()
            ->json('code');

        $this->get(route('short-url.redirect', ['code' => $code]))->assertRedirect(config('app.url').'events?filters[tag]=punk');
    }

    public function test_legacy_off_site_short_urls_do_not_redirect(): void
    {
        ShortUrl::create(['code' => 'zzlegacy', 'url' => 'https://evil.example/phish']);

        $this->get(route('short-url.redirect', ['code' => 'zzlegacy']))->assertNotFound();
    }

    // Series

    private function seriesPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ Linked Series',
            'slug' => 'zz-linked-series',
            'short' => 'A short description',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => OccurrenceType::where('name', 'No Schedule')->first()->id,
        ], $overrides);
    }

    public function test_series_cannot_pull_in_someone_elses_event(): void
    {
        $member = $this->makeUser();
        $theirs = Event::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($member)->post('/series', $this->seriesPayload(['eventLinkId' => $theirs->id]))->assertRedirect();

        $this->assertNull($theirs->fresh()->series_id);
    }

    public function test_series_owner_is_the_creator_unless_an_admin_sets_it(): void
    {
        $member = $this->makeUser();
        $victim = $this->makeUser();

        $this->actingAs($member)->post('/series', $this->seriesPayload(['created_by' => $victim->id]))->assertRedirect();
        $this->assertSame($member->id, (int) Series::where('slug', 'zz-linked-series')->value('created_by'));

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('/series', $this->seriesPayload(['slug' => 'zz-admin-series', 'name' => 'ZZ Admin Series', 'created_by' => $victim->id]))->assertRedirect();
        $this->assertSame($victim->id, (int) Series::where('slug', 'zz-admin-series')->value('created_by'));
    }

    public function test_series_owner_cannot_hand_the_series_to_someone_else(): void
    {
        $owner = $this->makeUser();
        $series = Series::factory()->create(['name' => 'ZZ Owned Series', 'slug' => 'zz-owned-series']);
        $series->forceFill(['created_by' => $owner->id])->saveQuietly();

        $this->actingAs($owner)->put(route('series.update', $series), $this->seriesPayload([
            'name' => 'ZZ Owned Series', 'slug' => 'zz-owned-series', 'created_by' => $this->makeUser()->id,
        ]))->assertRedirect();

        $this->assertSame($owner->id, (int) $series->fresh()->created_by);
    }
}
