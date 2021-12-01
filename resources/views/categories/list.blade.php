<table class="table table-striped">
	<thead>
	<!-- CONVERT TO PARTIAL THAT BUILDS THE HEADERS FROM A CONFIGURED ARRAY OR ARRAY FROM DB - SEE MY WORK NOTES -->
	<tr class="bg-info">
		<th><a href="?sort=id&sdirection={{ $direction == 'desc' ? 'asc' : 'desc' }}">ID</a></th>
		<th><a href="?sort=name&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Name</a></th>
		<th><a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Slug</a></th>
		<th><a href="?sort=slug&direction={{ $direction == 'desc' ? 'asc' : 'desc' }}">Created At</a></th>
		<th style="width: 60px"></th>
	</tr>
	</thead>
@if (isset($categories) && count($categories) > 0)

@foreach ($categories as $category)
	<tr>
		<td>{!! $category->id !!}</td>
		<td>{!! link_to_route('categories.show', $category->name, [$category->id], ['class' => 'item-title']) !!}</td>
		<td>{!! $category->forum->name !!}</td>
		<td>{!! $category->created_at !!}</td>
		<td>
			@can('edit_category')
			<a href="{!! route('categories.edit', ['category' => $category->id]) !!}"><i class="bi bi-pencil-fill icon"></i></a>
      		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $category, 'DELETE', 'Delete the category') !!}
			@endcan
		</td>

	</tr>
	@endforeach
@else
	<tr>
		<td colspan="5">
			<i>No categories listed</i>
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
    text: "You will not be able to recover this category!",
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
