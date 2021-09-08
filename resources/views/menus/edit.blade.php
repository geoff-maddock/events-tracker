@extends('app')

@section('title','Menu Edit')

@section('content')

<h1 class="display-6 text-primary">Menu. Edit @include('menus.crumbs', ['slug' => $menu->slug ?: $menu->id])</h1>
<div id="action-menu" class="mb-2">
	<a href="{!! route('menus.show', ['menu' => $menu->id]) !!}" class="btn btn-primary">Show Menu</a>
	<a href="{!! URL::route('menus.index') !!}" class="btn btn-info">Return to list</a>
</div>

	{!! Form::model($menu, ['route' => ['menus.update', $menu->id], 'method' => 'PATCH']) !!}

		@include('menus.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['menus.destroy', $menu->id]) !!}

	{!! link_to_route('menus.index','Return to list') !!}
@stop
