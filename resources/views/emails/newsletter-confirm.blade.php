@component('mail::message')

# Confirm your subscription

You (or someone using this address) asked to receive the weekly **Essential Events** digest from {{ $site }} — a curated email of the events we think you shouldn't miss.

Click the button below to confirm your subscription. If you didn't request this, ignore this email and you won't be subscribed.

@component('mail::button', ['url' => $confirmUrl])
Confirm Subscription
@endcomponent

Thanks!
{{ $site }}
{{ $url }}
@endcomponent
