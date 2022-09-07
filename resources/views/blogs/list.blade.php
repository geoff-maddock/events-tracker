<table class="table table-striped">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort=id&sdirection={{ $direction == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort=name&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Slug</a></th>
		<th><a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Created At</a></th>
		<th class="col-1"></th>
	</tr>
	</thead>
@if (isset($blogs) && count($blogs) > 0)

@foreach ($blogs as $blog)
	<tr>
		<td>{!! $blog->id !!}</td>
		<td>{!! link_to_route('blogs.show', $blog->name, [$blog->slug], ['class' => 'item-title']) !!}</td>
		<td>{!! $blog->slug !!}</td>
		<td>{!! $blog->created_at !!}</td>
		<td>
			@can('edit_blog')
			<a href="{!! route('blogs.edit', ['blog' => $blog->slug]) !!}"><i class="bi bi-pencil-fill icon"></i></a>
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
