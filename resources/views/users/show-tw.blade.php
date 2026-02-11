@extends('layouts.app-tw')

@section('title', 'User Profile View')

@section('content')

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-foreground mb-2">User Profile <span class="text-muted-foreground">@include('users.crumbs')</span></h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-2 mb-6">
        @if ($canViewFullProfile)
            <a href="{{ route('users.attending', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <i class="bi bi-calendar-check mr-2"></i>
                Attending
            </a>

            <!-- iCal Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    <i class="bi bi-calendar-plus mr-2"></i>
                    iCal
                    <i class="bi bi-chevron-down ml-2 text-xs"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-48 bg-card border border-border rounded-lg shadow-lg z-10">
                    <a href="{{ route('users.attendingIcal', ['id' => $user->id]) }}" class="block px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors rounded-t-lg">
                        Attending iCal
                    </a>
                    <a href="{{ route('users.interestedIcal', ['id' => $user->id]) }}" class="block px-4 py-2 text-sm text-foreground hover:bg-accent transition-colors rounded-b-lg">
                        Interested iCal
                    </a>
                </div>
            </div>
        @endif

        @if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser')))
            <a href="{{ route('users.edit', ['user' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <i class="bi bi-pencil mr-2"></i>
                Edit Profile
            </a>

            <form action="{{ route('users.exportData', ['id' => $user->id]) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors confirm" data-confirm-message="This will generate a ZIP file with all your data and email you a download link. Continue?">
                    <i class="bi bi-download mr-2"></i>
                    Export My Data
                </button>
            </form>

            @can('grant_access')
                @if (!$user->isActive)
                    <a href="{{ route('users.activate', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors confirm">
                        <i class="bi bi-check-circle mr-2"></i>
                        Activate
                    </a>
                @endif
                @if ($user->isActive)
                    <a href="{{ route('users.reminder', ['id' => $user->id]) }}" title="Shows all future events you are attending" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors confirm">
                        <i class="bi bi-bell mr-2"></i>
                        Send Reminder
                    </a>
                @endif
            @endcan

            @if ($user->isActive)
                <a href="{{ route('users.weekly', ['id' => $user->id]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors confirm">
                    <i class="bi bi-envelope mr-2"></i>
                    Send Weekly Update
                </a>
            @endif

            <a href="{{ url('/password/reset') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
                <i class="bi bi-key mr-2"></i>
                Reset Password
            </a>
            @can('grant_access')
                <a href="{!! route('users.showResetPassword', ['id' => $user->id]) !!}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
                                <i class="bi bi-key mr-2"></i>
                    Reset User Password
                </a>
            @endcan
            @can('impersonate_user')
                <a href="{{ route('user.impersonate', ['user' => $user->id]) }}" title="Impersonate user" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors confirm">
                    <i class="bi bi-person-badge mr-2"></i>
                    Impersonate
                </a>
            @endcan
        @endif

        <a href="{{ URL::route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
            <i class="bi bi-arrow-left mr-2"></i>
            Return to list
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
        <!-- Profile Card -->
        <div class="card-tw xl:col-span-2">
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-foreground">Name:</span>
                        <span class="text-foreground">{{ $user->full_name }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-foreground">Status:</span>
                        @if ($user->status->name !== 'Active')
                            <span class="text-amber-500 font-medium">{{ $user->status->name }}</span>
                        @else
                            <span class="text-green-500">{{ $user->status->name }}</span>
                        @endif
                    </div>

                    @if ($user->profile->alias)
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-foreground">Alias:</span>
                            <span class="text-foreground">{{ $user->profile->alias }}</span>
                        </div>
                    @endif

                    @if (($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser')) || $user->profile->setting_public_profile == 1))
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-foreground">Email:</span>
                            <a href="mailto:{{ $user->email }}" class="text-primary hover:underline">{{ $user->email }}</a>
                        </div>
                    @endif

                    @if ($canViewFullProfile)
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-foreground">Default Theme:</span>
                            <span class="text-foreground">{{ $user->profile->default_theme ?: Config::get('app.default_theme') }}</span>
                        </div>
                    @endif

                    @if ($canViewFullProfile && $user->profile->bio)
                        <div class="pt-4 border-t border-border">
                            <span class="font-semibold text-foreground block mb-2">Bio:</span>
                            <p class="text-muted-foreground">{{ $user->profile->bio }}</p>
                        </div>
                    @endif

                    <!-- Social Links -->
                    @if ($canViewFullProfile && ($user->profile->facebook_username || $user->profile->twitter_username || $user->profile->instagram_username))
                        <div class="pt-4 border-t border-border">
                            <span class="font-semibold text-foreground block mb-2">Social:</span>
                            <div class="flex flex-wrap gap-3">
                                @if ($user->profile->facebook_username)
                                    <a href="https://facebook.com/{{ $user->profile->facebook_username }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline">
                                        <i class="bi bi-facebook"></i>
                                        {{ $user->profile->facebook_username }}
                                    </a>
                                @endif
                                @if ($user->profile->twitter_username)
                                    <a href="https://twitter.com/{{ $user->profile->twitter_username }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline">
                                        <i class="bi bi-twitter-x"></i>
                                        {{ '@' }}{{ $user->profile->twitter_username }}
                                    </a>
                                @endif
                                @if ($user->profile->instagram_username)
                                    <a href="https://instagram.com/{{ $user->profile->instagram_username }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline">
                                        <i class="bi bi-instagram"></i>
                                        {{ '@' }}{{ $user->profile->instagram_username }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Settings -->
                    <div class="pt-4 border-t border-border">
                        <span class="font-semibold text-foreground block mb-2">Settings:</span>
                        <div class="space-y-1 text-sm">
                            @if (($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser')) || $user->profile->setting_public_profile == 1))
                                <div class="flex items-center gap-2">
                                    <i class="bi {{ $user->profile->setting_weekly_update ? 'bi-check-circle text-green-500' : 'bi-x-circle text-muted-foreground' }}"></i>
                                    <span class="text-muted-foreground">Receive Weekly Updates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="bi {{ $user->profile->setting_daily_update ? 'bi-check-circle text-green-500' : 'bi-x-circle text-muted-foreground' }}"></i>
                                    <span class="text-muted-foreground">Receive Daily Updates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="bi {{ $user->profile->setting_instant_update ? 'bi-check-circle text-green-500' : 'bi-x-circle text-muted-foreground' }}"></i>
                                    <span class="text-muted-foreground">Receive Instant Updates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="bi {{ $user->profile->setting_forum_update ? 'bi-check-circle text-green-500' : 'bi-x-circle text-muted-foreground' }}"></i>
                                    <span class="text-muted-foreground">Receive Forum Updates</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <i class="bi {{ $user->profile->setting_public_profile ? 'bi-check-circle text-green-500' : 'bi-x-circle text-muted-foreground' }}"></i>
                                <span class="text-muted-foreground">Public Profile</span>
                            </div>
                        </div>
                    </div>

                    <!-- Private Profile Notice -->
                    @if (!$canViewFullProfile)
                        <div class="pt-4 border-t border-border">
                            <div class="bg-muted/50 border border-border rounded-lg p-4 text-center">
                                <i class="bi bi-lock text-3xl text-muted-foreground mb-2 block"></i>
                                <p class="text-muted-foreground">This user has a private profile.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Groups -->
                    @if ($canViewFullProfile)
                        @unless ($user->groups->isEmpty())
                        <div class="pt-4 border-t border-border">
                            <span class="font-semibold text-foreground block mb-2">Groups:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($user->groups as $group)
                                    <a href="/groups/{{ $group->id }}" title="{{ $group->description }}" class="badge-tw badge-primary-tw">
                                        {{ $group->label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endunless
                    @endif

                    <!-- Activity Info -->
                    @if ($canViewFullProfile)
                    <div class="pt-4 border-t border-border text-sm text-muted-foreground">
                        <div class="flex flex-wrap gap-4">
                            <span><strong>Joined:</strong> {{ $user->created_at->format('M j, Y') }}</span>
                            <span><strong>Logins:</strong> {{ $user->loginCount }}</span>
                            <span><strong>Last Active:</strong> {{ $user->lastActivity ? $user->lastActivity->created_at->format('M j, Y') : 'Never' }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Delete Button -->
                    @if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser')))
                        <div class="pt-4 border-t border-border">
                            {!! delete_form(['users.destroy', $user->id]) !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Photos Card -->
        <div class="card-tw xl:col-span-1">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-foreground mb-4">Photos</h3>

                @if ($canViewFullProfile && ($signedIn || $user->id == Config::get('app.superuser')))
                    <form action="/users/{{ $user->id }}/photos" class="dropzone mb-4 border-2 border-dashed border-border rounded-lg" id="myDropzone" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    </form>
                @endif

                @if ($user->photos->count() > 0)
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        @foreach ($user->photos as $photo)
                            <div class="relative group">
                                <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="user-photos" class="block aspect-square overflow-hidden rounded-lg">
                                    <img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $user->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                </a>
                                @if ($signedIn || $user->id == Config::get('app.superuser'))
                                    <div class="absolute bottom-1 right-1 flex gap-1">
                                        @if ($photo->is_primary)
                                            <form action="/photos/{{ $photo->id }}/unset-primary" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" title="Primary Photo [Click to unset]" class="p-1 bg-primary text-primary-foreground rounded text-xs">
                                                    <i class="bi bi-star-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="/photos/{{ $photo->id }}/set-primary" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" title="Set as primary photo" class="p-1 bg-muted text-muted-foreground rounded text-xs hover:bg-accent">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('photos.destroy', $photo->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this photo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete photo" class="p-1 bg-destructive text-destructive-foreground rounded text-xs">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted-foreground text-center py-4">No photos uploaded yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Events and Following Cards -->
    @if ($canViewFullProfile)
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 gap-6">
            <!-- Events Card -->
            <div class="card-tw xl:col-span-1">
            @if (isset($tabs) && isset($tabs['events']))
                <div class="border-b border-border px-6 py-4">
                    <h3 class="text-lg font-semibold text-foreground">Events</h3>
                </div>
                <div class="px-6 py-3 border-b border-border bg-muted/30">
                    <div class="flex gap-2">
                        <a href="/users/{{ $user->id }}?tabs[events]=created&tabs[following]={{ $tabs['following'] }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['events'] === 'created' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Created
                            @if ($tabs['events'] === 'created')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->eventCount }}</span>
                            @endif
                        </a>
                        <a href="/users/{{ $user->id }}?tabs[events]=attending&tabs[following]={{ $tabs['following'] }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['events'] === 'attending' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Attending
                            @if ($tabs['events'] === 'attending')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->attendingCount }}</span>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @if ($tabs['events'] === 'created')
                        @include('events.list-tw', ['events' => $user->events ? $user->events->take(10) : collect()])
                        <div class="mt-4 pt-4 border-t border-border">
                            <a href="{{ route('events.index') }}" class="text-primary hover:underline text-sm">
                                View all events &rarr;
                            </a>
                        </div>
                    @else
                        @include('events.list-tw', ['events' => $user->getAttending()->get()->take(20)])
                        <div class="mt-4 pt-4 border-t border-border">
                            <a href="{{ route('users.attending', ['id' => $user->id, 'filters[start_at][start]' => '']) }}" class="text-primary hover:underline text-sm">
                                View all events attending &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Following Card -->
        <div class="card-tw xl:col-span-1">
            @if (isset($tabs) && isset($tabs['following']))
                <div class="border-b border-border px-6 py-4">
                    <h3 class="text-lg font-semibold text-foreground">Following</h3>
                </div>
                <div class="px-6 py-3 border-b border-border bg-muted/30">
                    <div class="flex flex-wrap gap-2">
                        <a href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=tags"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['following'] === 'tags' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Tags
                            @if ($tabs['following'] === 'tags')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->countTagsFollowing() }}</span>
                            @endif
                        </a>
                        <a href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=entities"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['following'] === 'entities' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Entities
                            @if ($tabs['following'] === 'entities')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->countEntitiesFollowing() }}</span>
                            @endif
                        </a>
                        <a href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=series"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['following'] === 'series' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Series
                            @if ($tabs['following'] === 'series')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->countSeriesFollowing() }}</span>
                            @endif
                        </a>
                        <a href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=threads"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tabs['following'] === 'threads' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent' }}">
                            Threads
                            @if ($tabs['following'] === 'threads')
                                <span class="ml-1 px-2 py-0.5 bg-amber-500 text-black text-xs rounded-full font-semibold">{{ $user->countThreadsFollowing() }}</span>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @switch($tabs['following'])
                        @case('tags')
                            @include('tags.list-tw', ['tags' => $user->getTagsFollowing()])
                            @break
                        @case('entities')
                            @include('entities.list-tw', ['entities' => $user->getEntitiesFollowing()->take(20)])
                            <div class="mt-4 pt-4 border-t border-border">
                                <a href="{{ route('entities.following') }}" class="text-primary hover:underline text-sm">
                                    View all entities followed &rarr;
                                </a>
                            </div>
                            @break
                        @case('series')
                            @include('series.list-tw', ['series' => $user->getSeriesFollowing()->take(20)])
                            <div class="mt-4 pt-4 border-t border-border">
                                <a href="{{ route('series.following') }}" class="text-primary hover:underline text-sm">
                                    View all series followed &rarr;
                                </a>
                            </div>
                            @break
                        @case('threads')
                            @include('threads.list-tw', ['threads' => $user->getThreadsFollowing()->take(20)])
                            <div class="mt-4 pt-4 border-t border-border">
                                <a href="{{ route('threads.following') }}" class="text-primary hover:underline text-sm">
                                    View all threads followed &rarr;
                                </a>
                            </div>
                            @break
                    @endswitch
                </div>
            @endif
            </div>
        </div>
    @endif
</div>

@stop

@section('scripts.footer')
<script>
$(document).ready(function() {
    if ($('#myDropzone').length) {
        Dropzone.autoDiscover = false;
        var myDropzone = new window.Dropzone('#myDropzone', {
            dictDefaultMessage: "Drop a file here to add a user profile picture. (5MB max)"
        });

        $('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

        myDropzone.options.addPhotosForm = {
            maxFilesize: 5,
            accept: ['.jpg','.png','.gif'],
            dictDefaultMessage: "Drop a file here to add a picture",
            init: function() {
                myDropzone.on("success", function(file) {
                    location.reload();
                });
                myDropzone.on("successmultiple", function(file) {
                    location.reload();
                });
                myDropzone.on("error", function(file, message) {
                    Swal.fire({
                        title: "Error",
                        text: "Error: " + message.message,
                        icon: "error",
                        confirmButtonColor: "#ef4444",
                        confirmButtonText: "Ok",
                    }).then(result => {
                        location.reload();
                    });
                });
            },
            success: console.log('Upload successful')
        };

        myDropzone.options.addPhotosForm.init();
    }

    // Delete confirmation
    $('input.delete').on('click', function(e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: "Are you sure?",
            text: "You will not be able to recover this user!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, delete it!",
            preConfirm: function() {
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        resolve()
                    }, 2000)
                })
            }
        }).then(result => {
            if (result.value) {
                form.submit();
            }
        });
    });
});
</script>
@stop
