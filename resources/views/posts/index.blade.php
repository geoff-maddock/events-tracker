@extends('app')

@section('title','Posts')

@section('content')

    <h1>Forum . Latest Posts
    </h1>

    <p>
        <a href="{{ url('/threads/all') }}" class="btn btn-info">Show all threads</a>
        <a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Show paged threads</a>
        <a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>

    <div class="row">

    @if (count($posts) > 0)
        <div class="col-md-12">
        <table class="table forum table-striped">
        <thead>
        <tr>
            <th>
                User
            </th>
            <th class="cell-stat hidden-xs">Category</th>
            <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
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
                            {!! link_form_icon('glyphicon-trash text-warning', $post, 'DELETE', 'Delete the [post]') !!}

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
