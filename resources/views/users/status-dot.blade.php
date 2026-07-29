{{-- Colored status indicator dot; expects $user in scope. Renders nothing without a mapped status. --}}
@php
$statusDotClass = match ($user->user_status_id) {
    \App\Models\UserStatus::ACTIVE => 'bg-green-500',
    \App\Models\UserStatus::PENDING => 'bg-yellow-400',
    \App\Models\UserStatus::SUSPENDED => 'bg-orange-500',
    \App\Models\UserStatus::BANNED => 'bg-red-500',
    \App\Models\UserStatus::DELETED => 'bg-gray-400',
    default => null,
};
@endphp
@if ($statusDotClass)
<span data-status-dot
    class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-card {{ $statusDotClass }}"
    title="{{ optional($user->status)->name }}"></span>
@endif
