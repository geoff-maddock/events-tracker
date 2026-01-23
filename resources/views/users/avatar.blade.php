@php
    $size = $size ?? 'md'; // default to medium
    $sizeClasses = [
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16',
        '2xl' => 'w-24 h-24',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

@if ($photo = $user->getPrimaryPhoto())
<img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" 
    alt="{{ $user->name }}" 
    class="{{ $sizeClass }} rounded-full object-cover" 
    title="{{ $user->name }}">
@else
<div class="{{ $sizeClass }} rounded-full bg-card flex items-center justify-center border border-border">
    <i class="bi bi-person text-muted-foreground/50"></i>
</div>
@endif
