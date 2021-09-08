@if (isset($entities) && count($entities) > 0)

<?php $type = null; ?>
<ul class='list'>
	@foreach ($entities as $entity)
		@include('entities.single', ['entity' => $entity])
	@endforeach
</ul>
@else
	<div><small>No entities found.</small></div>
@endif

