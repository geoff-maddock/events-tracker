@extends('app')

@section('title', 'Series Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-crumbs text-primary">Series . Edit . {{ $series->name }}</h1>

<div id="action-menu" class="mb-2">
    <a href="{!! route('series.show', ['series' => $series->slug]) !!}" class="btn btn-primary">Show Series</a> <a href="{!! URL::route('series.index') !!}" class="btn btn-info">Return to list</a>
</div>

<i>{{ $series->start_at ? $series->start_at->format('l F jS Y \\a\\t h:i A') : ''}} </i>
<div class="row">
    <div class="col-md-8">
        {!! Form::model($series, ['route' => ['series.update', $series->slug], 'method' => 'PATCH', 'class' => 'form-container']) !!}

        @include('series.form', ['action' => 'update'])

        {!! Form::close() !!}

        {!! delete_form(['series.destroy', $series->id]) !!}

    </div>

    <div class="col-md-4">
        @if ($user && (Auth::user()->id == $series?->user?->id || $user->id == Config::get('app.superuser') ) )
        <form action="/series/{{ $series->id }}/photos" class="dropzone" id="myDropzone" method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        @endif

        @foreach ($series->photos->chunk(4) as $set)
        <div class="row">
            @foreach ($set as $photo)
            <div class="col-md-2">
                <a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" data-lightbox="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"><img
                        src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" alt="{{ $series->name}}" class="mw-100"></a>
                @if ($user && (Auth::user()->id == $series?->user?->id || $user->id == Config::get('app.superuser') ) )
                @if ($signedIn || $user->id == Config::get('app.superuser'))
                {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning', $photo, 'DELETE', 'Delete the photo') !!}
                @if ($photo->is_primary)
                {!! link_form_bootstrap_icon('bi bi-star-fill text-primary', '/photos/'.$photo->id.'/unset-primary', 'POST', 'Primary Photo [Click to unset]') !!}
                @else
                {!! link_form_bootstrap_icon('bi bi-star text-info', '/photos/'.$photo->id.'/set-primary', 'POST', 'Set as primary photo') !!}
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
    $(document).ready(function() {
        var myDropzone = new window.Dropzone('#myDropzone', {
            dictDefaultMessage: "Drop a file here to add an entity profile picture."
        });

        $('div.dz-default.dz-message > span').show(); // Show message span
        $('div.dz-default.dz-message').css({
            'color': '#000000',
            'opacity': 1,
            'background-image': 'none'
        });

        myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture. (Max size 5MB)",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.href = 'series/{{ $series->id }}';
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.href = 'series/{{ $series->id }}';
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
	                location.href = 'series/{{ $series->id }}';
	                location.reload();
					});
				});
				console.log('dropzone init called');
	        },
		success: console.log('Upload successful')
	};

        myDropzone.options.addPhotosForm.init();

    })
    $('button.delete').on('click', function(e) {
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