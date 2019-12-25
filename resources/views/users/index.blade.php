@extends('app')

@section('title','Events')

@section('content')

		<h1>Users</h1>
		<i>public user directory</i><br>

		<!-- NAV / FILTER -->

		<div id="filters-container" class="col-md-12">

			<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle" class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
			{!! Form::open(['route' => ['users.filter'], 'method' => 'GET']) !!}

			<div id="filter-list" @if (!$hasFilter)style="display: none"@endif >
				<!-- BEGIN: FILTERS -->
				<div class="form-group col-sm-3">
					{!! Form::label('filter_email','Email') !!}
					{!! Form::text('filter_email', ($filters['filter_email'] ?? NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-3">
					{!! Form::label('filter_name','Name') !!}
					{!! Form::text('filter_name', ($filters['filter_name'] ?? NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_status','Status') !!}
                    <?php $venues = [''=>''] + App\UserStatus::orderBy('name','ASC')->pluck('name','name')->all();?>
					{!! Form::select('filter_status', $venues, $filters['filter_status'] ?? NULL, ['data-width' => '100%','class' =>'form-control select2', 'data-placeholder' => 'Select a status']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_rpp','RPP') !!}
                    <?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
					{!! Form::select('filter_rpp', $rpp_options, $filters['filter_rpp'] ?? NULL, ['class' =>'form-control auto-submit']) !!}
				</div>

				<div class="col-sm-2">
					<div class="btn-group col-sm-1">
						{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}
						{!! Form::close() !!}
						{!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
						{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}
						{!! Form::close() !!}
					</div>
				</div>
			</div>

		</div>
		<!-- END: FILTERS -->

		<br style="clear: left;"/>
		<div class="row">
			<div class='col-md-12 col-lg-6'>

				@if (!$users->isEmpty())
					{!! $users->appends(['sort_by' => $sortBy,
                                            'rpp' => $rpp
                    ])->render() !!}
				@endif

				@include('users.list', ['users' => $users])

				@if (!$users->isEmpty())
					{!! $users->appends(['sort_by' => $sortBy,
											'rpp' => $rpp
					])->render() !!}
				@endif
			</div>
		</div>
@stop

@section('footer')
	<script>
        $(document).ready(function() {
            $('#filters').click(function () {
                $('#filter-list').toggle();
                if ($('#filters-toggle').hasClass('glyphicon-chevron-down'))
                {
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
