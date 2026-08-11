@php
    // Built as a PHP array and json_encode()d rather than interpolated into a
    // JSON string literal. The old hand-written form escaped values with e(),
    // which leaves newlines and backslashes intact — either one inside a JSON
    // string is invalid, so any event with a multi-line description emitted
    // markup Google could not parse. json_encode also removes the need to
    // escape the @context key past Laravel 12's @context Blade directive.
    $jsonLd = \App\Services\EventSchema::document($event);
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
