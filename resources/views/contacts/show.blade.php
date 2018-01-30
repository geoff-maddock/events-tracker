@extends('app')
 
@section('content')
    <h2>
        {!! link_to_route('entities.show', $entity, [$entity->slug]) !!} -
        {{ $contact->name }}
    </h2>
 
    {{ $contact->city }}
@endsection