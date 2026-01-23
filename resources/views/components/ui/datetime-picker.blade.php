@props([
    'name' => '',
    'id' => '',
    'value' => '',
    'hasError' => false,
    'placeholder' => 'Select date and time',
    'enableTime' => true,
    'dateFormat' => 'Y-m-d H:i',
    'altFormat' => 'F j, Y at h:i K',
    'minDate' => null,
    'maxDate' => null,
])

@php
$classes = 'w-full px-3 py-2 bg-input border rounded-lg text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors';
$classes .= $hasError ? ' border-destructive' : ' border-input';
@endphp

<input
    type="text"
    name="{{ $name }}"
    id="{{ $id }}"
    value="{{ $value }}"
    placeholder="{{ $placeholder }}"
    data-flatpickr
    data-enable-time="{{ $enableTime ? 'true' : 'false' }}"
    data-date-format="{{ $dateFormat }}"
    data-alt-format="{{ $altFormat }}"
    @if($minDate) data-min-date="{{ $minDate }}" @endif
    @if($maxDate) data-max-date="{{ $maxDate }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    autocomplete="off"
/>
