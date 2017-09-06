@extends('app')

@section('title','Series Edit')

@section('content')

	<i>{{ $series->start_at->format('l F jS Y \\a\\t h:i A') }} </i>
	<h2>{{ $series->name }}</h2>

    <div class="row">
        <div class="col-md-8">
        {!! Form::model($series, ['route' => ['series.update', $series->id], 'method' => 'PATCH']) !!}

            @include('series.form', ['action' => 'update'])

        {!! Form::close() !!}

        {!! delete_form(['series.destroy', $series->id]) !!}

        {!! link_to_route('series.index','Return to list') !!}
        </div>

    <div class="col-md-4">
        @if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser') ) )
            <form action="/series/{{ $series->id }}/photos" class="dropzone" id="myDropzone" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </form>
        @endif

        <br style="clear: left;"/>

        @foreach ($series->photos->chunk(4) as $set)
            <div class="row">
                @foreach ($set as $photo)
                    <div class="col-md-2">
                        <a href="/{{ $photo->path }}" data-lightbox="{{ $photo->path }}"><img src="/{{ $photo->thumbnail }}" alt="{{ $series->name}}"  style="max-width: 100%;"></a>
                        @if ($user && (Auth::user()->id == $series->user->id || $user->id == Config::get('app.superuser') ) )
                            @if ($signedIn || $user->id == Config::get('app.superuser'))
                                {!! link_form_icon('glyphicon-trash text-warning', $photo, 'DELETE', 'Delete the photo') !!}
                                @if ($photo->is_primary)
                                    {!! link_form_icon('glyphicon-star text-primary', '/photos/'.$photo->id.'/unsetPrimary', 'POST', 'Primary Photo [Click to unset]') !!}
                                @else
                                    {!! link_form_icon('glyphicon-star-empty text-info', '/photos/'.$photo->id.'/setPrimary', 'POST', 'Set as primary photo') !!}
                                @endif
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    </div>

@stop

@section('scripts.footer')
    <script src="{{ asset('/js/facebook-event.js') }}"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/dropzone.js"></script>
    <script>
        Dropzone.autoDiscover = false;
        $(document).ready(function(){

            var myDropzone = new Dropzone('#myDropzone', {
                dictDefaultMessage: "Drop a file here to add an entity profile picture. Click the star to set primary photo."
            });

            $('div.dz-default.dz-message > span').show(); // Show message span
            $('div.dz-default.dz-message').css({'opacity':1, 'background-image': 'none'});

            myDropzone.options.addPhotosForm = {
                maxFilesize: 3,
                accept: ['.jpg','.png','.gif'],
                init: function () {
                    myDropzone.on("complete", function (file) {
                        location.href = 'series/{{ $series->id }}'
                        location.reload();

                    });
                }
            };

            myDropzone.options.addPhotosForm.init();

        })
    </script>
    <script type="text/javascript">
        $('input.delete').on('click', function(e){
            e.preventDefault();
            var form = $(this).parents('form');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this series!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function(isConfirm){
                    if (isConfirm)
                    {
                        form.submit();
                    };
                });
        })
    </script>
@stop