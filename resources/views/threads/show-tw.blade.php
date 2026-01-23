@extends('layouts.app-tw')

@section('title')
Forum Thread "{{ $thread->name }}"
@endsection

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Back Button -->
<div class="mb-6">
	<a href="{{ URL::previous() }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
		<i class="bi bi-arrow-left"></i>
		<span>Back</span>
	</a>
</div>

<div class="w-full space-y-6">

	<!-- Header -->
	<div>
		<h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">
			Forum @include('threads.crumbs')
		</h1>
	</div>

	<!-- Action Menu -->
	<div class="flex flex-wrap items-center gap-2 mb-6">
		<x-ui.button variant="secondary" href="{{ url('/threads/all') }}">
			<i class="bi bi-list mr-2"></i>
			Show all threads
		</x-ui.button>
		<x-ui.button variant="secondary" href="{{ URL::route('threads.index') }}">
			<i class="bi bi-card-list mr-2"></i>
			Show paginated threads
		</x-ui.button>
		<x-ui.button variant="default" href="{{ URL::route('threads.create') }}">
			<i class="bi bi-plus-circle mr-2"></i>
			Add a thread
		</x-ui.button>
	</div>

	<!-- Thread Content -->
	<div class="rounded-lg border bg-card shadow">
		<div class="overflow-x-auto">
			<table class="w-full">
				<thead class="bg-primary/10 border-b border-border">
					<tr class="text-sm font-semibold text-muted-foreground">
						<th class="px-4 py-3 text-left">Thread</th>
						<th class="px-4 py-3 text-left hidden md:table-cell">Category</th>
						<th class="px-4 py-3 text-left">User</th>
						<th class="px-4 py-3 text-center hidden md:table-cell">Posts</th>
						<th class="px-4 py-3 text-center hidden md:table-cell">Views</th>
						<th class="px-4 py-3 text-center hidden md:table-cell">Likes</th>
						<th class="px-4 py-3 text-left hidden sm:table-cell">Last Post</th>
					</tr>
				</thead>
				<tbody>
					@include('threads.first', ['thread' => $thread])
					@include('posts.list', ['thread' => $thread, 'posts' => $thread->posts])
				</tbody>
			</table>
		</div>
	</div>

	<!-- Reply Form -->
	<div class="rounded-lg border bg-card shadow p-6">
		@if ($thread->is_locked)
			<p class="text-center text-muted-foreground">This thread has been locked.</p>
		@else
			@if ($signedIn)
				<div class="mb-4">
					<span class="text-sm text-muted-foreground">
						Add new post as <strong class="text-foreground">{{ $user->name }}</strong>
					</span>
				</div>
				<form method="POST" action="{{ $thread->path().'/posts' }}" class="space-y-4">
					@csrf

					<div>
						<x-ui.textarea
							name="body"
							id="body"
							placeholder="Have something to say?"
							rows="5"
							autofocus
						/>
					</div>

					<div>
						<label for="tag_list" class="block text-sm font-medium text-muted-foreground mb-2">Tags:</label>
						{!! Form::select('tag_list[]', $tags, null, [
							'id' => 'tag_list',
							'class' => 'form-select-tw select2',
							'data-placeholder' => 'Choose a tag',
							'data-tags' => 'true',
							'multiple' => true
						]) !!}
						@error('tags')
							<span class="text-sm text-destructive mt-1 block">{{ $message }}</span>
						@enderror
					</div>

					<x-ui.button type="submit" variant="default">
						<i class="bi bi-send mr-2"></i>
						Post
					</x-ui.button>
				</form>

			@else
				<p class="text-center text-muted-foreground">
					Please <a href="{{ url('/login') }}" class="text-primary hover:underline">sign in</a> to participate in this discussion.
				</p>
			@endif
		@endif
	</div>
</div>

@stop

@section('scripts.footer')
<script>
	// Initialize Select2
	$(document).ready(function() {
		$('.select2').select2({
			theme: 'tailwind',
			width: '100%',
			tags: true
		});
	});
</script>
@stop
