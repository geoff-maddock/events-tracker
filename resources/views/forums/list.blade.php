@if (count($forums) > 0)
<table class="table forum table-striped">
    <thead>
      <tr>
        <th>
          Name
        </th>
        <th class="cell-stat hidden-xs hidden-sm">Threads</th>
        <th class="cell-stat hidden-xs hidden-sm">Views</th>
        <th class="cell-stat-2x hidden-xs hidden-sm">Last Post</th>
      </tr>
    </thead>
<tbody>

	@foreach ($forums as $forum)

	<tr>
	<td>{!! link_to_route('forums.show', $forum->name, [$forum->id], ['id' => 'forum-name', 'title' => 'Forum topic.', 'class' => 'forum-link pe-2']) !!}
			@if ($signedIn && $forum->ownedBy($user))
			<a href="{!! route('forums.edit', ['forum' => $forum->id]) !!}" title="Edit this forum."><i class="bi bi-pencil-fill icon"></i></a>
      {!! link_form_bootstrap_icon('bi bi-trash-fill text-warning icon', $forum, 'DELETE', 'Delete the forum', NULL, 'delete') !!}
			@endif
	</td>
    <td>{{ $forum->threadsCount ?? 0 }}
    </td>
    <td class="cell-stat hidden-xs hidden-sm">0</td>
    <td class="cell-stat text-center hidden-xs hidden-sm"></td>
    </tr>

	@endforeach
</tbody>
</table>
@else
	<ul class='forum-list'><li class="flow-root"><i>No forums listed</i></li></ul>
@endif

@section('scripts.footer')
<script type="text/javascript">
$('button.delete').on('click', function(e){
  e.preventDefault();
  var form = $(this).parents('form');
  var type = $(this).data('type');
  Swal.fire({
    title: "Are you sure?",
    text: "You will not be able to recover this "+type+"!",
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
   //
  });
})
</script>
@stop