@extends('app')
 
@section('content')
    <h2>
        {!! link_to_route('entities.show', $entity, [$entity->slug]) !!} -
        {{ $link->title }}
    </h2>
 
    {{ $contact->url }}
@endsection