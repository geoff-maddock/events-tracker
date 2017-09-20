You might be interested in this new thread because you are following <b>{!! $object->name !!}</b>.

<div class='event-date'>
    <h2>{!! $thread->created_at->format('l F jS Y') !!}</h2>
</div>

<h2>{{ $thread->name }}</h2>
<i><a href="{{ url('threads/'.$thread->id) }}">{{ $thread->description }}</a></i><br>

<p>
{{ $thread->body }}
</p>

    <br>
    <i>Added by <a href="{{ url('users/'.$thread->user->id) }}">{{ $thread->user->name or '' }}</a></i>

<P>
    @unless ($thread->series->isEmpty())
        Related Series:
        @foreach ($thread->series as $s)
            <span class="label label-tag"><a href="{{ url('threads/relatedto/'.$s->slug) }}">{{ $s->name }}</a></span>
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