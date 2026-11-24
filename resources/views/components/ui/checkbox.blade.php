@props([
    'hasError' => false,
    'checked' => false,
])
@aware([
    'error' => null,
    'helpText' => null,
    'required' => false,
])

@php
$classes = 'h-4 w-4 rounded border border-input bg-transparent text-primary shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
// what the form group around this control says about it
$state = \App\View\FieldState::attributes($attributes->get('id'), (bool) $hasError, $error, $helpText, $required);
@endphp

<input type="checkbox" @checked($checked) {{ $attributes->merge($state)->merge(['class' => $classes]) }} />
