{!! Form::open(['route' => ['events.filter'], 'method' => 'GET']) !!}

<!-- BEGIN: FILTERS -->
@if ($hasFilter)

<div class="form-group col-sm-2">

    {!! Form::label('filter_name','Filter By Name') !!}

    {!! Form::text('filter_name', (isset($name) ? $name : NULL), ['class' =>'form-control']) !!}
</div>

<div class="form-group col-sm-2">

    {!! Form::label('filter_venue','Filter By Venue') !!}
    <?php $venues = ['' => ''] + App\Models\Entity::getVenues()->pluck('name', 'name')->all(); ?>
    {!! Form::select('filter_venue', $venues, (isset($venue) ? $venue : NULL), ['class' =>'form-control select2']) !!}
</div>

<div class="form-group col-sm-2">
    {!! Form::label('filter_tag','Filter By Tags') !!}
    <?php $tags = ['' => '&nbsp;'] + App\Models\Tag::orderBy('name', 'ASC')->lists('name', 'name')->all(); ?>
    {!! Form::select('filters[tag][]', $tags, (isset($tag) ? $tag : NULL), ['class' =>'form-control select2', 'multiple' => true]) !!}
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

        {!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}

        {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}

        {!! Form::close() !!}
    </div>
</div>