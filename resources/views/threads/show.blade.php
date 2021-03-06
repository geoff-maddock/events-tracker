@extends('app')

@section('title','Forum')

@section('content')

	<h4>Forum
		@include('threads.crumbs')
	</h4>

	<p>
	<a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
	<a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paginated threads</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add an thread</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	<div class="col-lg-12">
	<table class="table forum table-striped">
	    <thead>
	      <tr>
	        <th>Thread</th>
	        <th class="cell-stat hidden-xs hidden-sm">Category</th>
	        <th class="cell-stat">Author</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Posts</th>
	        <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
            <th class="cell-stat text-center hidden-xs hidden-sm">Likes</th>
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
                    <textarea name="body" id="body" class="form-control" placeholder="Have something to say?" rows="5"></textarea>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                    {!! Form::label('tag_list','Tags:') !!}
                    {!! Form::select('tag_list[]', $tags, null, ['id' => 'tag_list', 'class' =>'form-control select2',
                    'data-placeholder' => 'Choose a tag',
                    'data-tags' =>'true',
                    'multiple']) !!}
                    {!! $errors->first('tags','<span class="help-block">:message</span>') !!}
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Post</button>
                </form>

                @else
                <p class="text-center">Please <a href="{{ url('/login')}}">sign in</a> to participate in this discussion.</p>
                @endif
            @endif
        </div>

        </div>
    @stop