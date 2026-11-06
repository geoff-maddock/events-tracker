<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	@unless ($meta['indexable'])
	<meta name="robots" content="noindex, follow">
	@endunless
	<link rel="canonical" href="{{ $meta['url'] }}">
	<meta name="theme-color" content="#0f172a"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $meta['title'] }}</title>
	<meta name="description" content="{{ $meta['description'] }}">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="{{ config('app.app_name') }}">
	<meta property="og:title" content="{{ $meta['title'] }}">
	<meta property="og:description" content="{{ $meta['description'] }}">
	<meta property="og:url" content="{{ $meta['url'] }}">
	<meta property="og:image" content="{{ $meta['image'] }}">
	<meta name="twitter:card" content="summary_large_image">
	<script type="application/ld+json">
{!! json_encode($meta['jsonLd'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
	</script>

	<script>
		(function() {
			var theme = '{{ config("app.default_theme") }}';
			try { theme = localStorage.getItem('theme') || theme; } catch (e) { /* storage blocked */ }
			document.documentElement.classList.add(theme);
		})();
	</script>

	@vite(['resources/css/tailwind.css'])
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">

	{{-- state styles toggled from public/js/player.js: plain CSS, since Tailwind doesn't scan public/js --}}
	<style>
		.player-queue-item[aria-current="true"] { background: hsl(var(--accent)); font-weight: 600; }
		.player-queue-item[data-state="unavailable"] { opacity: .45; text-decoration: line-through; }
		.player-queue-item[data-state="loading"] .player-queue-spinner { display: inline-block; }
		.player-queue-spinner { display: none; }
		.player-star[aria-pressed="true"] { color: #f59e0b; }
		.player-frame iframe { display: block; width: 100%; border: 0; }
		#player button:disabled { opacity: .35; cursor: not-allowed; }
		[hidden] { display: none !important; }
	</style>
</head>
<body class="bg-background text-foreground min-h-screen">
<div id="player"
	class="flex flex-col h-screen max-w-3xl mx-auto"
	data-queue-url="{{ route('player.queue') }}"
	data-signed-in="{{ auth()->check() ? '1' : '0' }}"
	data-login-url="{{ route('login') }}"
	data-follow-url="{{ url('entities/__ID__/follow') }}"
	data-unfollow-url="{{ url('entities/__ID__/unfollow') }}">

	<header class="px-4 py-3 border-b border-border min-w-0">
		<h1 class="text-lg font-semibold flex items-center gap-2">
			<i class="bi bi-music-note-beamed" aria-hidden="true"></i>
			<span class="truncate">{{ config('app.app_name') }} Player</span>
		</h1>
		<p id="player-source" class="text-sm text-muted-foreground truncate"></p>
	</header>

	<section class="px-4 py-4 space-y-3 border-b border-border" aria-labelledby="player-now-title">
		<div class="flex items-start justify-between gap-3">
			<div class="min-w-0">
				<h2 id="player-now-title" class="text-xl font-semibold truncate">
					<a id="player-entity" href="#" class="hover:underline" data-opener-link>Loading…</a>
				</h2>
				<p id="player-track" class="text-sm text-muted-foreground truncate"></p>
				<p id="player-event" class="text-sm truncate">
					<a href="#" class="hover:underline" data-opener-link></a>
				</p>
			</div>
			<button type="button" id="player-star" class="player-star text-2xl p-2 rounded-md hover:bg-accent shrink-0"
				aria-pressed="false" aria-label="Follow this artist" title="Follow this artist">
				<i class="bi bi-star" aria-hidden="true"></i>
			</button>
		</div>

		<div id="player-frame" class="player-frame rounded-md overflow-hidden"></div>

		<div class="flex items-center justify-center gap-2" role="group" aria-label="Player controls">
			<button type="button" id="player-prev" class="p-3 rounded-full hover:bg-accent text-2xl" aria-label="Previous artist" title="Previous artist (←)">
				<i class="bi bi-skip-start-fill" aria-hidden="true"></i>
			</button>
			<button type="button" id="player-next" class="p-3 rounded-full hover:bg-accent text-2xl" aria-label="Next artist" title="Next artist (→)">
				<i class="bi bi-skip-end-fill" aria-hidden="true"></i>
			</button>
		</div>

		<p id="player-status" class="text-sm text-muted-foreground text-center min-h-5" role="status" aria-live="polite"></p>
	</section>

	<section class="flex-1 overflow-y-auto" aria-labelledby="player-queue-title">
		<h2 id="player-queue-title" class="px-4 pt-3 pb-1 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Up next</h2>
		<ol id="player-queue"></ol>
		{{-- scrolled into view: player.js fetches the next page of the queue --}}
		<p id="player-more" class="px-4 py-3 text-sm text-muted-foreground" aria-live="polite" hidden>Loading more…</p>
		<div id="player-more-sentinel" class="h-4" aria-hidden="true"></div>
		<p id="player-empty" class="px-4 py-6 text-muted-foreground" hidden>
			No artists with SoundCloud or Bandcamp links were found for this list.
		</p>
	</section>
</div>

<template id="player-queue-item">
	<li>
		<button type="button" class="player-queue-item w-full text-left px-4 py-2 flex items-baseline gap-3 hover:bg-accent">
			<span class="player-queue-number text-sm text-muted-foreground w-6 shrink-0 tabular-nums"></span>
			<span class="min-w-0 flex-1">
				<span class="player-queue-name block truncate"></span>
				<span class="player-queue-event block text-sm text-muted-foreground truncate"></span>
			</span>
			<i class="player-queue-spinner bi bi-hourglass-split text-sm" aria-hidden="true"></i>
			<span class="player-queue-providers text-sm text-muted-foreground shrink-0"></span>
		</button>
	</li>
</template>

<script src="https://w.soundcloud.com/player/api.js"></script>
<script src="{{ asset('/js/player.js') }}?v={{ @filemtime(public_path('js/player.js')) }}"></script>
</body>
</html>
