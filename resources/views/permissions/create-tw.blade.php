@extends('layouts.app-tw')

@section('title', 'Add Permission')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a New Permission</h1>
		<p class="text-muted-foreground">Create a new permission</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('permissions.store') }}" method="POST">
				@csrf

				@include('permissions.form-tw')
			</form>
		</div>
	</div>
</div>

@stop

@section('scripts.footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Initialize Select2 for groups
	$('#group_list').select2({
		placeholder: 'Choose a group',
		tags: true,
		theme: 'tailwind'
	});
});
</script>
@stop
