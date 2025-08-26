@extends('app')

@section('title','Blog View')

@section('content')


<h1 class="display-6 text-primary">Blog	@include('blogs.crumbs', ['slug' => $blog->label])</h1>

<div id="action-menu" class="mb-2">
@can('edit_blog')
	<a href="{!! route('blogs.edit', ['blog' => $blog->slug]) !!}" class="btn btn-primary">Edit Blog</a>
@endcan
	<a href="{!! URL::route('blogs.index') !!}" class="btn btn-info">Return to list</a>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">{{ $blog->name }}</h5>

    @if ($blog->slug)
    <small>{{ $blog->slug }}</small>
    @endif

    @if ($blog->body)
      <p>{!! $blog->body !!}</p>
    @endif

    @unless ($blog->tags->isEmpty())

		<P><b>Tags:</b>

		@foreach ($blog->tags as $tag)
		  <span class="badge rounded-pill bg-dark"><a href="/tags/{{ $tag->slug }}">{{ $tag->name }}</a></span>
		@endforeach

	@endunless

    @unless ($blog->entities->isEmpty())

        <P><b>Entities:</b>

            @foreach ($blog->entities as $entity)
                <span class="badge rounded-pill bg-dark"><a href="/entities/{{ $entity->slug }}">{{ $entity->name }}</a></span>
        @endforeach

    @endunless

    @can('edit_blog')
      {!! delete_form(['blogs.destroy', $blog->slug, null, 'my-2']) !!}
    @endcan
  </div>
@stop

@section('scripts.footer')
<script type="text/javascript">
    $('button.delete').on('click', function(e){
        e.preventDefault();
        const form = $(this).parents('form');
        Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this blog!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
				preConfirm: function() {
					return new Promise(function(resolve) {
						setTimeout(function() {
							resolve()
						}, 2000)
					})
				}
            }).then(result => {
            if (result.value) {
                // handle Confirm button click
                // result.value will contain `true` or the input value
                form.submit();
            } else {
                // handle dismissals
                // result.dismiss can be 'cancel', 'overlay', 'esc' or 'timer'
                console.log('cancelled confirm')
            }
        });
})
</script>
@stop
