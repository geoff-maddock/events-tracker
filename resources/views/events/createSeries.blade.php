@extends('app')

@section('title','Event - Create Series')

@section('content')

    <h1>{{ $event->name}} </h1>
    <h2>Add Series: {{ $series->name }}</h2>

    {!! Form::model($series, ['route' => ['series.store']]) !!}

        @include('series.form', ['action' => 'createSeries', 'eventLinkId' => $event->id])

    {!! Form::close() !!}

    <P><a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
