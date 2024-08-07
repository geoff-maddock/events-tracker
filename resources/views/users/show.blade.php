@extends('app')

@section('title', 'User Profile View')

@section('content')

    <h1 class="display-crumbs text-primary">User  @include('users.crumbs')</h1>
    <div class="row">
        <div class="m-2">
            <a href="{!! route('users.attending', ['id' => $user->id]) !!}" class="btn btn-primary">Attending</a>
            @if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser') ) )
                <a href="{!! route('users.edit', ['user' => $user->id]) !!}" class="btn btn-primary">Edit Profile</a>
                @can('grant_access')
                @if (!$user->isActive)
                <a href="{!! route('users.activate', ['id' => $user->id]) !!}" class="btn btn-primary confirm">
                    Activate
                </a>
                @endif
                @if ($user->isActive)
                    <a href="{!! route('users.reminder', ['id' => $user->id]) !!}"  class="btn btn-primary confirm">
                        Send Reminder
                    </a>
                @endif
                @endcan
                @can('impersonate_user')
                <a href="{!! route('user.impersonate', ['user' => $user->id]) !!}" title="Impersonate user"  class="btn btn-primary confirm">
                    Impersonate
                </a>
                @endif
                @if ($user->isActive)
				<a href="{!! route('users.weekly', ['id' => $user->id]) !!}"  class="btn btn-primary confirm">
					Send Weekly Update
				</a>
			    @endif
                <a href="{{ url('/password/reset') }}" class="btn btn-primary">Reset Password</a>
                {!! delete_form(['users.destroy', $user->id]) !!}
            @endif

            <a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>
        </div>
    </div>
    <div class="row small-gutter mx-2">

            <div class="col-lg-6 profile-card">
                <b>Name </b> {{ $user->full_name }}<br>
                <b>Status </b> 
                @if ($user->status->name !== 'Active')
                <span class="text-warning">{{ $user->status->name }}</span>
                @else
                {{ $user->status->name }}
                @endif
                <br>
                @if ($user->profile->alias )
                    <b>Alias </b> {{ $user->profile->alias }}<br>
                @endif
                @if ($signedIn && (Auth::user()->hasGroup('super_admin') || Auth::user()->email == $user->email))
                    <b>Email </b> {{ $user->email }}<br>
                <b>Contact </b> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a><br>
                @endif
                <b>Default
                    Theme </b> {{ $user->profile->default_theme ? $user->profile->default_theme : Config::get('app.default_theme') }}
                <br>

                @if ($user->profile->bio)
                    <div class="bio">

                        <b>Bio</b><br>
                        <p>
                            {{ $user->profile->bio ? $user->profile->bio : 'No bio available'}}
                        </p>
                    </div>
                @endif

                @if ($user->profile->facebook_username)
                    <b>Facebook:</b> <a href="https://facebook.com/{{ $user->profile->facebook_username }}" target="_">{{$user->profile->facebook_username}}</a>
                @endif

                @if ($user->profile->twitter_username)
                        <b>Twitter:</b> <a href="https://twitter.com/{{ $user->profile->twitter_username }}" target="_">{{ '@' }}{{ $user->profile->twitter_username }}</a>
                @endif

                @if ($user->profile->instagram_username)
                        <b>Instagram:</b> <a href="https://instagram.com/{{ $user->profile->instagram_username }}" target="_">{{ '@' }}{{ $user->profile->instagram_username }}</a>
                @endif


                <hr>
                <b>Settings</b><br>
                Receive Weekly Updates: {{ $user->profile->setting_weekly_update ? 'Yes' : 'No'}}<br>
                Receive Daily Updates: {{ $user->profile->setting_daily_update ? 'Yes' : 'No'}}<br>
                Receive Instant Updates: {{ $user->profile->setting_instant_update ? 'Yes' : 'No'}}<br>
                Receive Forum Updates: {{ $user->profile->setting_forum_update ? 'Yes' : 'No'}}<br>

                <div class="groups">
                    @unless ($user->groups->isEmpty())
                        <P><b>Groups:</b>
                            @foreach ($user->groups as $group)
                            <span class="badge rounded-pill bg-dark">
                                <a href="/groups/{{ $group->id }}" title="{{ $group->description }}">{{ $group->label }}</a>
                            </span>
                        @endforeach
                    @endunless
                </div>
                <p>Actions:
                    <a href="/users/{{ $user->id }}/ical" title="Export attending events to iCal">
                        <i class='bi bi-calendar-plus text-warning icon'></i>
                    </a>
                </p>

                <b>Joined:</b> {{ $user->created_at->format('m.d.y') }} | Logged in {{ $user->loginCount }} times |  <b>Last Active:</b> {{ $user->lastActivity ?  $user->lastActivity->created_at->format('m.d.y') : 'Never'}}<br>
                <br>
            </div>

            <div class="col-lg-6">
                @if ($signedIn || $user->id == Config::get('app.superuser'))
                    <form action="/users/{{ $user->id }}/photos" class="dropzone" id="myDropzone" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    </form>
                @endif

                @foreach ($user->photos->chunk(4) as $set)
                    <div class="row">
                        @foreach ($set as $photo)
                            <div class="col-md-2">
                                <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"><img
                                            src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $user->name}}"
                                            class="mw-100"></a>
                                @if ($signedIn || $user->id == Config::get('app.superuser'))
                                        {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $photo, 'DELETE', 'Delete the photo') !!}
                                    @if ($photo->is_primary)
                                        {!! link_form_bootstrap_icon('bi bi-star-fill text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
                                    @else
                                        {!! link_form_bootstrap_icon('bi bi-star text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

        </div>

    <div class="row m-2"></div>
    <div class="row small-gutter mx-2">

            <div class="col-lg-6">
                <div class="card surface">
                    <div>
                        @if (isset($tabs) && isset($tabs['events']))

                            @if ($tabs['events'] === 'created')
                            <div class="card-header bg-primary">
                                <h5 class="my-0 fw-normal">Events  <span  class="badge rounded-pill bg-warning text-dark">{{ $user->eventCount }}</span></h3>
                            </div>
                                <ul class="nav nav-pills m-2">
                                    <li role="presentation" class="nav-item">
                                        <a class="nav-link @if ($tabs['events'] === 'created')active @endif" aria-current="page" href="/users/{{ $user->id }}?tabs[events]=created&tabs[following]={{ $tabs['following'] }}">Created</a>
                                    </li>
                                    <li role="presentation" class="nav-item">
                                        <a class="nav-link @if ($tabs['events'] === 'attending')active @endif" href="/users/{{ $user->id }}?tabs[events]=attending&tabs[following]={{ $tabs['following'] }}">Attending</a>
                                    </li>
                                </ul>
                                <div class="card-body">
                                    @include('events.list', ['events' => $user->events ? $user->events->take(20) : null])

                                    <div class="d-block" id="created-events">
                                        <div class="col-sm-12">
                                            <ul class="list-style-none"  class="mt-0">
                                                <li>{!! link_to_route('events.index', 'All Events', [], ['id' => 'add-event', 'class' => 'page-link text-nowrap']) !!}</li>
                                            </ul>
                                        </div>
                                    </div>

                                </div>
                            @else
                            <div class="card-header bg-primary">
                                <h5 class="my-0 fw-normal">Events  <span class="badge rounded-pill bg-warning text-dark">{{ $user->attendingCount }}</span></h3>
                            </div>
                                <ul class="nav nav-pills m-2">
                                    <li role="presentation" class="nav-item">
                                        <a class="nav-link @if ($tabs['events'] === 'created') active @endif" href="/users/{{ $user->id }}?tabs[events]=created&tabs[following]={{ $tabs['following'] }}">Created</a>
                                    </li>
                                    <li role="presentation" class="nav-item">
                                        <a class="nav-link @if ($tabs['events'] === 'attending') active @endif" href="/users/{{ $user->id }}?tabs[events]=attending&tabs[following]={{ $tabs['following'] }}">Attending</a>
                                    </li>
                                </ul>
                                <div class="card-body">
                                    @include('events.list', ['events' => $user->getAttending()->get()->take(20)])

                                    <div class="d-block" id="attending-events">
                                        <div class="col-sm-12">
                                            <ul class="list-style-none"  class="mt-0">
                                                <li>{!! link_to_route('users.attending', 'All Events Attending', ['id' => $user->id, 'filters[start_at][start]'=> ''], ['id' => 'add-event', 'class' => 'page-link text-nowrap']) !!}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card surface">
                    <div>
                        @if (isset($tabs) && isset($tabs['following']))
                            @switch($tabs['following'])
                                @case('tags')
                                    <div class="card-header bg-primary">
                                        <h5 class="my-0 fw-normal">Following <span class="badge rounded-pill bg-warning text-dark">{{ $user->countTagsFollowing() }}</span></h3>
                                    </div>
                                    <ul  class="nav nav-pills m-2">
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'tags') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=tags">Tags</a>
                                        </li>
                                        <li role="presentation" class="">
                                            <a class="nav-link @if ($tabs['following'] === 'entities') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=entities">Entities</a>
                                        </li>
                                        <li role="presentation" class="">
                                            <a class="nav-link @if ($tabs['following'] === 'series') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=series">Series</a>
                                        </li>
                                        <li role="presentation" class="">
                                            <a class="nav-link @if ($tabs['following'] === 'threads') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=threads">Threads</a>
                                        </li>
                                    </ul>
                                    <div class="card-body">
                                    @include('tags.list', ['tags' => $user->getTagsFollowing()])
                                    </div>
                                    @break

                                @case('entities')
                                    <div class="card-header bg-primary">
                                        <h5 class="my-0 fw-normal">Following <span class="badge rounded-pill bg-warning text-dark">{{ $user->countEntitiesFollowing() }}</span></h3>
                                    </div>
                                    <ul  class="nav nav-pills m-2">
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'tags') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=tags">Tags</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'entities') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=entities">Entities</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'series') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=series">Series</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'threads') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=threads">Threads</a>
                                        </li>
                                    </ul>
                                    <div class="card-body">
                                        @include('entities.list', ['entities' => $user->getEntitiesFollowing()->take(20)])
                                        <div class="d-block" id="following-entities">
                                            <div class="col-sm-12">
                                                <ul class="list-style-none"  class="mt-0">
                                                    <li>{!! link_to_route('entities.following', 'All Entities Followed', [], ['id' => 'following-entites', 'class' => 'page-link text-nowrap']) !!}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('series')
                                    <div class="card-header bg-primary">
                                        <h5 class="my-0 fw-normal">Following <span class="badge rounded-pill bg-warning text-dark">{{ $user->countSeriesFollowing() }}</span></h3>
                                    </div>
                                    <ul class="nav nav-pills m-2">
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'tags') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=tags">Tags</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'entities') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=entities">Entities</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'series') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=series">Series</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'threads') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=threads">Threads</a>
                                        </li>
                                    </ul>
                                    <div class="card-body">
                                        @include('series.list', ['series' => $user->getSeriesFollowing()->take(20)])

                                        <div class="d-block" id="following-series">
                                            <div class="col-sm-12">
                                                <ul class="list-style-none"  class="mt-0">
                                                    <li>{!! link_to_route('series.following', 'All Series Followed', [], ['id' => 'following-series', 'class' => 'page-link text-nowrap']) !!}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('threads')
                                    <div class="card-header bg-primary">
                                        <h5 class="my-0 fw-normal">Following <span class="badge rounded-pill bg-warning text-dark">{{ $user->countThreadsFollowing() }}</span></h3>
                                    </div>
                                    <ul class="nav nav-pills m-2">
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'tags') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=tags">Tags</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'entities') active @endif"href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=entities">Entities</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'series') active @endif"href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=series">Series</a>
                                        </li>
                                        <li role="presentation" class="nav-item">
                                            <a class="nav-link @if ($tabs['following'] === 'threads') active @endif" href="/users/{{ $user->id }}?tabs[events]={{ $tabs['events'] }}&tabs[following]=threads">Threads</a>
                                        </li>
                                    </ul>
                                    <div class="card-body">
                                        @include('threads.list', ['threads' => $user->getThreadsFollowing()->take(20)])
                                        <div class="d-block" id="following-threads">
                                            <div class="col-sm-12">
                                                <ul class="list-style-none"  class="mt-0">
                                                    <li>{!! link_to_route('threads.following', 'All Followed Threads', [], ['id' => 'following-threads', 'class' => 'page-link text-nowrap']) !!}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @break
                            @endswitch
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@stop

