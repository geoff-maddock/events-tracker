@props([
    'variant' => 'default',
    'size' => 'default',
    'href' => null,
    'loading' => false,
])

@php
$base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,background-color,border-color,transform,opacity] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50';

$variants = [
    'default' => 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
    'primary' => 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
    'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
    'outline' => 'border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground',
    'secondary' => 'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
    'ghost' => 'hover:bg-accent hover:text-accent-foreground',
    'link' => 'text-primary underline-offset-4 hover:underline',
];

$sizes = [
    'default' => 'h-9 px-4 py-2',
    'sm' => 'h-8 rounded-md px-3 text-xs',
    'lg' => 'h-10 rounded-md px-8',
    'icon' => 'h-9 w-9',
];

$classes = $base
    . ' ' . ($variants[$variant] ?? $variants['default'])
    . ' ' . ($sizes[$size] ?? $sizes['default']);

if ($loading) {
    $classes .= $href ? ' opacity-60 pointer-events-none' : '';
}
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($loading) aria-busy="true" @endif>
        @if($loading)<span class="btn-spinner" aria-hidden="true"></span>@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }} @if($loading) disabled aria-busy="true" @endif>
        @if($loading)<span class="btn-spinner" aria-hidden="true"></span>@endif
        {{ $slot }}
    </button>
@endif
