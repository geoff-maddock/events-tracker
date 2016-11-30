@extends('app')

@section('title','Events')

@section('content')


@stop

@section('footer')
	<div style='margin: 10px;'>
		{!! $calendar->calendar() !!}
		{!! $calendar->script() !!}
    </div>
@endsection