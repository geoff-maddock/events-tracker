<div class="panel">
<table class="table">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort_by=id&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort_by=name&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort_by=slug&sort_direction={{ $sortDirection == 'desc' ? 'asc' : 'desc' }}">Slug</a></th>
		<th style="width: 60px"></th>
	</tr>
	</thead>
@if (isset($blogs) && count($blogs) > 0)

@foreach ($blogs as $blog)
	<tr>
		<td>{!! $blog->id !!}</td>
		<td>{!! link_to_route('blogs.show', $blog->name, [$blog->id], ['class' => 'item-title']) !!}</td>
		<td>{!! $blog->slug !!}</td>
		<td>
			@can('edit_blog')
			<a href="{!! route('blogs.edit', ['blog' => $blog->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
      		{!! link_form_icon('glyphicon-trash text-warning', $blog, 'DELETE', 'Delete the blog') !!}
			@endcan
		</td>

	</tr>
	@endforeach
@else
	<tr>
		<td colspan="5">
			<i>No blogs listed</i>
		</td>
	</tr>
@endif
</table>
</div>

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
