<?php

namespace App\Http\Controllers;

use App\Models\EmailSuppression;
use App\Services\Sns\SnsMessageValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SES bounce and complaint notifications, delivered by SNS (#2103).
 *
 * Writes permanent bounces and complaints to email_suppressions, which
 * BlockSuppressedRecipients checks on every send. Before this the table only
 * filled from a manual ImportEmailSuppressions run.
 *
 * Nothing is trusted until the SNS signature verifies and the topic is one of
 * services.ses_notifications.topic_arns. Accepts both SES notification
 * formats: identity notifications ("notificationType") and configuration-set
 * event publishing ("eventType").
 */
class SesWebhookController extends Controller
{
    public function __invoke(Request $request, SnsMessageValidator $validator): Response
    {
        // SNS posts JSON with a text/plain content type, so read the raw body
        $message = json_decode($request->getContent(), true);
        if (!is_array($message)) {
            return response('Not an SNS message', 400);
        }

        if (!$validator->isValid($message)) {
            Log::warning('SesWebhook: rejected a message that failed SNS signature verification', [
                'type' => $message['Type'] ?? null,
                'topic' => $message['TopicArn'] ?? null,
            ]);

            return response('Invalid signature', 403);
        }

        $topics = (array) config('services.ses_notifications.topic_arns', []);
        if (!in_array($message['TopicArn'] ?? null, $topics, true)) {
            Log::warning('SesWebhook: rejected a message from an unexpected topic', ['topic' => $message['TopicArn'] ?? null]);

            return response('Unknown topic', 403);
        }

        switch ($message['Type']) {
            case 'SubscriptionConfirmation':
                $this->confirmSubscription($message);
                break;
            case 'Notification':
                $notification = json_decode((string) $message['Message'], true);
                if (is_array($notification)) {
                    $this->record($notification);
                }
                break;
        }

        return response()->noContent();
    }

    /** @param array<string, mixed> $message */
    private function confirmSubscription(array $message): void
    {
        $url = $message['SubscribeURL'] ?? null;
        if (!is_string($url) || !SnsMessageValidator::isSnsUrl($url)) {
            return;
        }

        Http::timeout(5)->get($url);
        Log::info('SesWebhook: confirmed the SNS subscription', ['topic' => $message['TopicArn']]);
    }

    /** @param array<string, mixed> $notification */
    private function record(array $notification): void
    {
        $type = $notification['notificationType'] ?? $notification['eventType'] ?? null;

        if ($type === 'Bounce') {
            $bounce = (array) ($notification['bounce'] ?? []);

            // Transient and undetermined bounces (full mailbox, greylisting) can succeed later
            if (($bounce['bounceType'] ?? null) !== 'Permanent') {
                return;
            }

            foreach ((array) ($bounce['bouncedRecipients'] ?? []) as $recipient) {
                $recipient = (array) $recipient;
                $this->suppress($recipient, EmailSuppression::REASON_BOUNCE, $recipient['status'] ?? null, $recipient['diagnosticCode'] ?? null, $bounce['timestamp'] ?? null);
            }
        } elseif ($type === 'Complaint') {
            $complaint = (array) ($notification['complaint'] ?? []);

            foreach ((array) ($complaint['complainedRecipients'] ?? []) as $recipient) {
                $this->suppress((array) $recipient, EmailSuppression::REASON_COMPLAINT, $complaint['complaintFeedbackType'] ?? null, null, $complaint['timestamp'] ?? null);
            }
        }
    }

    /** @param array<string, mixed> $recipient */
    private function suppress(array $recipient, string $reason, mixed $code, mixed $error, mixed $timestamp): void
    {
        $email = $recipient['emailAddress'] ?? null;
        if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $at = is_string($timestamp) ? Carbon::parse($timestamp) : now();
        } catch (\Throwable) {
            $at = now();
        }

        EmailSuppression::suppress($email, $reason, 'ses', [
            'code' => is_string($code) ? mb_substr($code, 0, 16) : null,
            'error' => is_string($error) ? $error : null,
            'suppressed_at' => $at,
        ]);

        Log::info('SesWebhook: suppressed an address', ['email' => EmailSuppression::normalize($email), 'reason' => $reason]);
    }
}
