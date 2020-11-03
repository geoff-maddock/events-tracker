@extends('app')

@section('title','Blog View')

@section('content')


<h1>Blog
	@include('blogs.crumbs', ['slug' => $blog->label])
</h1>

<P>
@can('edit_blog')
	<a href="{!! route('blogs.edit', ['blog' => $blog->id]) !!}" class="btn btn-primary">Edit Blog</a>
@endcan
	<a href="{!! URL::route('blogs.index') !!}" class="btn btn-info">Return to list</a>
</P>

<div class="row">
	<div class="profile-card col-md-4">
	<h2 class='item-title'>{{ $blog->label }}</h2>

	<p>

	@if ($blog->name)
	<label>Name: </label> <i>{{ $blog->name }} </i><br><br>
	@endif

	@if ($blog->slug)
	<label>Slug: </label> <i>{{ $blog->slug }} </i><br><br>
	@endif

    @if ($blog->body)
            <p>
            {!! $blog->body !!}
            </p>
    @endif

    @unless ($blog->tags->isEmpty())

		<P><b>Tags:</b>

		@foreach ($blog->tags as $tag)
		<span class="label label-tag"><a href="/tags/{{ $tag->id }}">{{ $tag->name }}</a></span>
		@endforeach

	@endunless

    @unless ($blog->entities->isEmpty())

        <P><b>Entities:</b>

            @foreach ($blog->entities as $entity)
                <span class="label label-tag"><a href="/entities/{{ $entity->slug }}">{{ $entity->name }}</a></span>
        @endforeach

    @endunless

	@can('edit_blog')
		{!! link_form_icon('glyphicon-trash text-warning', $blog, 'DELETE', 'Delete the [blog]') !!}
	@endcan
  </div>


@stop

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  Swal.fire({
    title: "Are you sure?",
    text: "You will not be able to recover this blog!",
    type: "warning",
    showCancelButton: true,
    confirmButtonColor: "#DD6B55",
    confirmButtonText: "Yes, delete it!",
    closeOnConfirm: true
  },
   function(isConfirm){
   	if (isConfirm) {
    	form.submit();
   	};
  });
})
</script>
@stop
