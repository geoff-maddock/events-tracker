@extends('app')
 
@section('content')
    <h2>
     @include('reviews.single', ['review' => $review])
    </h2>
 @endsection