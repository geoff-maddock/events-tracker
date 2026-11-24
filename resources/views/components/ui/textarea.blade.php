@props([
    'hasError' => false,
    'value' => '',
])
@aware([
    'error' => null,
    'helpText' => null,
    'required' => false,
])

@php
$classes = 'flex min-h-[80px] w-full rounded-md border border-input bg-input px-3 py-2 text-base shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
// what the form group around this control says about it
$state = \App\View\FieldState::attributes($attributes->get('id'), (bool) $hasError, $error, $helpText, $required);
@endphp

<textarea {{ $attributes->merge($state)->merge(['class' => $classes]) }}>{{ $value ?: $slot }}</textarea>
