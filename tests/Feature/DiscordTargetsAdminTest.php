<?php

namespace Tests\Feature;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Admin management of Discord targets (issue #2058).
 *
 * The gating and secret-handling assertions carry the weight here: a webhook
 * URL is a bearer credential, so "an ordinary user cannot reach this" and
 * "the token never reaches the page" are the properties worth protecting.
 */
class DiscordTargetsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const WEBHOOK = 'https://discord.com/api/webhooks/123456789012345678/aSecretTokenValue';

    private function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Techno Channel',
            'webhook_url' => self::WEBHOOK,
            'is_enabled' => '1',
            'match_all' => '1',
            'requires_photo' => '1',
            'post_on_create' => '1',
            'allow_manual' => '1',
            'digest_day' => 4,
            'digest_hour' => 10,
            'digest_window_days' => 7,
        ], $overrides);
    }

    public function test_ordinary_users_cannot_reach_any_of_it(): void
    {
        $this->withExceptionHandling();

        $target = DiscordTarget::factory()->create();
        $this->signIn();

        $this->get(route('discord-targets.index'))->assertForbidden();
        $this->get(route('discord-targets.create'))->assertForbidden();
        $this->get(route('discord-targets.show', ['discordTarget' => $target->id]))->assertForbidden();
        $this->get(route('discord-targets.edit', ['discordTarget' => $target->id]))->assertForbidden();
        $this->post(route('discord-targets.store'), $this->validPayload())->assertForbidden();
        $this->post(route('discord-targets.test', ['discordTarget' => $target->id]))->assertForbidden();
        $this->delete(route('discord-targets.destroy', ['discordTarget' => $target->id]))->assertForbidden();

        $this->assertSame(1, DiscordTarget::count());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->withExceptionHandling();

        $this->get(route('discord-targets.index'))->assertRedirect(route('login'));
    }

    public function test_an_admin_can_create_a_target(): void
    {
        $this->signIn($this->admin());

        $this->post(route('discord-targets.store'), $this->validPayload())
            ->assertRedirect();

        $target = DiscordTarget::firstOrFail();
        $this->assertSame('Techno Channel', $target->name);
        $this->assertSame(self::WEBHOOK, $target->webhook_url);
        $this->assertTrue($target->match_all);
    }

    public function test_the_stored_webhook_is_encrypted_and_never_rendered(): void
    {
        $this->signIn($this->admin());
        $this->post(route('discord-targets.store'), $this->validPayload());

        $target = DiscordTarget::firstOrFail();

        $raw = DB::table('discord_targets')->where('id', $target->id)->value('webhook_url');
        $this->assertStringNotContainsString('aSecretTokenValue', (string) $raw);

        foreach (['show', 'edit'] as $view) {
            $this->get(route('discord-targets.'.$view, ['discordTarget' => $target->id]))
                ->assertOk()
                ->assertDontSee('aSecretTokenValue');
        }

        $this->get(route('discord-targets.index'))->assertOk()->assertDontSee('aSecretTokenValue');
    }

    public function test_a_non_discord_url_is_rejected(): void
    {
        $this->withExceptionHandling();
        $this->signIn($this->admin());

        $this->post(route('discord-targets.store'), $this->validPayload([
            'webhook_url' => 'https://example.com/hook/abc',
        ]))->assertSessionHasErrors('webhook_url');

        $this->assertSame(0, DiscordTarget::count());
    }

    public function test_a_target_with_no_filters_and_no_match_all_is_rejected(): void
    {
        $this->withExceptionHandling();
        $this->signIn($this->admin());

        // Rejecting this blocks both bad outcomes at once: an accidental
        // firehose, and a channel that looks configured but receives nothing.
        $this->post(route('discord-targets.store'), $this->validPayload(['match_all' => '0']))
            ->assertSessionHasErrors('include');

        $this->assertSame(0, DiscordTarget::count());
    }

    public function test_criteria_are_saved_and_replaced_on_update(): void
    {
        $this->signIn($this->admin());

        $techno = Tag::factory()->create();
        $ambient = Tag::factory()->create();

        $this->post(route('discord-targets.store'), $this->validPayload([
            'match_all' => '0',
            'include' => ['tag' => [$techno->id]],
            'exclude' => ['tag' => [$ambient->id]],
        ]));

        $target = DiscordTarget::firstOrFail();
        $this->assertSame(2, $target->criteria()->count());

        $this->patch(route('discord-targets.update', ['discordTarget' => $target->id]), $this->validPayload([
            'match_all' => '0',
            'include' => ['tag' => [$ambient->id]],
        ]));

        $criteria = $target->fresh()->criteria;
        $this->assertCount(1, $criteria);
        $this->assertSame($ambient->id, $criteria->first()->criteria_id);
        $this->assertSame(DiscordTargetCriterion::MODE_INCLUDE, $criteria->first()->mode);
    }

    public function test_updating_without_a_webhook_keeps_the_stored_one(): void
    {
        $this->signIn($this->admin());
        $this->post(route('discord-targets.store'), $this->validPayload());

        $target = DiscordTarget::firstOrFail();

        // The edit form deliberately does not render the secret back, so an
        // ordinary save submits a blank field.
        $this->patch(route('discord-targets.update', ['discordTarget' => $target->id]), $this->validPayload([
            'name' => 'Renamed',
            'webhook_url' => '',
        ]))->assertRedirect();

        $target = $target->fresh();
        $this->assertSame('Renamed', $target->name);
        $this->assertSame(self::WEBHOOK, $target->webhook_url);
    }

    public function test_reminder_offsets_are_parsed_from_the_form(): void
    {
        $this->signIn($this->admin());

        $this->post(route('discord-targets.store'), $this->validPayload([
            'post_reminders' => '1',
            'reminder_offsets_csv' => '1, 7, 7',
        ]));

        $this->assertSame([7, 1], DiscordTarget::firstOrFail()->reminder_offsets);
    }

    public function test_an_admin_can_send_a_test_message(): void
    {
        config(['discord.enabled' => true]);
        Http::fake(['*' => Http::response(['id' => '1'], 200)]);

        $this->signIn($this->admin());
        $target = DiscordTarget::factory()->create();

        $this->post(route('discord-targets.test', ['discordTarget' => $target->id]))
            ->assertRedirect();

        Http::assertSentCount(1);
    }

    public function test_a_failing_test_message_reports_back_instead_of_throwing(): void
    {
        config(['discord.enabled' => true]);
        Http::fake(['*' => Http::response(['message' => 'Unknown Webhook', 'code' => 10015], 404)]);

        $this->signIn($this->admin());
        $target = DiscordTarget::factory()->create();

        $this->post(route('discord-targets.test', ['discordTarget' => $target->id]))
            ->assertRedirect()
            ->assertSessionHas('flash_message');

        $this->assertFalse($target->fresh()->is_enabled);
    }

    public function test_toggling_re_enables_an_auto_disabled_target(): void
    {
        $this->signIn($this->admin());

        $target = DiscordTarget::factory()->disabled()->create(['consecutive_failures' => 5]);

        $this->post(route('discord-targets.toggle', ['discordTarget' => $target->id]))
            ->assertRedirect();

        $target = $target->fresh();
        $this->assertTrue($target->is_enabled);
        // The streak must reset, or the next single failure disables it again.
        $this->assertSame(0, $target->consecutive_failures);
        $this->assertNull($target->disabled_reason);
    }

    public function test_deleting_a_target_removes_it_from_the_list(): void
    {
        $this->signIn($this->admin());
        $target = DiscordTarget::factory()->create();

        $this->delete(route('discord-targets.destroy', ['discordTarget' => $target->id]))
            ->assertRedirect(route('discord-targets.index'));

        $this->assertSoftDeleted('discord_targets', ['id' => $target->id]);
    }
}
