@props([
    'hasError' => false,
])
@aware([
    'error' => null,
    'helpText' => null,
    'required' => false,
])

@php
$classes = 'flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-input px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus:ring-destructive';
}
// what the form group around this control says about it
$state = \App\View\FieldState::attributes($attributes->get('id'), (bool) $hasError, $error, $helpText, $required);
@endphp

<select {{ $attributes->merge($state)->merge(['class' => $classes]) }}>
    {{ $slot }}
</select>
