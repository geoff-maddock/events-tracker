<?php

namespace Tests\Feature;

use App\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * SES bounce/complaint notifications via SNS (#2103).
 *
 * Messages are signed with a throwaway key and self-signed certificate that
 * the faked SigningCertURL serves, so the real signature check runs.
 */
class SesWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const TOPIC = 'arn:aws:sns:us-east-2:123456789012:ses-notifications';

    private const CERT_URL = 'https://sns.us-east-2.amazonaws.com/SimpleNotificationService-0000000000000000000000.pem';

    /** @var \OpenSSLAsymmetricKey */
    private $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        config()->set('services.ses_notifications.topic_arns', [self::TOPIC]);

        $this->key = $this->newKey();
        $csr = openssl_csr_new(['commonName' => 'sns.amazonaws.com'], $this->key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $this->key, 1, ['digest_alg' => 'sha256']);
        openssl_x509_export($cert, $pem);

        Http::fake([
            'sns.us-east-2.amazonaws.com/*.pem' => Http::response($pem),
            'sns.us-east-2.amazonaws.com/?Action=ConfirmSubscription*' => Http::response('<ConfirmSubscriptionResponse/>'),
        ]);
    }

    private function newKey(): \OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($key);

        return $key;
    }

    /**
     * An SNS envelope signed the way SNS signs it (SignatureVersion 2).
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function sign(array $fields, ?\OpenSSLAsymmetricKey $key = null): array
    {
        $message = $fields + [
            'MessageId' => 'b1f7d5b1-0000-0000-0000-000000000000',
            'TopicArn' => self::TOPIC,
            'Timestamp' => '2026-10-07T12:00:00.000Z',
            'SignatureVersion' => '2',
            'SigningCertURL' => self::CERT_URL,
        ];

        $keys = $message['Type'] === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $string = '';
        foreach ($keys as $k) {
            if (array_key_exists($k, $message)) {
                $string .= $k."\n".$message[$k]."\n";
            }
        }

        openssl_sign($string, $signature, $key ?? $this->key, OPENSSL_ALGO_SHA256);
        $message['Signature'] = base64_encode($signature);

        return $message;
    }

    /** @param array<string, mixed> $ses */
    private function notification(array $ses): array
    {
        return $this->sign(['Type' => 'Notification', 'Message' => json_encode($ses)]);
    }

    private function permanentBounce(string $email): array
    {
        return [
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bounceSubType' => 'General',
                'timestamp' => '2026-10-07T11:59:00.000Z',
                'bouncedRecipients' => [
                    ['emailAddress' => $email, 'status' => '5.1.1', 'diagnosticCode' => 'smtp; 550 5.1.1 user unknown'],
                ],
            ],
            'mail' => ['destination' => [$email]],
        ];
    }

    /** SNS posts JSON with a text/plain content type. */
    private function deliver(array $message): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhooks/ses', [], [], [], ['CONTENT_TYPE' => 'text/plain; charset=UTF-8'], json_encode($message));
    }

    public function test_a_permanent_bounce_suppresses_the_address_and_blocks_the_next_send(): void
    {
        $this->deliver($this->notification($this->permanentBounce('Gone@Example.com')))->assertNoContent();

        $row = EmailSuppression::where('email', 'gone@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame(EmailSuppression::REASON_BOUNCE, $row->reason);
        $this->assertSame('ses', $row->source);
        $this->assertSame('5.1.1', $row->code);

        /** @var ArrayTransport $transport */
        $transport = Mail::getSymfonyTransport();
        $transport->flush();
        Mail::raw('hello', fn ($m) => $m->to('gone@example.com')->subject('Digest'));
        $this->assertCount(0, $transport->messages());
    }

    public function test_a_transient_bounce_is_ignored(): void
    {
        $bounce = $this->permanentBounce('full@example.com');
        $bounce['bounce']['bounceType'] = 'Transient';

        $this->deliver($this->notification($bounce))->assertNoContent();

        $this->assertFalse(EmailSuppression::isSuppressed('full@example.com'));
    }

    public function test_a_complaint_suppresses_the_address(): void
    {
        $this->deliver($this->notification([
            'notificationType' => 'Complaint',
            'complaint' => [
                'complaintFeedbackType' => 'abuse',
                'timestamp' => '2026-10-07T11:59:00.000Z',
                'complainedRecipients' => [['emailAddress' => 'angry@example.com']],
            ],
        ]))->assertNoContent();

        $row = EmailSuppression::where('email', 'angry@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame(EmailSuppression::REASON_COMPLAINT, $row->reason);
        $this->assertSame('abuse', $row->code);
    }

    public function test_configuration_set_event_publishing_format_is_understood(): void
    {
        $event = $this->permanentBounce('event@example.com');
        unset($event['notificationType']);
        $event['eventType'] = 'Bounce';

        $this->deliver($this->notification($event))->assertNoContent();

        $this->assertTrue(EmailSuppression::isSuppressed('event@example.com'));
    }

    public function test_a_message_signed_with_another_key_is_rejected(): void
    {
        $message = $this->sign(
            ['Type' => 'Notification', 'Message' => json_encode($this->permanentBounce('victim@example.com'))],
            $this->newKey()
        );

        $this->deliver($message)->assertForbidden();

        $this->assertFalse(EmailSuppression::isSuppressed('victim@example.com'));
    }

    public function test_a_message_altered_after_signing_is_rejected(): void
    {
        $message = $this->notification($this->permanentBounce('real@example.com'));
        $message['Message'] = json_encode($this->permanentBounce('victim@example.com'));

        $this->deliver($message)->assertForbidden();

        $this->assertFalse(EmailSuppression::isSuppressed('victim@example.com'));
        $this->assertFalse(EmailSuppression::isSuppressed('real@example.com'));
    }

    public function test_an_unsigned_message_is_rejected(): void
    {
        $message = $this->notification($this->permanentBounce('victim@example.com'));
        unset($message['Signature']);

        $this->deliver($message)->assertForbidden();

        $this->assertFalse(EmailSuppression::isSuppressed('victim@example.com'));
    }

    public function test_a_certificate_from_outside_amazon_is_never_fetched(): void
    {
        $message = $this->sign([
            'Type' => 'Notification',
            'Message' => json_encode($this->permanentBounce('victim@example.com')),
            'SigningCertURL' => 'https://sns.us-east-2.amazonaws.com.evil.example/cert.pem',
        ]);

        $this->deliver($message)->assertForbidden();

        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'evil.example'));
        $this->assertFalse(EmailSuppression::isSuppressed('victim@example.com'));
    }

    public function test_a_validly_signed_message_from_another_topic_is_rejected(): void
    {
        $message = $this->sign([
            'Type' => 'Notification',
            'Message' => json_encode($this->permanentBounce('victim@example.com')),
            'TopicArn' => 'arn:aws:sns:us-east-2:999999999999:someone-elses-topic',
        ]);

        $this->deliver($message)->assertForbidden();

        $this->assertFalse(EmailSuppression::isSuppressed('victim@example.com'));
    }

    public function test_nothing_is_accepted_when_no_topic_is_configured(): void
    {
        config()->set('services.ses_notifications.topic_arns', []);

        $this->deliver($this->notification($this->permanentBounce('gone@example.com')))->assertForbidden();

        $this->assertFalse(EmailSuppression::isSuppressed('gone@example.com'));
    }

    public function test_a_subscription_confirmation_is_confirmed(): void
    {
        $subscribeUrl = 'https://sns.us-east-2.amazonaws.com/?Action=ConfirmSubscription&TopicArn='.urlencode(self::TOPIC).'&Token=abc';

        $this->deliver($this->sign([
            'Type' => 'SubscriptionConfirmation',
            'Message' => 'You have chosen to subscribe to the topic.',
            'SubscribeURL' => $subscribeUrl,
            'Token' => 'abc',
        ]))->assertNoContent();

        Http::assertSent(fn (HttpRequest $request) => $request->url() === $subscribeUrl);
    }

    public function test_garbage_is_a_bad_request(): void
    {
        $this->call('POST', '/webhooks/ses', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'not json')->assertStatus(400);
    }
}
