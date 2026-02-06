@props([
    'hasError' => false,
])

@php
$classes = 'flex h-9 w-full rounded-md border border-input bg-input px-3 py-1 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
if ($hasError) {
    $classes .= ' border-destructive focus-visible:ring-destructive';
}
$isPassword = $attributes->get('type') === 'password';
if ($isPassword) {
    $classes .= ' pr-10'; // Add right padding for toggle button
}
@endphp

{{-- Password inputs are wrapped with a toggle button. Note: id attribute is required for password inputs --}}
@if($isPassword)
<div class="relative">
    <input {{ $attributes->merge(['class' => $classes]) }} />
    <button 
        type="button" 
        data-password-toggle="{{ $attributes->get('id') }}"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground transition-colors"
        aria-label="Show password"
    >
        <i class="bi bi-eye text-base"></i>
    </button>
</div>
@else
<input {{ $attributes->merge(['class' => $classes]) }} />
@endif
