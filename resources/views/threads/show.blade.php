@extends('app')

@section('title')
Forum Thread "{{ $thread->name }}"
@endsection 

@section('content')

	<h1 class="display-crumbs text-primary">Forum
		@include('threads.crumbs')
	</h4>

    <div id="action-menu" class="mb-2">
	<a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
	<a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paginated threads</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add an thread</a>
	</div>

	<div class="row">

	<div class="col-lg-12">
	<table class="table forum table-striped">
	    <thead>
	      <tr>
	        <th>Thread</th>
	        <th class="cell-stat d-none d-md-table-cell">Category</th>
	        <th class="cell-stat">User</th>
	        <th class="cell-stat text-center d-none d-md-table-cell">Posts</th>
	        <th class="cell-stat text-center d-none d-md-table-cell">Views</th>
            <th class="cell-stat text-center d-none d-md-table-cell">Likes</th>
	        <th class="cell-stat-2x hidden-xs">Last Post</th>
	      </tr>
	    </thead>
	<tbody>

        @include('threads.first', ['thread' => $thread])
        @include('posts.list', ['thread' => $thread, 'posts' => $thread->posts])

        </tbody>
        </table>
        </div>

        <div class="col-lg-6">

            @if ($thread->is_locked)
            <P class="text-center">This thread has been locked.</P>
            @else
                @if ($signedIn)
                Add new post as <strong>{{ $user->name }}</strong>
                <form method="POST" action="{{ $thread->path().'/posts' }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <textarea name="body" id="body" class="form-control form-background" autofocus placeholder="Have something to say?" rows="5"></textarea>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                    {!! Form::label('tag_list','Tags:') !!}
                    {!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list', 'class' => 'form-control select2',
                    'data-placeholder' => 'Choose a tag',
                    'data-tags' =>'true',
                    'multiple']) !!}
                    {!! $errors->first('tags','<span class="help-block">:message</span>') !!}
                    </div>
                </div>

                <button type="submit" class="btn btn-primary my-2">Post</button>
                </form>

                @else
                <p class="text-center">Please <a href="{{ url('/login')}}">sign in</a> to participate in this discussion.</p>
                @endif
            @endif
        </div>

        </div>
    @stop