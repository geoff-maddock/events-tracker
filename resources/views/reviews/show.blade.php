@extends('app')
 
@section('content')
     @include('reviews.single', ['review' => $review])
@endsection