@extends('app')

@section('title','Menu View')

@section('content')


<h2>{{ $menu->name }}
	@include('menus.crumbs', ['slug' => $menu->slug])
</h2>


<div class="row">

    @forelse ($menu->blogs as $blog)
        <div class="profile-card col-md-8 mx-2">
            @if ($blog->contentType->name === "HTML")
                {!! $blog->body !!}
            @else
                {{ $blog->body }}
            @endif
            <br>
            <small>{{ $blog->created_at->format('l F jS Y') }}</small>
        </div>

    @empty
        <p>No blog posts</p>
    @endforelse


@stop
 
@section('scripts.footer')
@include('partials.social-footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  Swal.fire({
    title: "Are you sure?",
    text: "You will not be able to recover this menu!", 
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
