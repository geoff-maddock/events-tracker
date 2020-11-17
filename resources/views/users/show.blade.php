@extends('app')

@section('title','User Profile View')

@section('content')

    <div class="row">
        <div class="col-md-12">
            <h2>{{ $user->name }}</h2>
            <p>
                @if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser') ) )
                    <a href="{!! route('users.edit', ['user' => $user->id]) !!}" class="btn btn-primary">Edit Profile</a>
                @endif
                <a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>

            </p>
            <div class="col-lg-6 profile-card">
                <b>Name </b> {{ $user->full_name }}<br>
                <b>Status </b> {{ $user->status ? $user->status->name : '' }}<br>
                @if ($user->profile->alias )
                    <b>Alias </b> {{ $user->profile->alias }}<br>
                @endif
                <b>Contact </b> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a><br>
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


                <div class="groups">
                    @unless ($user->groups->isEmpty())
                        <P><b>Groups:</b>
                            @foreach ($user->groups as $group)
                                <span class="label label-tag"><a href="/groups/{{ $group->id }}"
                                                                 title="{{ $group->description }}">{{ $group->label }}</a></span>
                        @endforeach
                    @endunless
                </div>
                <p>Actions:
                    <a href="/users/{{ $user->id }}/ical" title="Export attending events to iCal">
                        <span class='glyphicon glyphicon-calendar'></span>
                    </a>
                </p>

                <h5>Added <b>{{ $user->created_at->format('l F jS Y') }}</b></h5>
                <br>
            </div>

            <div class="col-md-5">
                @if ($signedIn || $user->id == Config::get('app.superuser'))
                    <form action="/users/{{ $user->id }}/photos" class="dropzone" id="myDropzone" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    </form>
                @endif

                <br style="clear: left;"/>

                @foreach ($user->photos->chunk(4) as $set)
                    <div class="row">
                        @foreach ($set as $photo)
                            <div class="col-md-2">
                                <a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->getStoragePath() }}"><img
                                            src="{{ $photo->getStorageThumbnail() }}" alt="{{ $user->name}}"
                                            style="max-width: 100%;"></a>
                                @if ($signedIn || $user->id == Config::get('app.superuser'))
                                    {!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
                                    @if ($photo->is_primary)
                                        {!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
                                    @else
                                        {!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

        </div>

    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="col-lg-6">
                <div class="bs-component">
                    <div class="panel panel-info">
                        @if (isset($tabs))
                            @if ($tabs[0] === 'created')
                            <div class="panel-heading">
                                <h3 class="panel-title">Events  <span  class="label label-primary">{{ $user->eventCount }}</span></h3>
                            </div>
                                <ul class="nav nav-tabs">
                                    <li role="presentation" class="@if ($tabs[0] === 'created') active @endif"><a href="/users/{{ $user->id }}?tabs[0]=created&tabs[1]={{ $tabs[1] }}">Created</a></li>
                                    <li role="presentation" class="@if ($tabs[0] === 'attending') active @endif"><a href="/users/{{ $user->id }}?tabs[0]=attending&tabs[1]={{ $tabs[1] }}">Attending</a></li>
                                </ul>
                                <div class="panel-body">
                                @include('events.list', ['events' => $user->events->take(20)])
                                </div>
                            @else
                            <div class="panel-heading">
                                <h3 class="panel-title">Events  <span  class="label label-primary">{{ $user->attendingCount }}</span></h3>
                            </div>
                                <ul class="nav nav-tabs">
                                    <li role="presentation" class="@if ($tabs[0] === 'created') active @endif"><a href="/users/{{ $user->id }}?tabs[0]=created&tabs[1]={{ $tabs[1] }}">Created</a></li>
                                    <li role="presentation" class="@if ($tabs[0] === 'attending') active @endif"><a href="/users/{{ $user->id }}?tabs[0]=attending&tabs[1]={{ $tabs[1] }}">Attending</a></li>
                                </ul>
                                <div class="panel-body">
                                @include('events.list', ['events' => $user->getAttending()->get()->take(20)])
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bs-component">

                    <div class="bs-component">
                        <div class="panel panel-info">
                                @switch($tabs[1])
                                    @case('tags')
                                        <div class="panel-heading">
                                            <h3 class="panel-title">Following <span class="label label-primary">{{ $user->tagsFollowingCount }}</span></h3>
                                        </div>
                                        <ul class="nav nav-tabs">
                                            <li role="presentation" class="@if ($tabs[1] === 'tags') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=tags">Tags</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'entities') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=entities">Entities</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'series') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=series">Series</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'threads') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=threads">Threads</a></li>
                                        </ul>
                                        <div class="panel-body">
                                        @include('tags.list', ['tags' => $user->getTagsFollowing()->take(20)])
                                        </div>
                                        @break

                                    @case('entities')
                                        <div class="panel-heading">
                                            <h3 class="panel-title">Following <span class="label label-primary">{{ $user->entitiesFollowingCount }}</span></h3>
                                        </div>
                                        <ul class="nav nav-tabs">
                                            <li role="presentation" class="@if ($tabs[1] === 'tags') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=tags">Tags</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'entities') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=entities">Entities</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'series') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=series">Series</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'threads') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=threads">Threads</a></li>
                                        </ul>
                                        <div class="panel-body">
                                        @include('entities.list', ['entities' => $user->getEntitiesFollowing()->take(20)])
                                        </div>
                                        @break

                                    @case('series')
                                        <div class="panel-heading">
                                            <h3 class="panel-title">Following <span class="label label-primary">{{ $user->seriesFollowingCount }}</span></h3>
                                        </div>
                                        <ul class="nav nav-tabs">
                                            <li role="presentation" class="@if ($tabs[1] === 'tags') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=tags">Tags</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'entities') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=entities">Entities</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'series') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=series">Series</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'threads') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=threads">Threads</a></li>
                                        </ul>
                                        <div class="panel-body">
                                        @include('series.list', ['series' => $user->getSeriesFollowing()->take(20)])
                                        </div>
                                        @break

                                    @case('threads')
                                        <div class="panel-heading">
                                            <h3 class="panel-title">Following <span class="label label-primary">{{ $user->threadsFollowingCount }}</span></h3>
                                        </div>
                                        <ul class="nav nav-tabs">
                                            <li role="presentation" class="@if ($tabs[1] === 'tags') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=tags">Tags</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'entities') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=entities">Entities</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'series') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=series">Series</a></li>
                                            <li role="presentation" class="@if ($tabs[1] === 'threads') active @endif"><a href="/users/{{ $user->id }}?tabs[0]={{ $tabs[0] }}&tabs[1]=threads">Threads</a></li>
                                        </ul>
                                        <div class="panel-body">
                                        @include('threads.list', ['threads' => $user->getThreadsFollowing()->take(20)])
                                        </div>
                                        @break

                                @endswitch
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

@stop

@section('scripts.footer')
    <script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
    <script>
        Dropzone.autoDiscover = false;
        $(document).ready(function () {

            var myDropzone = new Dropzone('#myDropzone', {
                dictDefaultMessage: "Drop a file here to add a user profile picture."
            });

            $('div.dz-default.dz-message > span').show(); // Show message span
            $('div.dz-default.dz-message').css({'color': '#000000', 'opacity': 1, 'background-image': 'none'});

            myDropzone.options.addPhotosForm = {
                maxFilesize: 3,
                accept: ['.jpg', '.png', '.gif'],
                dictDefaultMessage: "Drop a file here to add a picture.",
                init: function () {
                    myDropzone.on("complete", function (file) {
                        location.href = 'users/{{ $user->id }}';
                        location.reload();
                    });
                }
            };

            myDropzone.options.addPhotosForm.init();

        })
    </script>
@stop

