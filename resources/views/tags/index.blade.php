@extends('app')

@section('title', isset($tag) ? 'Tags - '.$tag : 'Tags')

@section('content')

<h1 class="display-6 text-primary">Keywords	@include('tags.crumbs')</h1>

<div id="action-menu" class="mb-2">
	<a href="{{ url('/tags') }}" class="btn btn-info">Show all tags</a>
	<a href="{!! URL::route('tags.create') !!}" class="btn btn-primary">Add a tag</a>
</div>

	<div class="row">
		<div class="col-lg-2">
			<div class="card surface">
				<h5 class="card-header bg-primary">Keywords
					<a href="#" class="float-end"><i class="bi bi-question-octagon-fill" data-toggle="tooltip" data-placement="bottom"  title="Click on a keyword tag name in the left panel to find all related events or entites.  Click on the plus next to the tag to follow, minus to unfollow."></i></a>
					<a href="#" class="float-end px-1" title="Show / Hide" ><i class="bi bi-eye-fill toggler" id="tag-list-close-box" data-bs-target="#tag-list" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-list" role="button"></i></a>
				</h5>

				<div class="card-body collapsible collapse show" id="tag-list">
					<div class="row d-flex align-items-start">
					<div class="col-1 sticky-top pt-3 ps-3">
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
										<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to unfollow"><i class="bi bi-check-circle-fill  text-info"></i></a>
										@else
										<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to follow"><i class="bi bi-plus-circle text-warning"></i></a>
										@endif
									@endif
								</li>
							@else
								<li class='list'><a href="/tags/{{ $t->slug }}"  name="{{ $t->name[0] }}">{{ $t->name }}</a>
									@if ($signedIn)
										@if ($follow = $t->followedBy($user))
										<a href="{!! route('tags.unfollow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to unfollow"><i class="bi bi-check-circle-fill text-info"></i></a>
										@else
										<a href="{!! route('tags.follow', ['id' => $t->id]) !!}" data-target="#tag-{{ $t->id }}"  title="Click to follow"><i class="bi bi-plus-circle text-warning"></i></a>
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

	<div class="col-lg-10">
	@if (!isset($tag))
		<div class="card surface my-2">
				<h5 class="card-header bg-primary">Info</h5>
				<div class="card-body">
					Click on a <b>keyword</b> tag name in the left panel to find all related events or entites.
					@if (Auth::guest())
					<br> <a href="{{ url('/login') }}" class="link-danger">Log in</a> so you can subscribe to tags for updates.
					@else
					<br>Click on the <b>plus</b> next to the tag to follow, <b>minus</b> to unfollow.
					@endif
				</div>
		</div>
	@endif

	@if (!isset($match) && isset($userTags))

		<div class="card surface my-2">
				<h5 class="card-header bg-primary">Tags
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-followed-close-box" data-bs-target="#tag-followed" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-followed" role="button"></i></a>
				</h5>
				<div class="card-body collapsible collapse show" id="tag-followed">
					@include('tags.list', ['tags' => $userTags])
				</div>
		</div>

		<div class="card surface my-2">
				<h5 class="card-header bg-primary">Entities
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-entity-close-box" data-bs-target="#tag-entity" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-entity" role="button"></i></a>
				</h5>
				<div class="card-body collapsible collapse show" id="tag-entity">
					@include('entities.list', ['entities' => $entities])
					{!! $entities->onEachSide(2)->links() !!}
				</div>
		</div>
	@endif


	@if (isset($match) )
		<div class="card surface my-2">
				<h5 class="card-header bg-primary">Tags
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-followed-close-box" data-bs-target="#tag-followed" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-followed" role="button"></i></a>
				</h5>

				<div class="card-body collapsible collapse show" id="tag-followed">
					<ul class='event-list'>
						@include('tags.single', ['tag' => $match])
					</ul>
				</div>

		</div>

		<div class="card surface my-2">
			<h5 class="card-header bg-primary">Entities
				<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-entity-close-box" data-bs-target="#tag-entity" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-entity" role="button"></i></a>
			</h5>
			<div class="card-body collapsible collapse show" id="tag-entity">
					@include('entities.list', ['entities' => $entities])
					{!! $entities->onEachSide(2)->links() !!}
			</div>
		</div>
		@endif


		@if (isset($series) && count($series) > 0)
		<div class="card surface my-2">
			<h5 class="card-header bg-primary">Series
				<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-series-close-box" data-bs-target="#tag-series" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-series" role="button"></i></a>				
			</h5>
			<div class="card-body collapsible collapse show" id="tag-series">
				@include('series.list', ['series' => $series])
			</div>
		</div>
		@endif

		@if (isset($events) && count($events) > 0)
		<div class="card surface my-2">
			<h5 class="card-header bg-primary">Events
				<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-events-close-box" data-bs-target="#tag-events" data-bs-toggle="collapse" aria-expanded="false" aria-controls="tag-events" role="button"></i></a>				
				@if (isset($tag))
					<a href="{!! route('calendar.tag', ['tag' => $tag]) !!}" title="{{ $tag.' Calendar' }}"><i class='bi bi-calendar-plus text-warning float-end'></i></a>
				@endif
			</h5>

			<div class="card-body collapsible collapse show" id="tag-events">
				@include('events.list', ['events' => $events])
				{!! $events->onEachSide(2)->links() !!}
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
