<?php

namespace App\Services\Integrations\Discord;

use App\Exceptions\DiscordWebhookException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Low-level Discord incoming-webhook client (issue #2058).
 *
 * Deliberately built on the Http facade rather than the raw cURL style of
 * app/Services/Integrations/Instagram.php: cURL is invisible to Http::fake(),
 * so that integration cannot be tested without hitting the network. Everything
 * outbound here is fakeable.
 *
 * This class knows nothing about events, targets or ledgers — it takes a URL
 * and a payload and either returns the created message ids or throws. Deciding
 * what to send, and what a failure means for a channel, is the poster's job.
 */
class DiscordWebhookClient
{
    /**
     * POST a webhook payload and return the created message's identifiers.
     *
     * ?wait=true makes Discord return the created message as JSON instead of a
     * bare 204, which is the only way to learn the message id — needed to
     * reference or edit the post later.
     *
     * @param  array<string, mixed>  $payload
     * @return array{message_id: ?string, channel_id: ?string}
     *
     * @throws DiscordWebhookException
     */
    public function send(string $webhookUrl, array $payload): array
    {
        $attempt = 0;
        $sleptSeconds = 0.0;
        $maxAttempts = max(1, (int) config('discord.max_retries', 3));
        $maxSleep = (float) config('discord.max_retry_sleep_seconds', 10);

        while (true) {
            $attempt++;

            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('discord.timeout', 10))
                    ->post($this->withWaitParam($webhookUrl), $payload);
            } catch (ConnectionException $e) {
                // Timeouts and DNS failures say nothing about the payload.
                throw new DiscordWebhookException(
                    'Could not reach Discord: '.$e->getMessage(),
                    permanent: false,
                    previous: $e,
                );
            }

            if ($response->successful()) {
                return $this->identifiers($response);
            }

            if (429 === $response->status()) {
                $wait = $this->retryAfterSeconds($response);

                // Absorb short rate limits in process, but never park a queue
                // worker for a long one — hand it back to the queue's backoff.
                if ($attempt < $maxAttempts && ($sleptSeconds + $wait) <= $maxSleep) {
                    $sleptSeconds += $wait;
                    usleep((int) round($wait * 1_000_000));

                    continue;
                }

                throw new DiscordWebhookException(
                    'Rate limited by Discord after '.$attempt.' attempt(s).',
                    permanent: false,
                    status: 429,
                );
            }

            if ($response->serverError()) {
                if ($attempt < $maxAttempts) {
                    continue;
                }

                throw new DiscordWebhookException(
                    'Discord returned '.$response->status().' after '.$attempt.' attempt(s).',
                    permanent: false,
                    status: $response->status(),
                );
            }

            // Any other 4xx is about the request itself: a malformed embed, or
            // a webhook that has been deleted. Retrying cannot help.
            throw new DiscordWebhookException(
                $this->describeFailure($response),
                permanent: true,
                status: $response->status(),
                discordCode: $this->discordCode($response),
            );
        }
    }

    /**
     * @return array{message_id: ?string, channel_id: ?string}
     */
    private function identifiers(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            return ['message_id' => null, 'channel_id' => null];
        }

        $messageId = $body['id'] ?? null;
        $channelId = $body['channel_id'] ?? null;

        return [
            'message_id' => is_scalar($messageId) ? (string) $messageId : null,
            'channel_id' => is_scalar($channelId) ? (string) $channelId : null,
        ];
    }

    /**
     * Discord reports rate limits in the body as fractional seconds, and in
     * headers as a fallback. Clamped so a malformed value can't stall a worker.
     */
    private function retryAfterSeconds(Response $response): float
    {
        $body = $response->json();
        $candidates = [];

        if (is_array($body) && isset($body['retry_after']) && is_numeric($body['retry_after'])) {
            $candidates[] = (float) $body['retry_after'];
        }

        foreach (['Retry-After', 'X-RateLimit-Reset-After'] as $header) {
            $value = $response->header($header);
            if ('' !== $value && is_numeric($value)) {
                $candidates[] = (float) $value;
            }
        }

        $wait = $candidates ? min($candidates) : 1.0;

        return max(0.1, min($wait, (float) config('discord.max_retry_sleep_seconds', 10)));
    }

    private function discordCode(Response $response): ?int
    {
        $body = $response->json();

        if (is_array($body) && isset($body['code']) && is_numeric($body['code'])) {
            return (int) $body['code'];
        }

        return null;
    }

    /**
     * A message safe to store and display. Discord's own message is included
     * because its 400s are otherwise opaque, but never the URL.
     */
    private function describeFailure(Response $response): string
    {
        $body = $response->json();
        $detail = is_array($body) && isset($body['message']) && is_string($body['message'])
            ? $body['message']
            : 'no detail supplied';

        $code = $this->discordCode($response);

        return 'Discord rejected the request ('.$response->status().'): '.$detail
            .(null !== $code ? ' [code '.$code.']' : '');
    }

    /**
     * Append wait=true without clobbering an existing query string.
     */
    private function withWaitParam(string $webhookUrl): string
    {
        if (str_contains($webhookUrl, 'wait=')) {
            return $webhookUrl;
        }

        return $webhookUrl.(str_contains($webhookUrl, '?') ? '&' : '?').'wait=true';
    }
}
