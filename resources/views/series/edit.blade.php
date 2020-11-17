@extends('app')

@section('title','Series Edit')

@section('content')

	<h4>Series . Edit . {{ $series->name }}</h4>

	<i>{{ $series->start_at->format('l F jS Y \\a\\t h:i A') }} </i>
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
                        <a href="{{ $photo->getStoragePath() }}" data-lightbox="{{ $photo->getStoragePath() }}"><img src="{{ $photo->getStorageThumbnail() }}" alt="{{ $series->name}}"  style="max-width: 100%;"></a>
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
    <script type="text/javascript">
        window.Dropzone.autoDiscover = false;
        $(document).ready(function(){
            var myDropzone = new Dropzone('#myDropzone', {
                dictDefaultMessage: "Drop a file here to add an entity profile picture."
            });

            $('div.dz-default.dz-message > span').show(); // Show message span
            $('div.dz-default.dz-message').css({'color': '#000000', 'opacity':1, 'background-image': 'none'});

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
        $('input.delete').on('click', function(e){
            e.preventDefault();
            const form = $(this).parents('form');
            Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this series!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
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
                    // handle Confirm button click
                    // result.value will contain `true` or the input value
                    form.submit();
                } else {
                    // handle dismissals
                    // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                    console.log('Cancelled confirm')
                }
            });
        })
    </script>
@stop
