@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{-- $unsubscribeUrl is set by bulk mail (App\Mail\Concerns\HasUnsubscribeLink) and is a signed, no-login link --}}
@isset($unsubscribeUrl)
You received this email from {{ config('app.name') }}. You can [unsubscribe or choose which emails you get]({{ $unsubscribeUrl }}) without logging in, or view our [Privacy Policy]({{ url('privacy') }}).
@else
You received this email from {{ config('app.name') }}. You can change which emails you get on your [profile]({{ url('profile') }}), or view our [Privacy Policy]({{ url('privacy') }}).
@endisset
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