@section('scripts.footer')
    <script>
        Dropzone.autoDiscover = true;
        $(document).ready(function () {

            if ($('#myDropzone').length) {

                var myDropzone = new window.Dropzone('#myDropzone', {
                    dictDefaultMessage: "Drop a file here to add a user profile picture. (5MB max)"
                });

                $('div.dz-default.dz-message > span').show(); // Show message span
                $('div.dz-default.dz-message').css({'color': '#000000', 'opacity': 1, 'background-image': 'none'});

                myDropzone.options.addPhotosForm = {
                maxFilesize: 5,
                accept: ['.jpg','.png','.gif'],
                dictDefaultMessage: "Drop a file here to add a picture",
                init: function () {
                        myDropzone.on("success", function (file) {
                            location.href = 'users/{{ $user->id }}';
                            location.reload();
                        });
                        myDropzone.on("successmultiple", function (file) {
                            location.href = 'users/{{ $user->id }}';
                            location.reload();
                        });
                        myDropzone.on("error", function (file, message) {
                            Swal.fire({
                                title: "Are you sure?",
                                text: "Error: " + message.message,
                                type: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#DD6B55",
                                confirmButtonText: "Ok",
                        }).then(result => {
                            location.href = 'users/{{ $user->id }}';
                            location.reload();
                            });
                        });
                        console.log('dropzone init called');
                    },
                success: console.log('Upload successful')
            };

                myDropzone.options.addPhotosForm.init();

            }
        })
        $('input.delete').on('click', function(e){
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this user!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                preConfirm: function() {
                        return new Promise(function(resolve) {
                            setTimeout(function() {
                                resolve()
                            }, 2000)
                        })
                    }
            }).then(result => {
            if (result.value) {
                // handle Confirm button click
                // result.value will contain `true` or the input value
                form.submit();
            } else {
                // handle dismissals
                // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                console.log('cancelled confirm')
            }
        });
    })
    </script>
@stop

