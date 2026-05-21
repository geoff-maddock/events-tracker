@extends('layouts.app-tw')

@section('title', 'Notifications & Background Jobs')

@php
    $statusStyles = [
        'queued'    => ['bi-hourglass-split', 'text-amber-600', 'Queued'],
        'running'   => ['bi-arrow-repeat',    'text-blue-600',  'Running'],
        'succeeded' => ['bi-check-circle',    'text-green-600', 'Succeeded'],
        'failed'    => ['bi-x-circle',        'text-red-600',   'Failed'],
    ];
    $hasActiveJobs = $jobStatuses->whereIn('status', ['queued', 'running'])->isNotEmpty();
@endphp

@section('content')
<div class="max-w-4xl mx-auto w-full overflow-x-hidden">
    <div class="mb-4 md:mb-8 flex items-center justify-between">
        <h1 class="text-2xl md:text-3xl font-bold text-foreground">
            <i class="bi bi-bell mr-2"></i>Notifications
        </h1>
        @if ($notifications->whereNull('read_at')->isNotEmpty())
        <form action="{{ route('job-status.notifications.read') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-muted-foreground hover:text-foreground">
                <i class="bi bi-check2-all mr-1"></i>Mark all read
            </button>
        </form>
        @endif
    </div>

    {{-- Notifications --}}
    <div class="space-y-2 mb-8">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <div class="card-tw p-4 flex items-start gap-3 {{ $notification->read_at ? 'opacity-60' : '' }}">
                <i class="bi {{ ($data['succeeded'] ?? false) ? 'bi-check-circle text-green-600' : 'bi-x-circle text-red-600' }} text-xl mt-0.5"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-foreground font-medium">{{ $data['label'] ?? 'Background job' }}</p>
                    <p class="text-sm text-muted-foreground">{{ $data['message'] ?? '' }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @unless ($notification->read_at)
                <form action="{{ route('job-status.notifications.read') }}" method="POST">
                    @csrf
                    <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                    <button type="submit" class="text-xs text-muted-foreground hover:text-foreground" title="Mark read">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
                @endunless
            </div>
        @empty
            <div class="card-tw p-8 text-center text-muted-foreground">
                <i class="bi bi-bell-slash text-3xl mb-2"></i>
                <p>No notifications yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Background jobs --}}
    <h2 class="text-xl font-bold text-foreground mb-3">
        <i class="bi bi-list-task mr-2"></i>Recent Background Jobs
    </h2>
    <div class="space-y-2">
        @forelse ($jobStatuses as $job)
            @php [$icon, $color, $label] = $statusStyles[$job->status] ?? ['bi-question-circle', 'text-muted-foreground', ucfirst($job->status)]; @endphp
            <div class="card-tw p-4 flex items-start gap-3">
                <i class="bi {{ $icon }} {{ $color }} text-xl mt-0.5"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-foreground font-medium">{{ $job->label ?? $job->type }}</p>
                    <p class="text-sm {{ $color }}">{{ $label }}</p>
                    @if ($job->message)
                        <p class="text-sm text-muted-foreground">{{ $job->message }}</p>
                    @endif
                    <p class="text-xs text-muted-foreground mt-1">{{ $job->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div class="card-tw p-8 text-center text-muted-foreground">
                <i class="bi bi-inbox text-3xl mb-2"></i>
                <p>No background jobs yet.</p>
            </div>
        @endforelse
    </div>
</div>

@if ($hasActiveJobs)
{{-- Auto-refresh while jobs are still queued or running --}}
<script>
    setTimeout(function () { window.location.reload(); }, 6000);
</script>
@endif
@endsection
