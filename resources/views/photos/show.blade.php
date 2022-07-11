@extends('app')


@section('title', 'Photo - '. $photo->name)

@section('og-description')
@include('photos.slug-text', ['photo' => $photo])
@stop


@section('og-image')
{{ Storage::disk('external')->url($photo->getStoragePath()) }}
@endsection

@section('content')

<h1 class="display-6 text-primary">Photo @include('photos.crumbs', ['photo' => $photo])</h1>

<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('photos.index') !!}" class="btn btn-info">Show photo index</a>
	@if ($event = $photo->events->first())
	<a href="{!! URL::route('events.show', ['event' => $event->id]) !!}" class="btn btn-info">Show event</a>
	@endif
</div>

<div class="row">
<div class="col-lg-12">
	<div class="event-card">
			<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" class="img-fluid">
	</div>
</div>
</div>
@stop