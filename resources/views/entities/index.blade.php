@extends('app')

@section('title')
Entities @include('entities.title-crumbs')
@endsection


@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<h1 class="display-crumbs text-primary">Entities  @include('entities.crumbs')</h1>

<div id="action-menu" class="mb-2">
    <a href="{!! URL::route('entities.create') !!}" class="btn btn-primary my-1">Add an entity</a>
    <a href="{!! URL::route('entities.role', ['role' => 'artist']) !!}" class="btn btn-info my-1">Show artists</a>
    <a href="{!! URL::route('entities.role', ['role' => 'band']) !!}" class="btn btn-info my-1">Show bands</a>
    <a href="{!! URL::route('entities.role', ['role' => 'dj']) !!}" class="btn btn-info my-1">Show DJs</a>
    <a href="{!! URL::route('entities.role', ['role' => 'producer']) !!}" class="btn btn-info my-1">Show producers</a>
    <a href="{!! URL::route('entities.role', ['role' => 'promoter']) !!}" class="btn btn-info my-1">Show promoters</a>
    <a href="{!! URL::route('entities.role', ['role' => 'shop']) !!}" class="btn btn-info my-1">Show shops</a>
    <a href="{!! URL::route('entities.role', ['role' => 'venue']) !!}" class="btn btn-info my-1">Show venues</a>
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
        {!! Form::open(['route' => ['entities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col-sm">
                {!! Form::label('filter_name','Name') !!}

                {!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
                [
                    'class' =>'form-control form-background',
                    'name' => 'filters[name]'
                ]) !!}
            </div>

			<div class="col-sm">
                {!! Form::label('filter_role','Role', array('width' => '100%')) !!}<br>
                {!! Form::select('filter_role', $roleOptions, (isset($filters['role']) ? $filters['role']
                : NULL),
                [
                    'data-theme' => 'bootstrap-5',
                    'data-style' => '100%',
                    'data-width' => '100%',
                    'class' => 'form-control select2 form-background',
                    'data-placeholder' => 'Select a role',
                    'name' => 'filters[role]'
                ]) !!}
            </div>

			<div class="col">
                {!! Form::label('filter_tag','Filter By Tag', array('width' => '100%')) !!}
                {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] :
                NULL),
                [
                    'data-theme' => 'bootstrap-5',
                    'class' =>'form-control select2 form-background',
                    'data-width' => '100%',
                    'data-placeholder' => 'Select a tag',
                    'name' => 'filters[tag]'
                ])
                !!}
            </div>

			<div class="col-sm">
				{!! Form::label('filter_entity_type','Type') !!}
				{!! Form::select('filter_entity_type', $entityTypeOptions, (isset($filters['entity_type'])
				? $filters['entity_type'] : NULL),
				[
                    'data-theme' => 'bootstrap-5',
                    'data-width' => '100%',
                    'class' => 'form-control select2 form-background',
                    'data-placeholder' => 'Select a type',
                    'name' => 'filters[entity_type]'
				]) !!}
			</div>

            <div class="col-sm">
				{!! Form::label('filter_entity_status','Status') !!}
				{!! Form::select('filter_entity_status', $entityStatusOptions, (isset($filters['entity_status'])
				? $filters['entity_status'] : NULL),
				[
                    'data-theme' => 'bootstrap-5',
                    'data-width' => '100%',
                    'class' => 'form-control select2 form-background',
                    'data-placeholder' => 'Select a status',
                    'name' => 'filters[entity_status]'
				]) !!}
			</div>
        </div>
        <div class="row">
			<div class="col-sm-2">
				<div class="btn-group col-sm-1">
                    <label></label>
                    {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb me-2 my-2', 'id' =>
                    'primary-filter-submit']) !!}
                    {!! Form::close() !!}
                    {!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}
                    {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb me-2 my-2', 'id' =>
                    'primary-filter-reset']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>

    <div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
        <form action="" method="GET" class="form-inline">
			<div class="form-group row gx-1 justify-content-end">
				<div class="col-auto">
					<a href="{{ url()->action('EntitiesController@rppReset') }}" class="btn btn-primary" alt="Reset" aria-label="Reset">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
							<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
							<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
						</svg>
					</a>
				</div>
				<div class="col-auto">
                {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
                <div class="col-auto">
                {!! Form::select('sort', $sortOptions, ($sort ?? 'events.name'), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
                <div class="col-auto">
                {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' => 'form-background form-select  auto-submit']) !!}
                </div>
            </div>
        </form>
    </div>

</div>
@if (isset($latestEntities) )

<div class="card surface my-2">
        <h5 class="card-header bg-primary">Recently Popular Entities
            <a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#entity-popular" data-bs-toggle="collapse" aria-label="Popular entity toggle" aria-expanded="false" aria-controls="entity-popular" role="button"></i></a>
        </h5>
        <div class="card-body collapsible collapse show" id="entity-popular">
            @include('entities.list', ['entities' => $latestEntities])
        </div>
</div>
@endif

<div id="list-container" class="row">
    <div>

        {!! $entities->onEachSide(2)->links() !!}

        @include('entities.list', ['entities' => $entities])

        {!! $entities->onEachSide(2)->links() !!}
    </div>
</div>
@stop

@section('footer')
@include('partials.filter-js')
@endsection