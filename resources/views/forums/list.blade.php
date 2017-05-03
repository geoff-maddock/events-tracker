@if (count($forums) > 0)
<table class="table forum table-striped">
    <thead>
      <tr>
        <th>
          Name
        </th>
        <th class="cell-stat hidden-xs hidden-sm">Threads</th>
        <th class="cell-stat text-center hidden-xs hidden-sm">Views</th>
        <th class="cell-stat-2x hidden-xs hidden-sm">Last Post</th>
      </tr>
    </thead>
<tbody>

	@foreach ($forums as $forum)

	<tr>
	<td>{!! link_to_route('forums.show', $forum->name, [$forum->id], ['id' => 'forum-name', 'title' => 'Forum topic.', 'class' => 'forum-link']) !!} 
			@if ($signedIn && $forum->ownedBy($user))
			<a href="{!! route('forums.edit', ['id' => $forum->id]) !!}" title="Edit this forum."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif


	</td>
    <td>{{ $forum->threadsCount or 0 }}
    </td>
    <td class="cell-stat hidden-xs hidden-sm">0</td>
    <td class="cell-stat text-center hidden-xs hidden-sm"></td>
    </tr>

	@endforeach
</tbody>
</table>
@else
	<ul class='forum-list'><li style='clear:both;'><i>No forums listed</i></li></ul> 
@endif
