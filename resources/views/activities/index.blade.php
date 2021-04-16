@extends('app')

@section('title','Activity')

@section('content')

<h4>Activity</h4>

<!-- NAV / FILTER -->
<div id="filters-container" class="row">
	<div id="filters-content" class="col-lg-9">

		<a href="#" id="filters" class="btn btn-primary">
			Filters <span id="filters-toggle"
				class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
				{!! Form::open(['route' => [$filterRoute ?? 'activities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

			<div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
					style="@if (!$hasFilter) display: none; @endif">

			<!-- BEGIN: FILTERS -->
			<div class="form-group col-sm-2">
				{!! Form::label('filter_message','Filter By Message') !!}
				{!! Form::text('filter_message', (isset($filters['message']) ? $filters['message'] : NULL),
				[
					'class' => 'form-control',
					'name' => 'filters[message]'
				]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_type','Filter By Table') !!}
				{!! Form::text('filter_object_table', (isset($filters['object_table']) ?
				$filters['object_table'] : NULL), 
				[
					'class' =>'form-control',
					'name' => 'filters[object_table]',
					]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_action','Filter By Action') !!}
				{!! Form::select('filter_action', $actionOptions, (isset($filters['action']) ?
				$filters['action'] : NULL), 
				[
					'data-width' => '100%',
					'class' =>'form-control',
					'data-placeholder' => 'Select an action',
					'name' => 'filters[action]'
					]) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_user_id','Filter By User') !!}
				{!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
				NULL), 
				[
					'data-width' => '100%', 
					'class' =>'form-control select2 auto-submit', 
					'data-placeholder' => 'Select a user',
					'name' => 'filters[user]'
					]) !!}
			</div>

			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
					<label></label>
					{!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
					'primary-filter-submit']) !!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['activities.reset'], 'method' => 'GET']) !!}
					{!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
					'primary-filter-reset']) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>

	<div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
		<form action="{{ url()->action('ActivityController@filter') }}" method="GET" class="form-inline">
			<div class="form-group">
				<a href="{{ url()->action('ActivityController@rppReset') }}" class="btn btn-primary">
					<span class="glyphicon glyphicon-repeat"></span>
				</a>
				{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
				{!! Form::select('sort', $sortOptions, ($sort ?? 'activities.name'), ['class' =>'form-control
				auto-submit'])
				!!}
				{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' =>'form-control
				auto-submit']) !!}
			</div>
		</form>
	</div>

</div>

<br style="clear: left;" />

<!-- LIST OF ALL RECENT ACTIVITY -->
{!! $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
<ul class="list-group">
	@if (count($activities) > 0)

	@foreach ($activities as $activity)
	<li class="list-group-item {{ $activity->style }} ">
		{{ $activity->id }}
		<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->message }}</a>

		<br>

		<small>by <a href="users/{{ $activity->user_id }}">{{ $activity->userName }}</a> {{ isset($activity->created_at)
			? ' on '.$activity->created_at->format('m/d/Y H:i') : '' }} {{ (isset($activity->ip_address) ? '
			['.$activity->ip_address.']' : '') }} </small>
	</li>
	@endforeach
	@endif
</ul>
{!! $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
@stop

@section('footer')
<script>
	$(document).ready(function() {
		$('#filters').click(function() {
			$('#filter-list').toggle();
			if ($('#filters-toggle').hasClass('glyphicon-chevron-down')) {
				$('#filters-toggle').removeClass('glyphicon-chevron-down');
				$('#filters-toggle').addClass('glyphicon-chevron-up');
			} else {
				$('#filters-toggle').removeClass('glyphicon-chevron-up');
				$('#filters-toggle').addClass('glyphicon-chevron-down');
			}
		});
	});
</script>
@endsection