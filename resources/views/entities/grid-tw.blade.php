@if (isset($entities) && count($entities) > 0)
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
	@foreach ($entities as $entity)
		@include('entities.card-tw', ['entity' => $entity])
	@endforeach
</div>
@else
<div class="text-center py-8">
	<i class="bi bi-building text-4xl text-gray-600 mb-2"></i>
	<p class="text-gray-400">No entities found.</p>
</div>
@endif
