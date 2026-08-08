<?php

namespace Tests\Unit\Services;

use App\Exceptions\DiscordWebhookException;
use App\Services\Integrations\Discord\DiscordWebhookClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Discord webhook transport (issue #2058).
 *
 * These assertions are only possible because the client uses the Http facade:
 * the raw cURL style of the Instagram integration is invisible to Http::fake()
 * and could not be tested without hitting the network.
 */
class DiscordWebhookClientTest extends TestCase
{
    private const WEBHOOK = 'https://discord.com/api/webhooks/123456789012345678/token';

    private function client(): DiscordWebhookClient
    {
        return new DiscordWebhookClient;
    }

    public function test_a_successful_post_returns_the_message_identifiers(): void
    {
        Http::fake([
            '*' => Http::response(['id' => '999', 'channel_id' => '555'], 200),
        ]);

        $result = $this->client()->send(self::WEBHOOK, ['embeds' => [['title' => 'x']]]);

        $this->assertSame('999', $result['message_id']);
        $this->assertSame('555', $result['channel_id']);
    }

    public function test_it_requests_the_created_message_back(): void
    {
        Http::fake(['*' => Http::response(['id' => '1'], 200)]);

        $this->client()->send(self::WEBHOOK, ['embeds' => []]);

        // Without wait=true Discord answers 204 with no body, and the message
        // id — needed to reference the post later — is unrecoverable.
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'wait=true')
            && array_key_exists('embeds', $request->data()));
    }

    public function test_a_short_rate_limit_is_absorbed_and_retried(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['retry_after' => 0.1], 429)
                ->push(['id' => '7'], 200),
        ]);

        $result = $this->client()->send(self::WEBHOOK, []);

        $this->assertSame('7', $result['message_id']);
        Http::assertSentCount(2);
    }

    public function test_a_long_rate_limit_is_handed_back_to_the_queue(): void
    {
        config(['discord.max_retry_sleep_seconds' => 0.2, 'discord.max_retries' => 2]);

        Http::fake(['*' => Http::response(['retry_after' => 30], 429)]);

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            // Retryable, not permanent: a worker must never be parked for
            // half a minute waiting out a rate limit.
            $this->assertFalse($e->permanent);
            $this->assertSame(429, $e->status);
        }
    }

    public function test_a_deleted_webhook_is_permanent_and_recognised(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Unknown Webhook', 'code' => 10015], 404),
        ]);

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            $this->assertTrue($e->permanent);
            $this->assertSame(10015, $e->discordCode);
            $this->assertTrue($e->isUnknownWebhook());
        }
    }

    public function test_a_rejected_payload_is_permanent(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid Form Body', 'code' => 50035], 400),
        ]);

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            $this->assertTrue($e->permanent);
            $this->assertFalse($e->isUnknownWebhook());
            // Discord's 400s are otherwise opaque, so its own wording is kept.
            $this->assertStringContainsString('Invalid Form Body', $e->getMessage());
        }
    }

    public function test_a_server_error_is_retryable(): void
    {
        config(['discord.max_retries' => 2]);

        Http::fake(['*' => Http::response('', 502)]);

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            $this->assertFalse($e->permanent);
            $this->assertSame(502, $e->status);
        }
    }

    public function test_a_connection_failure_is_retryable(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            $this->assertFalse($e->permanent);
        }
    }

    public function test_failure_messages_never_carry_the_webhook_token(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unknown Webhook', 'code' => 10015], 404)]);

        try {
            $this->client()->send(self::WEBHOOK, []);
            $this->fail('Expected a DiscordWebhookException.');
        } catch (DiscordWebhookException $e) {
            // This message reaches logs, the ledger and admin screens.
            $this->assertStringNotContainsString('token', $e->getMessage());
            $this->assertStringNotContainsString('discord.com/api/webhooks', $e->getMessage());
        }
    }
}
