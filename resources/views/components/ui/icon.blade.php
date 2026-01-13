@props([
    'name' => '',
    'size' => 'base', // xs, sm, base, lg, xl, 2xl, 4xl
])

@php
$sizeClasses = [
    'xs' => 'text-xs',
    'sm' => 'text-sm',
    'base' => 'text-base',
    'lg' => 'text-lg',
    'xl' => 'text-xl',
    '2xl' => 'text-2xl',
    '4xl' => 'text-4xl',
];

$sizeClass = $sizeClasses[$size] ?? $sizeClasses['base'];
@endphp

<i {{ $attributes->merge(['class' => "bi bi-{$name} {$sizeClass}"]) }}></i>
