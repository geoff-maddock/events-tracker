<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsletterSubscribersController extends Controller
{
    /**
     * Handle a public subscription request to the Essential Events digest.
     */
    public function subscribe(Request $request): RedirectResponse
    {
        // honeypot - bots fill the hidden field; respond as if successful
        if ($request->filled('website')) {
            flash()->success('Success', 'Check your email to confirm your subscription.');

            return back();
        }

        $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:64'],
        ]);

        $email = strtolower(trim($request->input('email')));

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber === null) {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'token' => Str::random(64),
                'source' => $request->input('source'),
            ]);
        } elseif ($subscriber->confirmed_at !== null && $subscriber->unsubscribed_at === null) {
            // already an active subscriber - same generic response, no email
            flash()->success('Success', 'Check your email to confirm your subscription.');

            return back();
        } elseif ($subscriber->unsubscribed_at !== null) {
            // resubscribing after an unsubscribe requires a fresh confirmation
            $subscriber->update(['confirmed_at' => null, 'unsubscribed_at' => null]);
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmSubscription(
            config('app.url'),
            config('app.app_name'),
            config('app.admin'),
            config('app.noreplyemail'),
            $subscriber
        ));

        Log::info('Newsletter confirmation email sent to '.$subscriber->email.'.');

        flash()->success('Success', 'Check your email to confirm your subscription.');

        return back();
    }

    /**
     * Confirm a subscription from the emailed double opt-in link.
     */
    public function confirm(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if ($subscriber->confirmed_at === null || $subscriber->unsubscribed_at !== null) {
            $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);

            Log::info('Newsletter subscription confirmed for '.$subscriber->email.'.');
        }

        return view('newsletter.confirmed', compact('subscriber'));
    }

    /**
     * Unsubscribe via the one-click link in every digest footer.
     *
     * Accepts GET (footer link) and POST (RFC 8058 List-Unsubscribe-Post).
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);

            Log::info('Newsletter unsubscribe for '.$subscriber->email.'.');
        }

        return view('newsletter.unsubscribed', compact('subscriber'));
    }
}
