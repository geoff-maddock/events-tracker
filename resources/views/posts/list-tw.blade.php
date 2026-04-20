@if (count($posts) > 0)

@foreach ($posts as $post)
<div class="border border-border rounded-lg overflow-hidden" id="post-{{ $post->id }}">
    <div class="flex">

        {{-- Author sidebar (sm+) --}}
        <div class="hidden sm:flex flex-col items-center gap-2 py-5 px-3 bg-muted/30 border-r border-border w-28 flex-shrink-0 text-center">
            @if (isset($post->user))
            @include('users.avatar', ['user' => $post->user, 'size' => 'lg'])
            <a href="{{ route('users.show', [$post->user]) }}"
               class="text-xs font-medium text-foreground hover:text-primary leading-tight break-all">
                {{ $post->user->name }}
            </a>
            <span class="text-xs text-muted-foreground">{{ $post->created_at->format('M j, Y') }}</span>
            @else
            <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                <i class="bi bi-person text-xl text-muted-foreground/40"></i>
            </div>
            <span class="text-xs text-muted-foreground italic">Deleted</span>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0 p-4">

            {{-- Mobile author bar --}}
            <div class="sm:hidden flex items-center gap-2 mb-3 pb-3 border-b border-border">
                @if (isset($post->user))
                @include('users.avatar', ['user' => $post->user, 'size' => 'sm'])
                <div class="min-w-0">
                    <a href="{{ route('users.show', [$post->user]) }}" class="text-sm font-medium hover:text-primary">{{ $post->user->name }}</a>
                    <div class="text-xs text-muted-foreground">{{ $post->created_at->diffForHumans() }}</div>
                </div>
                @else
                <span class="text-sm text-muted-foreground italic">User deleted</span>
                @endif
                <span class="text-xs text-muted-foreground ml-auto">{{ $post->created_at->diffForHumans() }}</span>
            </div>

            {{-- Body --}}
            <div class="prose prose-sm max-w-none dark:prose-invert mb-4">
                @if (isset($post->user) && $post->user->can('trust_post'))
                    {!! $post->body !!}
                @else
                    {{ $post->body }}
                @endcan
            </div>

            {{-- Related entities & tags --}}
            @unless ($post->entities->isEmpty())
            <div class="mb-2 flex flex-wrap items-center gap-1">
                <span class="text-xs text-muted-foreground">Related:</span>
                @foreach ($post->entities as $entity)
                <a href="/posts/related-to/{{ urlencode($entity->slug) }}"
                   class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
                    {{ $entity->name }}
                    <a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" title="Show entity" class="ml-1">
                        <i class="bi bi-link-45deg"></i>
                    </a>
                </a>
                @endforeach
            </div>
            @endunless

            @unless ($post->tags->isEmpty())
            <div class="mb-3 flex flex-wrap items-center gap-1">
                <span class="text-xs text-muted-foreground">Tags:</span>
                @foreach ($post->tags as $tag)
                <x-tag-badge :tag="$tag" context="posts" />
                @endforeach
            </div>
            @endunless

            {{-- Footer actions --}}
            <div class="flex items-center gap-2 pt-3 border-t border-border">
                @if ($signedIn)
                    @if (($post->ownedBy($user) && $post->isRecent()) || $user->hasGroup('super_admin'))
                    <a href="{!! route('posts.edit', ['post' => $post->id]) !!}"
                       class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-card border border-border rounded hover:bg-accent transition-colors"
                       title="Edit">
                        Edit <i class="bi bi-pencil-fill"></i>
                    </a>
                    {!! link_form_bootstrap_icon('bi bi-trash-fill text-destructive', $post, 'DELETE', 'Delete', NULL, 'py-0 my-0', 'confirm') !!}
                    @endif
                    @if ($like = (isset($likedPostIds) ? array_key_exists($post->id, $likedPostIds) : $post->likedBy($user)))
                    <a href="{!! route('posts.unlike', ['id' => $post->id]) !!}"
                       class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-card border border-border rounded hover:bg-accent transition-colors"
                       title="Unlike">
                        <i class="bi bi-star-fill text-warning"></i>
                        Unlike
                    </a>
                    @else
                    <a href="{!! route('posts.like', ['id' => $post->id]) !!}"
                       class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-card border border-border rounded hover:bg-accent transition-colors"
                       title="Like">
                        <i class="bi bi-star"></i>
                        Like
                    </a>
                    @endif
                @endif
                @if($post->likes > 0)
                <span class="text-xs text-muted-foreground ml-auto"><i class="bi bi-star-fill text-warning/70"></i> {{ $post->likes }}</span>
                @endif
            </div>

        </div>
    </div>
</div>
@endforeach

@else
<div class="text-center py-8 text-muted-foreground italic text-sm">
    No replies yet.
</div>
@endif

@section('scripts.footer')
@stop
