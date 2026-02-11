{!! Form::open(['route' => ['entities.filter'], 'method' => 'GET']) !!}

<!-- BEGIN: FILTERS -->
@if ($hasFilter)

<div class="form-group col-sm-2">

    {!! Form::label('filter_name','Filter By Name') !!}

    {!! Form::text('filter_name', (isset($filters['filter_name']) ? $filters['filter_name'] : NULL), ['class'
    =>'form-control']) !!}
</div>

<div class="form-group col-sm-2">

    {!! Form::label('filter_role','Filter By Role') !!}
    <?php $roles = ['' => '&nbsp;'] + App\Models\Role::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
    {!! Form::select('filter_role', $roles, (isset($filters['filter_role']) ? $filters['filter_role'] : NULL), ['class'
    =>'form-control select2', 'data-placeholder' => 'Select a role']) !!}
</div>

<div class="form-group col-sm-2">
    {!! Form::label('filter_tag','Filter By Tag') !!}
    <?php $tags = ['' => '&nbsp;'] + App\Models\Tag::orderBy('name', 'ASC')->pluck('name', 'name')->all(); ?>
    {!! Form::select('filter_tag', $tags, (isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL), ['class'
    =>'form-control select2', 'data-placeholder' => 'Select a tag']) !!}
</div>

<div class="form-group col-sm-2">
    {!! Form::label('filter_active_range','Filter By Active Range') !!}
    <?php $active_range_options = ['' => '&nbsp;', '1-week' => '1 Week', '1-month' => '1 Month', '3-months' => '3 Months', '1-year' => '1 Year', '2-years' => '2 Years', '5-years' => '5 Years']; ?>
    {!! Form::select('filters[active_range]', $active_range_options, (isset($filters['active_range']) ? $filters['active_range'] : NULL), ['class'
    =>'form-control', 'data-placeholder' => 'Select active range', 'id' => 'filter_active_range']) !!}
</div>

<div class="form-group col-sm-1">
    {!! Form::label('filter_rpp','RPP') !!}
    <?php $rpp_options = ['' => '&nbsp;', 5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000]; ?>
    {!! Form::select('filter_rpp', $rpp_options, (isset($rpp) ? $rpp : NULL), ['class' =>'form-control']) !!}
</div>
@endif

<div class="col-sm-2">
    <div class="btn-group col-sm-1">
        {!! Form::submit('Filter', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-submit']) !!}

        {!! Form::close() !!}

        {!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}

        {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

        {!! Form::close() !!}
    </div>
</div>