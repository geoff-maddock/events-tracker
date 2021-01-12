@extends('app')

@section('title','Activity')

@section('content')

<h4>Activity</h4>

<!-- NAV / FILTER -->
<div id="filters-container" class="row">
	<div id="filters-content" class="col-lg-9">

		<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle"
				class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
		{!! Form::open(['route' => ['activity.filter'], 'method' => 'GET']) !!}

		<div id="filter-list" @if (!$hasFilter)style="display: none" @endif>

			<!-- BEGIN: FILTERS -->
			<div class="form-group col-sm-2">
				{!! Form::label('filter_name','Filter By Name') !!}
				{!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL),
				['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_type','Filter By Table') !!}
				{!! Form::text('filter_object_table', (isset($filters['filter_object_table']) ?
				$filters['filter_object_table'] : NULL), ['class' =>'form-control']) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_action','Filter By Action') !!}
				<?php $actions = ['' => '&nbsp;'] + App\Models\Action::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
				{!! Form::select('filter_action', $actions, (isset($filters['filter_action']) ?
				$filters['filter_action'] : NULL), ['data-width' => '100%', 'class' =>'form-control auto-submit',
				'data-placeholder' => 'Select an action']) !!}
			</div>

			<div class="form-group col-sm-2">
				{!! Form::label('filter_user_id','Filter By User') !!}
				<?php $users = ['' => '&nbsp;'] + App\Models\User::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
				{!! Form::select('filter_user', $users, (isset($filters['filter_user']) ? $filters['filter_user'] :
				NULL), ['data-width' => '100%', 'class' =>'form-control select2 auto-submit', 'data-placeholder' =>
				'Select a user']) !!}
			</div>

			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
					<label></label>
					{!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
					'primary-filter-submit']) !!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['activity.reset'], 'method' => 'GET']) !!}
					{!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
					'primary-filter-reset']) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>

	<div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
		<form action="{{ url()->action('PagesController@filter') }}" method="GET" class="form-inline">
			<div class="form-group">
				<a href="{{ url()->action('PagesController@rppResetActivity') }}" class="btn btn-primary">
					<span class="glyphicon glyphicon-repeat"></span>
				</a>
				<?php $rpp_options = [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000]; ?>
				<?php $sort_by_options = ['id' => 'Id', 'object_table' => 'Object', 'action_id' => 'Action', 'created_at' => 'Created']; ?>
				<?php $sort_order_options = ['asc' => 'asc', 'desc' => 'desc']; ?>
				{!! Form::select('rpp', $rpp_options, ($rpp ?? 10), ['class' =>'form-control auto-submit']) !!}
				{!! Form::select('sortBy', $sort_by_options, ($sortBy ?? 'created_at'), ['class' =>'form-control
				auto-submit']) !!}
				{!! Form::select('sortOrder', $sort_order_options, ($sortOrder ?? 'desc'), ['class' =>'form-control
				auto-submit']) !!}
			</div>
		</form>
	</div>

</div>

<br style="clear: left;" />

<!-- LIST OF ALL RECENT ACTIVITY -->
<ul class="list-group">
	@if (count($activities) > 0)

	@foreach ($activities as $date => $record)
	<h4>{{ $date }}</h4>
	@foreach ($record as $activity)
	<li class="list-group-item {{ $activity->style }} ">
		{{ $activity->id }}
		<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->message }}</a>

		<br>

		<small>by <a href="users/{{ $activity->user_id }}">{{ $activity->userName }}</a> {{ isset($activity->created_at)
			? ' on '.$activity->created_at->format('m/d/Y H:i') : '' }} {{ (isset($activity->ip_address) ? '
			['.$activity->ip_address.']' : '') }} </small>
	</li>
	@endforeach
	@endforeach

	@endif

</ul>
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