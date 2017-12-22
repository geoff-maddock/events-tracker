@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	<div class="jumbotron">
	<h3>Event Repo</h3>
	<p>A guide and calender of events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.</p>
	<P>
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('events.future') !!}" class="btn btn-info">Show future events</a>
	<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show event series</a> 
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a> 
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
    @if (Auth::guest())
        <a href="{!! URL::route('register') !!}" class="btn btn-success">Register account</a>
    @endif
	</p>

	</div>

	<section class="4days">
        @include('pages.4daysAjax')
 	</section>
@stop

@section('scripts.footer')
<script type="text/javascript">

$(function() {

    // load the heavier part after page load
    var url ='/home';
    getEvents(url);
    window.history.pushState("", "", url);

    $('body').on('click', '.pagination a', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');  
        getEvents(url);
        window.history.pushState("", "", url);
    });


    function getEvents(url) {
        $.ajax({
            url : url  
        }).done(function (data) {
            $('.4days').html(data);  
        }).fail(function () {
            alert('No events could be loaded.');
        });
    }
});

</script>
@stop