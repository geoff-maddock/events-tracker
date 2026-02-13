<?php

namespace App\Http\Controllers;

use App\Models\ClickTrack;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ClickTrackController extends Controller
{
    /**
     * Track click and redirect to event ticket link
     *
     * @param Request $request
     * @param int $eventId
     * @return RedirectResponse
     */
    public function redirect(Request $request, int $eventId): RedirectResponse
    {
        // Find the event
        $event = Event::find($eventId);

        // If event doesn't exist or has no ticket link, redirect to event page
        if (!$event || !$event->ticket_link) {
            if ($event) {
                return redirect()->route('events.show', $event->id);
            }
            return redirect()->route('home');
        }

        // Collect tracking data
        $tags = $event->tags->pluck('name')->implode(',');
        
        // Create click tracking record
        ClickTrack::create([
            'event_id' => $event->id,
            'venue_id' => $event->venue_id,
            'promoter_id' => $event->promoter_id,
            'tags' => $tags,
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'ip_address' => $request->ip(),
            'clicked_at' => Carbon::now(),
        ]);

        // Get the ticket link and attach referral params if needed
        $ticketUrl = $event->ticket_link;
        
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

        // Redirect to the ticket URL
        return redirect()->away($redirectUrl);
    }
}
