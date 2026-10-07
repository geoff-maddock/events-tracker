<?php

namespace App\Services\Sns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies that an HTTP(S) SNS message really came from Amazon SNS.
 *
 * This is AWS's documented procedure, the same one aws-php-sns-message-validator
 * implements, kept in-app so the webhook doesn't need a new composer package:
 * https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html
 *
 *  - the signing certificate must come over https from an sns.<region>.amazonaws.com host
 *  - the signature covers a fixed set of fields, in a fixed order, per message type
 *  - SignatureVersion 1 is SHA1withRSA, 2 is SHA256withRSA
 */
class SnsMessageValidator
{
    private const NOTIFICATION_KEYS = ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'];

    private const SUBSCRIPTION_KEYS = ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

    private const HOST_PATTERN = '/^sns\.[a-z0-9-]+\.amazonaws\.com(\.cn)?$/';

    /** @param array<string, mixed> $message */
    public function isValid(array $message): bool
    {
        $certUrl = $message['SigningCertURL'] ?? null;
        $signature = $message['Signature'] ?? null;

        if (!is_string($certUrl) || !is_string($signature) || !self::isSnsUrl($certUrl)
            || !str_ends_with((string) parse_url($certUrl, PHP_URL_PATH), '.pem')) {
            return false;
        }

        $algorithm = match ((string) ($message['SignatureVersion'] ?? '')) {
            '1' => OPENSSL_ALGO_SHA1,
            '2' => OPENSSL_ALGO_SHA256,
            default => null,
        };

        $stringToSign = $this->stringToSign($message);
        $decoded = base64_decode($signature, true);

        if ($algorithm === null || $stringToSign === null || $decoded === false) {
            return false;
        }

        $certificate = $this->certificate($certUrl);
        if ($certificate === null) {
            return false;
        }

        $key = openssl_pkey_get_public($certificate);
        if ($key === false) {
            return false;
        }

        return openssl_verify($stringToSign, $decoded, $key, $algorithm) === 1;
    }

    /**
     * An https URL on an SNS host. Used for SigningCertURL here and for
     * SubscribeURL by the webhook, so a forged message can't make us fetch
     * an arbitrary URL.
     */
    public static function isSnsUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && preg_match(self::HOST_PATTERN, strtolower((string) ($parts['host'] ?? ''))) === 1
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port']);
    }

    /** @param array<string, mixed> $message */
    private function stringToSign(array $message): ?string
    {
        $keys = match ($message['Type'] ?? null) {
            'Notification' => self::NOTIFICATION_KEYS,
            'SubscriptionConfirmation', 'UnsubscribeConfirmation' => self::SUBSCRIPTION_KEYS,
            default => null,
        };

        if ($keys === null) {
            return null;
        }

        $string = '';
        foreach ($keys as $key) {
            if (!array_key_exists($key, $message)) {
                // Subject is the only optional signed field
                if ($key === 'Subject') {
                    continue;
                }

                return null;
            }

            if (!is_string($message[$key])) {
                return null;
            }

            $string .= $key."\n".$message[$key]."\n";
        }

        return $string;
    }

    /** The PEM certificate, cached: SNS signs with the same cert for months. */
    private function certificate(string $url): ?string
    {
        $cacheKey = 'sns-signing-cert:'.sha1($url);

        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->get($url);
        } catch (\Throwable $e) {
            Log::warning('SnsMessageValidator: could not fetch the signing certificate', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        $pem = $response->successful() ? $response->body() : '';
        if (!str_contains($pem, 'BEGIN CERTIFICATE')) {
            return null;
        }

        Cache::put($cacheKey, $pem, now()->addDay());

        return $pem;
    }
}
