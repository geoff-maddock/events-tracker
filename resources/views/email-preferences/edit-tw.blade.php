@extends('layouts.app-tw')

@section('title', 'Email Preferences')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-2">Email preferences</h1>
    <p class="text-muted-foreground mb-6">Choose which emails {{ config('app.app_name') }} sends to {{ $recipient->email }}.</p>

    <div class="card-tw p-6">
        <form method="POST" action="{{ $updateUrl }}" class="space-y-6">
            @csrf

            <div class="space-y-4">
                @foreach ($lists as $list => $definition)
                    <div class="flex items-start gap-3">
                        <x-ui.checkbox
                            name="lists[]"
                            id="list_{{ $list }}"
                            value="{{ $list }}"
                            :checked="(int) $profile->{$definition['setting']} === 1" />
                        <div class="flex-1">
                            <x-ui.label for="list_{{ $list }}">{{ $definition['label'] }}</x-ui.label>
                            <p class="text-sm text-muted-foreground">{{ $definition['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit">Save preferences</x-ui.button>
                <x-ui.button type="submit" name="unsubscribe_all" value="1" variant="outline">Unsubscribe from all</x-ui.button>
            </div>
        </form>
    </div>

    <p class="text-sm text-muted-foreground mt-4">
        Account emails, like password resets, are still sent when you ask for them.
    </p>
</div>
@endsection
