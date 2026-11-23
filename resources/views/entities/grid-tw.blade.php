@if (isset($entities) && count($entities) > 0)
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
	@foreach ($entities as $entity)
		@include('entities.card-tw', ['entity' => $entity])
	@endforeach
</div>
@else
<x-empty-state icon="bi-building" compact>No entities found.</x-empty-state>
@endif
