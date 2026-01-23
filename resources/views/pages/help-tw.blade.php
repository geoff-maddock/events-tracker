@extends('layouts.app-tw')

@section('title', 'Help')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-8">
		<h1 class="text-4xl font-bold text-primary mb-4">Help & Tutorials</h1>
		<p class="text-lg text-muted-foreground">Learn how to use {{ config('app.app_name') }} to discover and share events</p>
	</div>

	<!-- Register / Login Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-person-plus text-primary text-xl"></i>
				</div>
				Register / Login
			</h2>
			<p class="text-muted-foreground">
				To add anything, you must first register (simple process), and then log in.
			</p>
			<div class="relative w-full" style="padding-bottom: 56.25%;">
				<iframe
					class="absolute top-0 left-0 w-full h-full rounded-lg"
					src="https://www.youtube.com/embed/RJB2VogM5tg"
					title="Registration and Login Tutorial"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen>
				</iframe>
			</div>
		</div>
	</div>

	<!-- Entities Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-people text-primary text-xl"></i>
				</div>
				Entities
			</h2>
			<p class="text-muted-foreground">
				Producers, DJs, Promoters, Venues and Artists - add yourself as an <strong class="text-foreground">entity</strong>. From there, if you view your entity, you can add images, links, contacts, and link yourself to events.
			</p>
			<div class="relative w-full" style="padding-bottom: 56.25%;">
				<iframe
					class="absolute top-0 left-0 w-full h-full rounded-lg"
					src="https://www.youtube.com/embed/Qj4f2k2x3ho"
					title="Entities Tutorial"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen>
				</iframe>
			</div>
		</div>
	</div>

	<!-- Event Series Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-collection text-primary text-xl"></i>
				</div>
				Event Series
			</h2>
			<p class="text-muted-foreground">
				Add a <strong class="text-foreground">series</strong> under the events menu for recurring events. These events will automatically show up on the calendar on future dates based on the recurrence frequency you specify.
			</p>
		</div>
	</div>

	<!-- Events Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-calendar-event text-primary text-xl"></i>
				</div>
				Events
			</h2>
			<p class="text-muted-foreground">
				For one-offs, or instances of a series, add individual <strong class="text-foreground">events</strong>. It's best to add as much data and fill out as many fields as possible, but the only required fields are <em>Name</em>, <em>Event Type</em>, and <em>Start At</em>.
			</p>
			<p class="text-muted-foreground">
				Adding related entities and tags will help users discover your events. But we would recommend not adding more than six tags, or excessively creating new tags that other users won't use.
			</p>
			<div class="relative w-full" style="padding-bottom: 56.25%;">
				<iframe
					class="absolute top-0 left-0 w-full h-full rounded-lg"
					src="https://www.youtube.com/embed/dtqjrb1SiYw"
					title="Events Tutorial"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen>
				</iframe>
			</div>
		</div>
	</div>

	<!-- Calendar Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-calendar3 text-primary text-xl"></i>
				</div>
				Calendar
			</h2>
			<div class="space-y-2">
				<p class="text-muted-foreground">
					Any event that has been added will appear on the calendar automatically in blue.
				</p>
				<p class="text-muted-foreground">
					For each series, the next instance that has not yet been added will be displayed on the calendar in light blue.
				</p>
			</div>
		</div>
	</div>

	<!-- Following & Getting Updates Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-star text-primary text-xl"></i>
				</div>
				Following & Getting Updates
			</h2>
			<p class="text-muted-foreground">
				In order to mark yourself as attending for events, follow entities or keywords, you first have to register and log in. Once you're logged in, you'll see a "star" icon next to any event, you can click it to mark yourself attending. You'll be reminded of any events you are attending as they get closer.
			</p>
			<p class="text-muted-foreground">
				For entities or keywords, you can click the "Follow" icon and you will receive future updates about events related to those entities or keywords.
			</p>
			<div class="relative w-full" style="padding-bottom: 56.25%;">
				<iframe
					class="absolute top-0 left-0 w-full h-full rounded-lg"
					src="https://www.youtube.com/embed/EMVDXGbwxvA"
					title="Following and Getting Updates Tutorial"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen>
				</iframe>
			</div>
		</div>
	</div>

	<!-- Video Tutorials Section -->
	<div class="card-tw mb-6">
		<div class="p-6 space-y-4">
			<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
				<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center">
					<i class="bi bi-play-circle text-primary text-xl"></i>
				</div>
				Video Tutorials
			</h2>
			<div class="space-y-4">
				<h3 class="text-lg font-semibold text-foreground">Breakdown of the Homepage</h3>
				<div class="relative w-full" style="padding-bottom: 56.25%;">
					<iframe
						class="absolute top-0 left-0 w-full h-full rounded-lg"
						src="https://www.youtube.com/embed/TtV17-d1GNU"
						title="Homepage Breakdown Tutorial"
						frameborder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
						allowfullscreen>
					</iframe>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Links Section -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<h2 class="text-2xl font-semibold text-foreground mb-4">Quick Links</h2>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
				@if (Auth::guest())
				<a href="{{ route('register') }}" class="inline-flex items-center px-4 py-3 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-person-plus mr-2"></i>
					Register an Account
				</a>
				@endif
				<a href="{{ route('events.create') }}" class="inline-flex items-center px-4 py-3 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-plus-lg mr-2"></i>
					Add an Event
				</a>
				<a href="{{ route('entities.create') }}" class="inline-flex items-center px-4 py-3 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-plus-lg mr-2"></i>
					Add an Entity
				</a>
				<a href="{{ route('series.create') }}" class="inline-flex items-center px-4 py-3 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-plus-lg mr-2"></i>
					Add a Series
				</a>
			</div>
		</div>
	</div>
</div>

@stop

@section('footer')
@stop
