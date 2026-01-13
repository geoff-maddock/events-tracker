@props([
    'type' => 'info', // success, error, warning, info
    'dismissible' => false,
    'title' => '',
])

@php
$typeConfig = [
    'success' => [
        'bg' => 'bg-green-600/20 dark:bg-green-600/10',
        'border' => 'border-green-600/30',
        'text' => 'text-green-700 dark:text-green-300',
        'icon' => 'bi-check-circle',
    ],
    'error' => [
        'bg' => 'bg-destructive/20',
        'border' => 'border-destructive/30',
        'text' => 'text-destructive',
        'icon' => 'bi-exclamation-circle',
    ],
    'warning' => [
        'bg' => 'bg-yellow-500/20 dark:bg-yellow-500/10',
        'border' => 'border-yellow-500/30',
        'text' => 'text-yellow-700 dark:text-yellow-300',
        'icon' => 'bi-exclamation-triangle',
    ],
    'info' => [
        'bg' => 'bg-blue-600/20 dark:bg-blue-600/10',
        'border' => 'border-blue-600/30',
        'text' => 'text-blue-700 dark:text-blue-300',
        'icon' => 'bi-info-circle',
    ],
];

$config = $typeConfig[$type] ?? $typeConfig['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-lg border {$config['bg']} {$config['border']} {$config['text']}"]) }}
     role="alert">

    <div class="flex items-start gap-3">
        <!-- Icon -->
        <div class="flex-shrink-0">
            <i class="bi {{ $config['icon'] }} text-lg"></i>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            @if($title)
            <h3 class="font-semibold mb-1">{{ $title }}</h3>
            @endif

            <div class="text-sm">
                {{ $slot }}
            </div>
        </div>

        <!-- Dismiss Button -->
        @if($dismissible)
        <button type="button"
                class="flex-shrink-0 p-1 rounded-md hover:bg-black/10 dark:hover:bg-white/10 transition-colors"
                onclick="this.closest('[role=alert]').remove()">
            <i class="bi bi-x-lg"></i>
        </button>
        @endif
    </div>
</div>
