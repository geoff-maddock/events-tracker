@extends('app')

@section('title','Posts')

@section('content')

<h1 class="display-6 text-primary">Forum . Latest Posts</h1>

<div id="action-menu" class="mb-2">
    <a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
    <a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paged threads</a>
    <a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
</div>

<div id="filters-container" class="row my-2">
	<div id="filters-content" class="col-xl-9">
		<a href="#" id="filters" class="btn btn-primary">
			Filters 
			<span id="filters-toggle" class="@if (!$hasFilter) filter-closed @else filter-open @endif">
                <i class="bi bi-chevron-down"></i>
			</span>
		</a>
        {!! Form::open(['route' => [$filterRoute ?? 'posts.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
        
		<div id="filter-list" class="px-2 @if (!$hasFilter)d-none @endif">
            <div class="row">
                <div class="col-sm">
        
                        {!! Form::label('filter_body','Body') !!}
        
                        {!! Form::text('filter_body', (isset($filters['body']) ? $filters['body'] : NULL),
                        [
                            'class' => 'form-control form-control form-background',
                            'name' => 'filters[body]'
                        ]
                        ) !!}
                </div>

                <div class="col-sm">
                        {!! Form::label('filter_user','User') !!}
                        {!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
                        NULL), 
                        [
                            'data-theme' => 'bootstrap-5',
                            'data-width' => '100%', 
                            'class' => 'form-select select2', 
                            'data-placeholder' => 'Select a user',
                            'name' => 'filters[user]'
                            ]) !!}
                </div>

                <div class="col-sm">
                        {!! Form::label('filter_tag','Tag') !!}
                        {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
                        [
                            'data-theme' => 'bootstrap-5',
                             'data-width' => '100%',
                             'class' => 'form-select form-background select2',
                             'data-placeholder' => 'Select a tag',
                             'name' => 'filters[tag]'
                        ]
                        ) !!}
                </div>
            </div>
                <div class="row my-2">
                    <div class="col-sm-2">
                        <div class="btn-group col-sm-1">
                            <label></label>
                            {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' => 'primary-filter-submit']) !!}
                            {!! Form::close() !!}
                            {!! Form::open(['route' => ['posts.reset'], 'method' => 'GET']) !!}
                            {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' => 'primary-filter-reset']) !!}
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
                <a href="{{ url()->action('PostsController@rppReset') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
                </div>
                <div class="col-auto">
                    {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
                <div class="col-auto">
                    {!! Form::select('sort', $sortOptions, ($sort ?? 'threads.created_at'), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
                <div class="col-auto">
                    {!! Form::select('direction', $directionOptions, ($direction ?? 'desc'), ['class' => 'form-background form-select auto-submit']) !!}
                </div>
            </div>
        </form>
        </div>
    </div>

    <div class="row">

    @if (count($posts) > 0)
        <div class="col-md-12">
        <table class="table forum table-striped">
            <thead>
            <tr>
                <th>User</th>
                <th class="cell-stat hidden-xs">Thread</th>
                <th class="cell-stat hidden-xs">Category</th>
                <th class="cell-stat text-center hidden-xs hidden-sm">Likes</th>
                <th class="cell-stat hidden-xs">Last Post</th>
            </tr>
            </thead>
        @foreach ($posts as $post)
            <tbody class='thread-post'>
                <tr id='post-{{ $post->id }}'>
                    <td>
                        @if (isset($post->user))
                            @include('users.avatar', ['user' => $post->user])
                            {!! link_to_route('users.show', $post->user->name, [$post->user->id], ['class' => 'forum-link']) !!}
                        @else
                            User deleted
                        @endif
                    </td>
                    <td class="hidden-xs hidden-sm">
                        {!! link_to_route('threads.show', $post->thread->name, [$post->thread ? $post->thread->id : 0], ['id' => 'thread-name', 'title' => 'Thread topic.', 'class' => 'forum-link']) !!}
                    </td>
                    <td class="hidden-xs hidden-sm">{{ $post->thread->threadCategory ? $post->thread->threadCategory->name : 'General' }}</td>
                    <td class="cell-stat text-center hidden-xs hidden-sm">{{ $post->likes }}</td>
                    <td class="hidden-xs">{{ $post->created_at->diffForHumans() }}</td>
                </tr>
                <tr>
                    <td colspan='7' class="post-body">
                        <!-- TO DO: change this to storing the trust in the user at post save -->
                        @if (isset($post->user) && $post->user->can('trust_post'))
                            {!! $post->body !!}
                        @else
                            {{ $post->body }}
                        @endcan
                        <span>

                        @if ($signedIn && (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin')))
                                    <a href="{!! route('posts.edit', ['post' => $post->id]) !!}" title="Edit this post."><i class="bi bi-pencil-fill icon"></i></span></a>
                                    {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $post, 'DELETE', 'Delete the post', NULL, 'delete') !!}                            

                                @endif
                                @if ($signedIn)
                                    @if ($like = $post->likedBy($user))
                                        <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}" title="Click to unlike"><i class="bi bi-star-fill icon"></i></a>
                                    @else
                                        <a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Click to like"><i class="bi bi-star icon"></i></a>
                                    @endif
                                @endif

                        </span>

                        <br>

                        @unless ($post->entities->isEmpty())
                            Related:
                            @foreach ($post->entities as $entity)
                                <span class="badge rounded-pill bg-dark"><a href="/posts/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
                            @endforeach
                        @endunless

                        @unless ($post->tags->isEmpty())
                            Tags:
                            @foreach ($post->tags as $tag)
                                <span class="badge rounded-pill bg-dark"><a href="/posts/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
                            @endforeach
                        @endunless
                    </td>
                </tr>
            </tbody>
        @endforeach
            {!! $posts->onEachSide(2)->links() !!}
        </div>
        @else
            <tr>
                <td colspan="7"><i>No posts listed</i></td>
            </tr>
        @endif
    </table>
    </div>
@stop

@section('footer')
@include('partials.filter-js')
@endsection