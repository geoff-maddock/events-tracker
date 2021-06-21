@extends('app')

@section('title','Posts')

@section('content')

    <h4>Forum . Latest Posts</h4>

    <div id="action-menu" style="margin-bottom: 5px;">
        <a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
        <a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paged threads</a>
        <a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
    </div>

        <div id="filters-container" class="row">
            <div id="filters-content" class="col-lg-9">
                <a href="#" id="filters" class="btn btn-primary">Filters
                    <span id="filters-toggle"
                        class="glyphicon @if (!$hasFilter) glyphicon-chevron-down @else glyphicon-chevron-up @endif"></span></a>
                        {!! Form::open(['route' => [$filterRoute ?? 'posts.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
        
                    <div id="filter-list" class="row @if (!$hasFilter) d-block d-xs-none @endif"
                    style="@if (!$hasFilter) display: none; @endif">
        
                    <div class="form-group col-sm-2 ">
        
                        {!! Form::label('filter_body','Filter By Body') !!}
        
                        {!! Form::text('filter_body', (isset($filters['body']) ? $filters['body'] : NULL),
                        [
                            'class' =>'form-control',
                            'name' => 'filters[body]'
                        ]
                        ) !!}
                    </div>
        
                    <div class="form-group col-sm-2">
                        {!! Form::label('filter_user','Filter By User') !!}
                        {!! Form::select('filter_user', $userOptions, (isset($filters['user']) ? $filters['user'] :
                        NULL), 
                        [
                            'data-theme' => 'bootstrap',
                            'data-width' => '100%', 
                            'class' => 'form-control select2', 
                            'data-placeholder' => 'Select a user',
                            'name' => 'filters[user]'
                            ]) !!}
                    </div>
        
                    <div class="form-group col-sm-2">
                        {!! Form::label('filter_tag','Filter By Tag') !!}
                        {!! Form::select('filter_tag', $tagOptions, (isset($filters['tag']) ? $filters['tag'] : NULL),
                        [
                            'data-theme' => 'bootstrap',
                             'data-width' => '100%',
                             'class' => 'form-control select2',
                             'data-placeholder' => 'Select a tag',
                             'name' => 'filters[tag]'
                        ]
                        ) !!}
                    </div>
        
                    <div class="col-sm-2">
                        <div class="btn-group col-sm-1">
                            <label></label>
                            {!! Form::submit('Apply', ['class' =>'btn btn-primary btn-sm btn-tb mx-2', 'id' =>
                            'primary-filter-submit']) !!}
                            {!! Form::close() !!}
                            {!! Form::open(['route' => ['posts.reset'], 'method' => 'GET']) !!}
                            {!! Form::submit('Reset', ['class' =>'btn btn-primary btn-sm btn-tb', 'id' =>
                            'primary-filter-reset']) !!}
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
            <div id="list-control" class="col-lg-3 visible-lg-block visible-md-block text-right">
                <form action="{{ url()->current() }}" method="GET" class="form-inline">
                    <div class="form-group">
                        <a href="{{ url()->action('PostsController@rppReset') }}" class="btn btn-primary">
                            <span class="glyphicon glyphicon-repeat"></span>
                        </a>
                        {!! Form::select('limit', $limitOptions, ($limit ?? 10), ['class' =>'form-control auto-submit']) !!}
                        {!! Form::select('sort', $sortOptions, ($sort ?? 'name'), ['class' =>'form-control
                        auto-submit']) !!}
                        {!! Form::select('direction', $directionOptions, ($direction ?? 'asc'), ['class' =>'form-control
                        auto-submit']) !!}
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
                        {!! link_to_route('users.show', $post->user->name, [$post->thread->user->id], ['class' => 'forum-link']) !!}
                    @else
                        User deleted
                    @endif
                </td>
                <td class="hidden-xs hidden-sm">
                    {!! link_to_route('threads.show', $post->thread->name, [$post->thread->id], ['id' => 'thread-name', 'title' => 'Thread topic.', 'class' => 'forum-link']) !!}
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
                            <a href="{!! route('posts.edit', ['post' => $post->id]) !!}" title="Edit this post."><span class='glyphicon glyphicon-pencil text-primary'></span></a>
                            {!! link_form_icon('glyphicon-trash text-warning', $post, 'DELETE', 'Delete the post', NULL, 'delete') !!}                            

                        @endif
                        @if ($signedIn)
                            @if ($like = $post->likedBy($user))
                                <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}" title="Click to unlike"><span class='glyphicon glyphicon-star text-success'></span></a>
                            @else
                                <a href="{!! route('posts.like', ['id' => $post->id]) !!}" title="Click to like"><span class='glyphicon glyphicon-star-empty text-warning'></span></a>
                            @endif
                        @endif

                </span>

                    <br>

                    @unless ($post->entities->isEmpty())
                        Related:
                        @foreach ($post->entities as $entity)
                            <span class="label label-tag"><a href="/posts/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
                        @endforeach
                    @endunless

                    @unless ($post->tags->isEmpty())
                        Tags:
                        @foreach ($post->tags as $tag)
                            <span class="label label-tag"><a href="/posts/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
                        @endforeach
                    @endunless
                </td>
            </tr>
            </tbody>
        @endforeach
            {!! $posts->render() !!}
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