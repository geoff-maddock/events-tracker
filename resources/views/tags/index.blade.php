@extends('app')

@section('title','Tags')

@section('content')

	<h4>Tags
		@include('tags.crumbs')
	</h4>

	<p>
	<a href="{{ url('/tags') }}" class="btn btn-info">Show all tags</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	<div class="col-lg-2">
			<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Tags
						<a href="#" ><span class='glyphicon glyphicon-question-sign pull-right' data-toggle="tooltip" data-placement="bottom"  title="Click on a tag name in the left panel to find all related events or entites.  Click on the plus next to the tag to follow, minus to unfollow."></span></a>
					</h3>
				</div>

				<div class="panel-body">
				<ul style="margin-left: -30px;">
				@foreach ($tags as $t)
					@if (isset($tag) && (strtolower($tag) === strtolower($t->name)))
						<?php $match = $t;?>
						<li class='list selected'><a href="/tags/{{ $t->name }}" title="Click to show all related events and entities.">{{ $t->name }}</a>
							@if ($signedIn)
								@if ($follow = $t->followedBy($user))
								<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
								@else
								<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
								@endif
							@endif 
						</li>
					@else 
						<li class='list'><a href="/tags/{{ $t->name }}">{{ $t->name }}</a>
							@if ($signedIn)
								@if ($follow = $t->followedBy($user))
								<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
								@else
								<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
								@endif
							@endif 
						</li>
					@endif
				@endforeach
				</ul>
				</div>
			</div>
		</div>
	</div>

	@if (!isset($tag))
	<div class="col-lg-10">
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Info</h3>
				</div>

				<div class="panel-body" style="padding: 15px !important;">

				Click on a <b>tag</b> name in the left panel to find all related events or entites.  Click on the <b>plus</b> next to the tag to follow, <b>minus</b> to unfollow.
				</div>

			</div>
		</div>	
	</div>
	@endif


	@if (!isset($match) && isset($userTags))
		<div class="col-lg-5">
			<div class="bs-component">
				<div class="panel panel-info">


					<div class="panel-heading">
						<h3 class="panel-title">Tags</h3>
					</div>

					<div class="panel-body">
					@include('tags.list', ['tags' => $userTags])
					</div>

				</div>
			</div>
		</div>
	@endif


	@if (isset($events) && count($events) > 0)
	<div class="col-lg-5">


		@if (isset($match) )
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Tags</h3>
				</div>

				<div class="panel-body">
				<ul class='event-list'>
					@include('tags.single', ['tag' => $match])
				</ul>
				</div>

			</div>
		</div>
		@endif

		
		@if (isset($series) && count($series) > 0)
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Series</h3>
				</div>

				<div class="panel-body">
				@include('series.list', ['series' => $series])
				{!! $events->render() !!}
				</div>

			</div>
		</div>
		@endif
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Events
						<a href="{!! route('calendar.tag', ['tag' => $tag]) !!}" title="{{ $tag.' Calendar' }}"><span class='glyphicon glyphicon-calendar pull-right'></span></a>
					</h3>

				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $events])
				{!! $events->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif

	@if (isset($entities) && count($entities) > 0)
	<div class="col-lg-5">
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Entities</h3>
				</div>

				<div class="panel-body">
				@include('entities.list', ['entities' => $entities])
				{!! $entities->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif

</div>

@stop

@section('scripts.footer')
@parent
	<script type="text/javascript">
        $('button.delete').on('click', function(e){
            e.preventDefault();
            var form = $(this).parents('form');
            var type = $(this).data('type');
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this "+type+"!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function(isConfirm){
                    if (isConfirm) {
                        form.submit();
                    };
                    //
                });
        })
	</script>
<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip(); 
});
</script>
@stop