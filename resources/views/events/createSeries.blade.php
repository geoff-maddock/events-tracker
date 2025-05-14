@extends('app')

@section('title','Event - Create Series')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection


@section('content')

    <h1>{{ $event->name}} </h1>
    <h2>Add Series: {{ $series->name }}</h2>

    {!! Form::model($series, ['route' => ['series.store']]) !!}

        @include('series.form', ['action' => 'createSeries', 'eventLinkId' => $event->id])

    {!! Form::close() !!}

    <P><a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
