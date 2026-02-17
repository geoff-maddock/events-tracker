<?php

namespace App\Http\Controllers;

use App\Helpers\BotDetector;
use App\Models\ClickTrack;
use App\Models\Event;
use App\Models\Series;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ClickTrackController extends Controller
{
    /**
     * Track click and redirect to event ticket link
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function redirectEvent(Request $request, int $id): RedirectResponse
    {
        // Find the event
        $event = Event::find($id);

        // If event doesn't exist or has no ticket link, redirect to event page
        if (!$event || !$event->ticket_link) {
            if ($event) {
                return redirect()->route('events.show', $event->id);
            }
            return redirect()->route('home');
        }

        // Check if the request is from a bot/crawler
        if (BotDetector::isBot($request->userAgent())) {
            // Redirect without tracking if it's a bot
            return redirect()->away($this->attachReferralParams($event->ticket_link));
        }

        // Collect tracking data
        $tags = $event->tags->pluck('name')->implode(',');
        
        // Create click tracking record
        ClickTrack::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(), // Will be null for anonymous users
            'venue_id' => $event->venue_id,
            'promoter_id' => $event->promoter_id,
            'tags' => $tags,
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'ip_address' => $request->ip(),
            'clicked_at' => Carbon::now(),
        ]);

        // Redirect with referral params
        return redirect()->away($this->attachReferralParams($event->ticket_link));
    }

    /**
     * Track click and redirect to series ticket link
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function redirectSeries(Request $request, int $id): RedirectResponse
    {
        // Find the series
        $series = Series::find($id);

        // If series doesn't exist or has no ticket link, redirect to series page
        if (!$series || !$series->ticket_link) {
            if ($series) {
                return redirect()->route('series.show', $series->slug);
            }
            return redirect()->route('home');
        }

        // Check if the request is from a bot/crawler
        if (BotDetector::isBot($request->userAgent())) {
            // Redirect without tracking if it's a bot
            return redirect()->away($this->attachReferralParams($series->ticket_link));
        }

        // Collect tracking data
        $tags = $series->tags->pluck('name')->implode(',');
        
        // Create click tracking record
        ClickTrack::create([
            'event_id' => null,
            'user_id' => Auth::id(), // Will be null for anonymous users
            'venue_id' => $series->venue_id,
            'promoter_id' => $series->promoter_id,
            'tags' => $tags,
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'ip_address' => $request->ip(),
            'clicked_at' => Carbon::now(),
        ]);

        // Redirect with referral params
        return redirect()->away($this->attachReferralParams($series->ticket_link));
    }

    /**
     * Attach referral parameters to the ticket URL
     *
     * @param string $ticketUrl
     * @return string
     */
    private function attachReferralParams(string $ticketUrl): string
    {
        // Parse URL to add referral parameters
        $parsedUrl = parse_url($ticketUrl);
        $query = [];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        
        // Add referral parameter
        $query['ref'] = config('app.name', 'events-tracker');
        
        // Rebuild URL with parameters
        $newQuery = http_build_query($query);
        $redirectUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        if (isset($parsedUrl['port'])) {
            $redirectUrl .= ':' . $parsedUrl['port'];
        }
        
        if (isset($parsedUrl['path'])) {
            $redirectUrl .= $parsedUrl['path'];
        }
        
        if ($newQuery) {
            $redirectUrl .= '?' . $newQuery;
        }
        
        if (isset($parsedUrl['fragment'])) {
            $redirectUrl .= '#' . $parsedUrl['fragment'];
        }

        return $redirectUrl;
    }
}
