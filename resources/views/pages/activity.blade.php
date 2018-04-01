@extends('app')

@section('title','Event Repo - Club Guide')

@section('content')

	<!-- NAV / FILTER -->
	<div class="row" class="tab-content filters-content">

		<div id="filters-container" class="col-sm-12">

			<a href="#" id="filters" class="btn btn-primary">Filters <span id="filters-toggle" class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
			{!! Form::open(['route' => ['activity.filter'], 'method' => 'GET']) !!}

			<div id="filter-list" @if (!$hasFilter)style="display: none"@endif >

				<!-- BEGIN: FILTERS -->
				<div class="form-group col-sm-2">
					{!! Form::label('filter_name','Filter By Name') !!}
					{!! Form::text('filter_name', (isset($filters['filter_name']) ?? NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_type','Filter By Type') !!}
					{!! Form::text('filter_type', (isset($filters['filter_type']) ?? NULL), ['class' =>'form-control']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_action','Filter By Action') !!}
                    <?php $actions = [''=>'&nbsp;'] + App\Action::orderBy('name', 'ASC')->pluck('name', 'name')->all();?>
					{!! Form::select('filter_action', $actions, (isset($filters['filter_action']) ?? NULL), ['data-width' => '100%', 'class' =>'form-control  auto-submit', 'data-placeholder' => 'Select an action']) !!}
				</div>

				<div class="form-group col-sm-2">
					{!! Form::label('filter_user_id','Filter By User') !!}
                    <?php $users = [''=>'&nbsp;'] + App\User::orderBy('name', 'ASC')->pluck('name', 'name')->all();?>
					{!! Form::select('filter_user', $users, (isset($filters['filter_user']) ?? NULL), ['data-width' => '100%', 'class' =>'form-control select2  auto-submit', 'data-placeholder' => 'Select a user']) !!}
				</div>

				<div class="form-group col-sm-1">
					{!! Form::label('filter_rpp','RPP') !!}
                    <?php $rpp_options =  [''=>'&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000];?>
					{!! Form::select('filter_rpp', $rpp_options, (isset($rpp) ?? NULL), ['class' =>'auto-submit form-control']) !!}
				</div>

				<div class="col-sm-2">
					<div class="btn-group col-sm-1">
						{!! Form::submit('Filter',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

						{!! Form::close() !!}

						{!! Form::open(['route' => ['activity.reset'], 'method' => 'GET']) !!}

						{!! Form::submit('Reset',  ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

						{!! Form::close() !!}
					</div>
				</div>
			</div>

		</div>
		<!-- END: FILTERS -->
	</div>

	<br style="clear: left;"/>

	<!-- LIST OF ALL RECENT ACTIVITY --> 
	<ul class="list-group">
	@if (count($activities) > 0)

		@foreach ($activities as $date => $record)
			<h4 >{{ $date }}</h4>
			@foreach ($record as $activity)
			<li class="list-group-item {{ $activity->style }} ">
				{{ $activity->id }}
				<a href="{{ strtolower($activity->getShowLink()) }}">{{ $activity->message }}</a>

				<br>

				<small>by <a href="users/{{ $activity->user_id }}">{{ $activity->userName }}</a> {{ isset($activity->created_at) ? ' on '.$activity->created_at->format('m/d/Y H:i') : '' }} {{ (isset($activity->ip_address) ? ' ['.$activity->ip_address.']' : '') }} </small>
			 </li>
			@endforeach
			@endforeach

	@endif

	</ul>
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