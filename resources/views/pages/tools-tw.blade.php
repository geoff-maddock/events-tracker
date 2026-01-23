@extends('layouts.app-tw')

@section('title', 'Tools')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-6">Admin Tools</h1>

    <div class="grid gap-6">
        <!-- Import Photos Section -->
        <div class="card-tw p-6">
            <h2 class="text-xl font-semibold text-foreground mb-4">
                <i class="bi bi-image mr-2"></i>Photo Import
            </h2>
            <x-ui.button variant="secondary" href="{{ route('events.importPhotos') }}">
                <i class="bi bi-cloud-download mr-2"></i>Import Photos
            </x-ui.button>

            @if (count($events) > 0)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-muted-foreground mb-2">Events with Photos to Import</h3>
                    <ul class="space-y-2">
                        @foreach ($events as $event)
                            <li class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                <a href="{{ url('events/' . $event->id) }}" class="text-foreground hover:text-primary transition-colors">
                                    {{ $event->name }}
                                </a>
                                <x-ui.button variant="outline" size="sm" href="{{ url('events/' . $event->id . '/import-photo') }}">
                                    Import Photo
                                </x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Send Invites Section -->
        <div class="card-tw p-6">
            <h2 class="text-xl font-semibold text-foreground mb-4">
                <i class="bi bi-envelope mr-2"></i>Send Invites
            </h2>
            <form action="{{ route('pages.invite') }}" method="POST" class="space-y-4">
                @csrf
                <x-ui.form-group name="email" label="Email Addresses">
                    <x-ui.input
                        type="text"
                        name="email"
                        id="email"
                        placeholder="Enter email addresses (comma separated)" />
                </x-ui.form-group>
                <x-ui.button type="submit" variant="default">
                    <i class="bi bi-send mr-2"></i>Send Invites
                </x-ui.button>
            </form>
        </div>

        <!-- Purge Users Section -->
        <div class="card-tw p-6 border-destructive/50">
            <h2 class="text-xl font-semibold text-destructive mb-4">
                <i class="bi bi-exclamation-triangle mr-2"></i>Danger Zone
            </h2>
            <form action="{{ route('users.purge') }}" method="POST">
                @csrf
                <p class="text-muted-foreground mb-4">
                    This will permanently remove unverified users from the system.
                </p>
                <x-ui.button type="submit" variant="destructive" class="confirm">
                    <i class="bi bi-trash mr-2"></i>Purge Users
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
@stop

@section('footer')
<script src="{{ asset('/js/facebook-event.js') }}"></script>
<script>
    document.querySelectorAll('.confirm').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to do this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection
