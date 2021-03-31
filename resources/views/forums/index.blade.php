@extends('app')

@section('title','Forum')

@section('content')

	<h4>Forum
		@include('forums.crumbs')
	</h4>

	<div id="action-menu" style="margin-bottom: 5px;">
		<a href="{{ url('/forums/all') }}" class="btn btn-info">Show all forums</a>
		<a href="{!! URL::route('forums.index') !!}" class="btn btn-info">Show paginated forums</a>
		<a href="{!! URL::route('forums.create') !!}" class="btn btn-primary">Add a forum</a>
	</div>

	<div id="filters-container" class="row">
		<div id="filters-content" class="col-lg-9">
			<a href="#" id="filters" class="btn btn-primary">
				Filters
				<span id="filters-toggle"
					class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
			{!! Form::open(['route' => ['forums.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
			<div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
				style="@if (!$hasFilter) display: none; @endif">
	
				<!-- BEGIN: FILTERS -->
				<div class="form-group col-sm-2">
					{!! Form::label('filter_name','Filter By Name') !!}
	
					{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
					['class' =>'form-control', 'name' => 'filters[name]']) !!}
				</div>
	
	
				<div class="col-sm-2">
					<div class="btn-group col-sm-1">
						<label></label>
						{!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
						'primary-filter-submit']) !!}
						{!! Form::close() !!}
						{!! Form::open(['route' => ['forums.reset'], 'method' => 'GET']) !!}
						{!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
						'primary-filter-reset']) !!}
						{!! Form::close() !!}
					</div>
				</div>
	
			</div>
		</div>

		<div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
			<form action="" method="GET" class="form-inline">
				<div class="form-group">
					<a href="{{ url()->action('ForumsController@rppReset') }}" class="btn btn-primary"><span
							class="glyphicon glyphicon-repeat"></span></a>
					{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
					{!! Form::select('sort', $sortOptions, ($sort ?? 'events.name'), ['class' =>'form-control auto-submit'])
					!!}
					{!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' =>'form-control
					auto-submit']) !!}
				</div>
			</form>
		</div>
	
	</div>
	
	<br style="clear: left;"/>

	<div class="row">

	@if (isset($forums) && count($forums) > 0)
	<div class="col-lg-12">
		@include('forums.list', ['forums' => $forums])
		{!! $forums->render() !!}

	</div>
	@endif

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