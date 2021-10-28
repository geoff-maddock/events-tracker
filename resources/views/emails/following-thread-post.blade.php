There was a new post in a thread that you are following with the subject <b>{!! $object->name !!}</b>.

<div class='event-date'>
    <h2>{!! $thread->created_at->format('l F jS Y') !!}</h2>
</div>

<h2>{{ $thread->name }}</h2>
<i><a href="{{ url('threads/'.$thread->id) }}">{{ $thread->description }}</a></i><br>

<p>
{{ $thread->body }}
</p>

...
<b>New Post</b> by {{ $post->user->name}} at {!! $post->created_at->format('l F jS Y') !!}
<P>
{{ $post->body }}
</P>


<P>
    @unless ($thread->series->isEmpty())
        Related Series:
        @foreach ($thread->series as $s)
            <span class="label label-tag"><a href="{{ url('threads/related-to/'.$s->slug) }}">{{ $s->name }}</a></span>
        @endforeach
    @endunless
</P>

@unless ($thread->tags->isEmpty())
    <P>Tags:
        @foreach ($thread->tags as $tag)
            <span class="label label-tag"><a href="{{ url('threads/tag/'.$tag->name) }}">{{ $tag->name }}</a></span>
        @endforeach
        @endunless
    </P>
    </div>
    </div>