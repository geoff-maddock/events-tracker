@extends('app')
 
@section('content')
    <h2>
        {!! link_to_route('entities.show', $entity, [$entity->id]) !!} -
        {{ $location->name }}
    </h2>
 
    {{ $location->city }}
@endsection