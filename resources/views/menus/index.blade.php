@extends('app')

@section('title','Menus')

@section('content')

<h1 class="display-6 text-primary">Menus @include('menus.crumbs')</h1>

<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('menus.create') !!}" class="btn btn-primary">Add a menu</a>
</div>

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
		{!! Form::open(['route' => [$filterRoute ?? 'menus.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
		
		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
			<div class="row">
				<div class="col">
						{!! Form::label('filter_name','Filter By Name') !!}
						{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
						[
							'class' => 'form-control form-background',
							'name' => 'filters[name]'
						]) !!}
				</div>
			</div>
		<div class="row">
			<div class="col-sm-2 py-2">
				<div class="btn-group col-sm-1">
					<label></label>
					{!! Form::submit('Apply',
					[
					'class' => 'btn btn-primary btn-sm btn-tb mx-2',
					'id' => 'primary-filter-submit'
					])
					!!}
					{!! Form::close() !!}
					{!! Form::open(['route' => ['menus.reset'], 'method' => 'GET']) !!}
					{!! Form::hidden('redirect', $redirect ?? 'menus.index') !!}
					{!! Form::hidden('key', $key ?? 'internal_menu_index') !!}
					{!! Form::submit('Reset',
					[
					'class' => 'btn btn-primary btn-sm btn-tb',
					'id' => 'primary-filter-reset'
					]) !!}
					{!! Form::close() !!}
				</div>
			</div>
		</div>
	</div>
</div>
<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right">
				<form action="{{ url()->current() }}" method="GET" class="form-inline">
					<div class="form-group row gx-1 justify-content-end">
						<div class="col-auto">
							<a href="{{ url()->action('MenusController@rppReset') }}" class="btn btn-primary">
								<i class="bi bi-arrow-clockwise"></i>
							</a>
						</div>
						<div class="col-auto">							
							{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-select form-background auto-submit']) !!}
						</div>
						<div class="col-auto">
							{!! Form::select('sort', $sortOptions, ($sort ?? 'menus.created_by'), ['class' => 'form-select form-background auto-submit'])	!!}
						</div>
						<div class="col-auto">
							{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-select form-background auto-submit']) !!}
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class='col-md-6'>
		{!! $menus->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->render() !!}
			@include('menus.list', ['menus' => $menus])
		{!! $menus->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->render() !!}
		</div>
@stop

@section('footer')
@include('partials.filter-js')
@endsection