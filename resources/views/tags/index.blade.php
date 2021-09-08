@extends('app')

@section('title','Tags')

@section('content')

<h1 class="display-6 text-primary">Keywords	@include('tags.crumbs')</h1>

<div id="action-menu" class="mb-2">
	<a href="{{ url('/tags') }}" class="btn btn-info">Show all tags</a>
	<a href="{!! URL::route('tags.create') !!}" class="btn btn-primary">Add a tag</a>
</div>

	<div class="row">
		<div class="col-lg-2">
			<div class="card bg-dark">
				<h5 class="card-header bg-primary">Keywords
					<a href="#" ><span class='glyphicon glyphicon-question-sign pull-right' data-toggle="tooltip" data-placement="bottom"  title="Click on a keyword tag name in the left panel to find all related events or entites.  Click on the plus next to the tag to follow, minus to unfollow."></span></a>
				</h5>

				<div class="card-body">
					<div class="row">
					<div class="col-lg-1">
						<ul class="list-click">
							<li><a href="#A">A</a></li>
							<li><a href="#B">B</a></li>
							<li><a href="#C">C</a></li>
							<li><a href="#D">D</a></li>
							<li><a href="#E">E</a></li>
							<li><a href="#F">F</a></li>
							<li><a href="#G">G</a></li>
							<li><a href="#H">H</a></li>
							<li><a href="#I">I</a></li>
							<li><a href="#J">J</a></li>
							<li><a href="#K">K</a></li>
							<li><a href="#L">L</a></li>
							<li><a href="#M">M</a></li>
							<li><a href="#N">N</a></li>
							<li><a href="#O">O</a></li>
							<li><a href="#P">P</a></li>
							<li><a href="#Q">Q</a></li>
							<li><a href="#R">R</a></li>
							<li><a href="#S">S</a></li>
							<li><a href="#T">T</a></li>
							<li><a href="#U">U</a></li>
							<li><a href="#V">V</a></li>
							<li><a href="#W">W</a></li>
							<li><a href="#X">X</a></li>
							<li><a href="#Y">Y</a></li>
							<li><a href="#Z">Z</a></li>
						</ul>
					</div>
					<div class="col">
						<ul>
						@foreach ($tags as $t)
							@if (isset($tag) && (strtolower($tag) === strtolower($t->name)))
								<?php $match = $t;?>
								<li class='list selected'><a href="/tags/{{ $t->slug }}" title="Click to show all related events and entities." name="{{ $t->name[0] }}">{{ $t->name }}</a>
									@if ($signedIn)
										@if ($follow = $t->followedBy($user))
										<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to unfollow"><i class="bi bi-dash-circle-fill text-warning"></i></a>
										@else
										<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
										@endif
									@endif
								</li>
							@else
								<li class='list'><a href="/tags/{{ $t->slug }}"  name="{{ $t->name[0] }}">{{ $t->name }}</a>
									@if ($signedIn)
										@if ($follow = $t->followedBy($user))
										<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to unfollow"><i class="bi bi-dash-circle-fill text-warning"></i></a>
										@else
										<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to follow"><i class="bi bi-plus-circle-fill text-info"></i></a>
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
	</div>

	@if (!isset($tag))
	<div class="col-lg-10">
		<div class="card bg-dark">
				<h5 class="card-header bg-primary">Info</h5>
				<div class="card-body">
					Click on a <b>keyword</b> tag name in the left panel to find all related events or entites.  Click on the <b>plus</b> next to the tag to follow, <b>minus</b> to unfollow.
				</div>
			</div>
		</div>
	</div>
	@endif

	@if (!isset($match) && isset($userTags))

		<div class="col-lg-10">
			<div class="card bg-dark">
					<h5 class="card-header bg-primary">Tags</h5>
					<div class="card-body">
						@include('tags.list', ['tags' => $userTags])
					</div>
			</div>

			<div class="card bg-dark">
					<h5 class="panel-title">Entities</h5>	
					<div class="card-body">
						@include('entities.list', ['entities' => $entities])
						{!! $entities->render() !!}
					</div>
			</div>
		</div>

	@endif


	<div class="col-lg-10">
		@if (isset($match) )
		<div class="card bg-dark my-2">
				<h5 class="card-header bg-primary">Tags</h5>

				<div class="card-body">
					<ul class='event-list'>
						@include('tags.single', ['tag' => $match])
					</ul>
				</div>

		</div>

		<div class="card bg-dark my-2">
			<h5 class="card-header bg-primary">Entities</h5>
			<div class="card-body">
					@include('entities.list', ['entities' => $entities])
					{!! $entities->render() !!}
			</div>
		</div>
		@endif


		@if (isset($series) && count($series) > 0)
		<div class="card bg-dark my-2">
			<h5 class="card-header bg-primary">Series</h5>
			<div class="card-body">
				@include('series.list', ['series' => $series])
			</div>
		</div>
		@endif

		@if (isset($events) && count($events) > 0)
		<div class="card bg-dark  my-2">

				<h5 class="card-header bg-primary">Events
                	@if (isset($tag))
						<a href="{!! route('calendar.tag', ['tag' => $tag]) !!}" title="{{ $tag.' Calendar' }}"><i class='bi bi-calendar-plus text-warning pull-right'></i></a>
                    @endif
				</h5>

				<div class="card-body">
					@include('events.list', ['events' => $events])
				{!! $events->render() !!}
				</div>

			</div>
		</div>
		@endif
	</div>

</div>

@stop

@section('scripts.footer')
@parent
<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
<script type="text/javascript">
    $('button.delete').on('click', function(e){
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this event!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            },
            function(isConfirm){
                if (isConfirm)
                {
                    form.submit();
                };
            });
    })
</script>
@stop
