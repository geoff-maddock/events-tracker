@extends('app')

@section('title','Activity')

@section('content')

<h1 class="display-crumbs text-primary">Activity</h1>

<div id="filters-container" class="row">
	<div id="filters-content" class="col-xl-9">
		<a href="#" id="filters" class="btn btn-primary">
            Filters
			<span id="filters-toggle" class="@if (!$hasFilter) filter-closed @else filter-open @endif">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                  </svg>
                </span>
        </a>
		{!! Form::open(['route' => [$filterRoute ?? 'activities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col-sm">
				{!! Form::label('filter_message','Message') !!}
				{!! Form::text('filter_message', (isset($filters['message']) ? $filters['message'] : NULL),
				[
					'class' => 'form-control form-background',
					'name' => 'filters[message]'
				]) !!}
            </div>

			<div class="col-sm">
				{!! Form::label('filter_type','Table') !!}
				{!! Form::text('filter_object_table', (isset($filters['object_table']) ?
				$filters['object_table'] : NULL), 
				[
					'class' =>'form-control form-background',
					'name' => 'filters[object_table]',
					]) !!}
            </div>

			<div class="col-sm">
				{!! Form::label('filter_action','Action') !!}
				{!! Form::select('filter_action', $actionOptions, (isset($filters['action']) ?
				$filters['action'] : NULL), 
				[
					'data-width' => '100%',
					'class' =>'form-control form-background',
					'data-placeholder' => 'Select an action',
					'name' => 'filters[action]'
					]) !!}
            </div>

			<div class="col-sm">
				{!! Form::label('filter_user_id','User') !!}
				{!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
				NULL), 
				[
					'data-width' => '100%', 
					'class' =>'form-control select2 auto-submit', 
					'data-placeholder' => 'Select a user',
					'name' => 'filters[user]'
					]) !!}
            </div>
        </div>
			<div class="row my-1">
				<div class="col-sm-2">
					<div class="btn-group col-sm-1">
					<label></label>
					{!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>	'primary-filter-submit']) !!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['activities.reset'], 'method' => 'GET']) !!}
					{!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
					'primary-filter-reset']) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>
</div>

<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
		<form action="{{ url()->action('ActivityController@filter') }}" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
					<a href="{{ url()->action('ActivityController@rppReset') }}" class="btn btn-primary">
						<i class="bi bi-arrow-clockwise"></i>
					</a>
				</div>
				<div class="col-auto">		
					{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
				<div class="col-auto">		
				{!! Form::select('sort', $sortOptions, ($sort ?? 'activities.name'), ['class' => 'form-background form-select auto-submit'])
				!!}
				</div>
				<div class="col-auto">	 
				{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-background form-select auto-submit']) !!}
				</div>
			</div>
		</form>
	</div>

</div>

<!-- LIST OF ALL RECENT ACTIVITY -->
{!! $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
<ul class="list">
	@if (count($activities) > 0)

	@foreach ($activities as $activity)
	<li class="list-group-item {{ $activity->style }} ">
		{{ $activity->id }}
		<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->message }}</a> <small>{{ $activity->object_name}}</small>

		<br>

		<small>by <a href="/activity/filter?filters[user]={{ $activity->userName }}">{{ $activity->userName }}</a> {{ isset($activity->created_at)
			? ' on '.$activity->created_at->format('m/d/Y H:i') : '' }} {{ (isset($activity->ip_address) ? '
			['.$activity->ip_address.']' : '') }} </small>
	</li>
	@endforeach
	@endif
</ul>
{!! $activities->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->render() !!}
@stop

@section('footer')
@include('partials.filter-js')
@endsection