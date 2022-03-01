@extends('app')

@section('title','Blogs')

@section('content')

<h1 class="display-6 text-primary">Blogs @include('blogs.crumbs')</h1>
		<div id="action-menu" class="mb-2">
			<a href="{!! URL::route('blogs.create') !!}" class="btn btn-primary">Add a blog</a>
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
				{!! Form::open(['route' => [$filterRoute ?? 'blogs.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
		
				<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
					<div class="row">
						<div class="col-sm">
							{!! Form::label('filter_name','Name') !!}
							{!! Form::text('filter_name', (isset($filters['name']) ? $filters['name'] : NULL),
							[
								'class' => 'form-control form-background',
								'name' => 'filters[name]'
							]) !!}
						</div>

						<div class="col-sm">
							{!! Form::label('filter_body','Body') !!}
							{!! Form::text('filter_body', (isset($filters['body']) ? $filters['body'] : NULL),
							[
								'class' => 'form-control form-background',
								'name' => 'filters[body]'
							]) !!}
						</div>

						<div class="col-sm">
							{!! Form::label('filter_user', 'User') !!}
							{!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
							NULL), 
							[
								'data-theme' => 'bootstrap-5',
								'data-width' => '100%', 
								'class' => 'form-control select2', 
								'data-placeholder' => 'Select a user',
								'name' => 'filters[user]'
								]) !!}
						</div>
	
						<div class="col-sm">
							{!! Form::label('filter_tag', 'Tag') !!}
							{!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
							[
								'data-theme' => 'bootstrap-5',
								'data-width' => '100%',
								'class' => 'form-control select2',
								'data-placeholder' => 'Select a tag',
								'name' => 'filters[tag]'
							]
							) !!}
						</div>

					</div>
					<div class="row my-1">
						<div class="col-sm-2">
							<div class="btn-group col-sm-1">
							<label></label>
							{!! Form::submit('Apply',
							[
							'class' => 'btn btn-primary btn-sm btn-tb mx-2',
							'id' => 'primary-filter-submit'
							])
							!!}
							{!! Form::close() !!}
							{!! Form::open(['route' => ['blogs.reset'], 'method' => 'GET']) !!}
							{!! Form::hidden('redirect', $redirect ?? 'blogs.index') !!}
							{!! Form::hidden('key', $key ?? 'internal_blog_index') !!}
							{!! Form::submit('Reset',
								[
								'class' => 'btn btn-primary btn-sm btn-tb',
								'id' => 'primary-filter-reset'
								])
							!!}
							{!! Form::close() !!}
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div id="list-control" class="col-xl-3 visible-lg-block visible-md-block text-right my-2">
				<form action="{{ url()->current() }}" method="GET" class="form-inline">
					<div class="form-group row gx-1 justify-content-end">
						<div class="col-auto">
							<a href="{{ url()->action('BlogsController@rppReset') }}" class="btn btn-primary">
								<i class="bi bi-arrow-clockwise"></i>
							</a>
						</div>
						<div class="col-auto">
							{!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
						</div>
						<div class="col-auto">
							{!! Form::select('sort', $sortOptions, ($sort ?? 'blogs.created_at'), ['class' => 'form-background form-select auto-submit']) !!}
						</div>
						<div class="col-auto">
							{!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-background form-select auto-submit']) !!}
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class='col-xl-6'>
		{!! $blogs->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links() !!}
			@include('blogs.list', ['blogs' => $blogs])
		{!! $blogs->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links() !!}
		</div>

@stop


@section('footer')
@include('partials.filter-js')
@endsection