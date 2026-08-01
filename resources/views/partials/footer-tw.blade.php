<footer class="mt-12 border-t border-border py-6 text-sm text-muted-foreground">
	<div class="flex flex-wrap items-center gap-x-2 gap-y-1">
		<span class="font-semibold text-foreground">{{ config('app.app_name') }}</span>
		@foreach (\App\Enums\EventTimeWindow::cases() as $window)
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url($window->path()) }}" class="hover:text-primary transition-colors">{{ $window->label() }}</a>
		@endforeach
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url('/events') }}" class="hover:text-primary transition-colors">Events</a>
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url('/entities') }}" class="hover:text-primary transition-colors">Entities</a>
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url('/series') }}" class="hover:text-primary transition-colors">Series</a>
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url('/tags') }}" class="hover:text-primary transition-colors">Tags</a>
	</div>
	<div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
		<span class="font-semibold text-foreground">Scenes</span>
		@foreach (config('scenes', []) as $sceneSlug => $scene)
		<span aria-hidden="true">&middot;</span>
		<a href="{{ url('/scenes/'.$sceneSlug) }}" class="hover:text-primary transition-colors">{{ $scene['name'] }}</a>
		@endforeach
	</div>
</footer>
