@extends('layouts.app-tw')

@section('title', 'Help')

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">Help &amp; Tutorials</h1>
		<p class="text-base md:text-lg text-muted-foreground">
			Everything you need to use {{ config('app.app_name') }} — browse, follow what you love, and contribute your own events. <br>
			New here? Start with the 60-second tour, then dig in!
		</p>
		<p class="text-xs text-muted-foreground mt-2">Last updated: May 2026</p>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-[16rem_1fr] gap-6">
		<!-- Sticky Table of Contents (lg+) -->
		<aside class="hidden lg:block">
			<nav class="sticky top-20 card-tw p-4 text-sm">
				<h2 class="font-semibold text-foreground mb-3 uppercase tracking-wide text-xs text-muted-foreground">On this page</h2>
				<ul class="space-y-1.5">
					<li><a href="#quick-start" class="text-muted-foreground hover:text-primary">60-second start</a></li>
					<li><a href="#glossary" class="text-muted-foreground hover:text-primary">Glossary</a></li>
					<li><a href="#how-to" class="text-muted-foreground hover:text-primary">How do I…?</a></li>
					<li><a href="#register" class="text-muted-foreground hover:text-primary">Register &amp; log in</a></li>
					<li><a href="#browse" class="text-muted-foreground hover:text-primary">Browse events</a></li>
					<li><a href="#follow" class="text-muted-foreground hover:text-primary">Follow &amp; get updates</a></li>
					<li><a href="#entities" class="text-muted-foreground hover:text-primary">Add an entity</a></li>
					<li><a href="#events" class="text-muted-foreground hover:text-primary">Add an event</a></li>
					<li><a href="#flyer" class="text-muted-foreground hover:text-primary">AI flyer upload</a></li>
					<li><a href="#series" class="text-muted-foreground hover:text-primary">Add a series</a></li>
					<li><a href="#photos" class="text-muted-foreground hover:text-primary">Photos &amp; media</a></li>
					<li><a href="#tags" class="text-muted-foreground hover:text-primary">Tags</a></li>
					<li><a href="#forum" class="text-muted-foreground hover:text-primary">Forum &amp; threads</a></li>
					<li><a href="#syndication" class="text-muted-foreground hover:text-primary">Social syndication</a></li>
					<li><a href="#videos" class="text-muted-foreground hover:text-primary">Video tutorials</a></li>
					<li><a href="#faq" class="text-muted-foreground hover:text-primary">FAQ</a></li>
					<li><a href="#developers" class="text-muted-foreground hover:text-primary">For developers</a></li>
					<li><a href="#quick-links" class="text-muted-foreground hover:text-primary">Quick links</a></li>
				</ul>
			</nav>
		</aside>

		<!-- Main content -->
		<div class="min-w-0 space-y-6">

			<!-- ========== 60-second start ========== -->
			<section id="quick-start" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-stopwatch text-primary text-xl"></i>
						</div>
						Getting started in 60 seconds
					</h2>
					<ol class="list-decimal list-inside space-y-2 text-muted-foreground">
						<li><strong class="text-foreground">Browse.</strong> Head to the <a href="{{ route('home') }}" class="text-primary hover:underline">homepage</a> or <a href="{{ route('calendar') }}" class="text-primary hover:underline">calendar</a> — no account needed. Click any event, venue, or artist to see more.</li>
						<li><strong class="text-foreground">Register.</strong> <a href="{{ route('register') }}" class="text-primary hover:underline">Create a free account</a> so you can follow, attend, and post.</li>
						<li><strong class="text-foreground">Follow your first thing.</strong> Find a venue, artist, or genre you care about and hit <em>Follow</em>. You'll start getting digest emails about upcoming related events.</li>
					</ol>
					<p class="text-sm text-muted-foreground italic">That's the basics. Everything below is the long version.</p>
				</div>
			</section>

			<!-- ========== Glossary ========== -->
			<section id="glossary" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-journal-text text-primary text-xl"></i>
						</div>
						Glossary
					</h2>
					<p class="text-muted-foreground">A few words you'll see a lot. Knowing the difference saves a lot of confusion:</p>
					<dl class="grid grid-cols-1 sm:grid-cols-[8rem_1fr] gap-x-4 gap-y-3 text-sm">
						<dt class="font-semibold text-foreground">Event</dt>
						<dd class="text-muted-foreground">A single show, party, opening, or screening that happens on a specific date.</dd>
						<dt class="font-semibold text-foreground">Series</dt>
						<dd class="text-muted-foreground">A recurring event (weekly, biweekly, monthly). Each occurrence shows up on the calendar automatically until someone creates a real event for that date with the specifics filled in.</dd>
						<dt class="font-semibold text-foreground">Entity</dt>
						<dd class="text-muted-foreground">Anyone or anything that participates in events: venues, promoters, bands, DJs, producers, artists, and shops.</dd>
						<dt class="font-semibold text-foreground">Tag</dt>
						<dd class="text-muted-foreground">A keyword (genre, vibe, format) shared across events, series, entities, and threads. Used for filtering and follows.</dd>
						<dt class="font-semibold text-foreground">Thread</dt>
						<dd class="text-muted-foreground">A forum post in the community forum. Can be standalone or attached to an event.</dd>
					</dl>
				</div>
			</section>

			<!-- ========== How do I…? (task-oriented) ========== -->
			<section id="how-to" class="card-tw">
				<div class="p-6 space-y-6">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-patch-question text-primary text-xl"></i>
						</div>
						How do I…?
					</h2>
					<p class="text-muted-foreground">
						Quick walkthroughs for the most common tasks. Not sure whether the thing you want to add is a venue, a band, or an event? Almost everyone and everything is an <a href="#entities" class="text-primary hover:underline">entity</a> — the show itself is the <a href="#events" class="text-primary hover:underline">event</a>.
					</p>

					{{-- How to add my band --}}
					<div class="rounded-lg border border-border p-4 space-y-2">
						<h3 class="text-lg font-semibold text-foreground flex items-center gap-2">
							<i class="bi bi-music-note-beamed text-primary"></i>
							How to add my band (or DJ / artist / producer)
						</h3>
						<p class="text-muted-foreground text-sm">A band is an <strong class="text-foreground">entity</strong>, not an event. Add it once and then link it to every show you play.</p>
						<ol class="list-decimal list-inside space-y-1 text-muted-foreground text-sm">
							<li><a href="{{ route('entities.create') }}" class="text-primary hover:underline">Add a new entity</a>.</li>
							<li>Enter your name and pick <em>Group</em> (band) or <em>Individual</em> (solo artist/DJ) as the entity type.</li>
							<li>Under <em>Roles</em>, choose all that apply — <em>Band</em>, <em>Artist</em>, <em>DJ</em>, <em>Producer</em>. You can pick more than one.</li>
							<li>Save, then open your new page to add a photo, links (Bandcamp, Instagram, SoundCloud), a description and tags.</li>
							<li>Now when you <a href="{{ route('events.create') }}" class="text-primary hover:underline">add an event</a>, find yourself under <em>Related Entities</em>.</li>
						</ol>
					</div>

					{{-- How to add a venue --}}
					<div class="rounded-lg border border-border p-4 space-y-2">
						<h3 class="text-lg font-semibold text-foreground flex items-center gap-2">
							<i class="bi bi-geo-alt text-primary"></i>
							How to add a venue
						</h3>
						<p class="text-muted-foreground text-sm">A venue is an <strong class="text-foreground">entity</strong> with the <em>Venue</em> role. It needs to exist before you can attach it to an event.</p>
						<ol class="list-decimal list-inside space-y-1 text-muted-foreground text-sm">
							<li><a href="{{ route('entities.create') }}" class="text-primary hover:underline">Add a new entity</a>, choose entity type <em>Space</em>, and select the <em>Venue</em> role.</li>
							<li>Or — the fast way — open the <a href="{{ route('events.create') }}" class="text-primary hover:underline">event form</a> and click <em>Add New Venue</em> right under the Venue dropdown. It's created and selected on the spot without leaving the form.</li>
							<li>Either way, you can flesh out the venue later from its page with an address/location, photo, and links.</li>
						</ol>
					</div>

					{{-- How to add my event --}}
					<div class="rounded-lg border border-border p-4 space-y-2">
						<h3 class="text-lg font-semibold text-foreground flex items-center gap-2">
							<i class="bi bi-calendar-plus text-primary"></i>
							How to add my event
						</h3>
						<ol class="list-decimal list-inside space-y-1 text-muted-foreground text-sm">
							<li><a href="{{ route('events.create') }}" class="text-primary hover:underline">Create an event</a>. Only <em>Name</em>, <em>Event Type</em>, and <em>Start At</em> are required.</li>
							<li>Pick the <em>Venue</em>. Not listed? Hit <em>Add New Venue</em> beside the field to create it inline.</li>
							<li>Add the <em>Promoter</em> and any performers under <em>Related Entities</em>. Missing one? Use the <em>Add New</em> link next to each field.</li>
							<li>Add a flyer/cover photo, tags, price and ticket link to make it more discoverable.</li>
						</ol>
						<p class="text-muted-foreground text-sm">See <a href="#events" class="text-primary hover:underline">Add an event</a> below for the full field-by-field rundown.</p>
					</div>
				</div>
			</section>

			<!-- ========== Register & log in ========== -->
			<section id="register" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-person-plus text-primary text-xl"></i>
						</div>
						Register &amp; log in
					</h2>
					<p class="text-muted-foreground">
						To follow, attend, post events, or use the forum, you need an account. <a href="{{ route('register') }}" class="text-primary hover:underline">Register here</a> — it takes about 20 seconds. Confirm the verification email and you're in.
					</p>
					<p class="text-muted-foreground">Once you're logged in, visit your <a href="{{ route('users.profile') }}" class="text-primary hover:underline">profile</a> to:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>Upload a profile photo</li>
						<li>Set your display name and bio</li>
						<li>Choose your email digest frequency</li>
						<li>View everything you've followed or marked as attending</li>
					</ul>
					<p class="text-muted-foreground text-sm">Forgot your password? Use the <em>Forgot password</em> link on the login page.</p>
					<div class="relative w-full" style="padding-bottom: 56.25%;">
						<iframe class="absolute top-0 left-0 w-full h-full rounded-lg" src="https://www.youtube.com/embed/RJB2VogM5tg" title="Registration and Login Tutorial" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
				</div>
			</section>

			<!-- ========== Browse events ========== -->
			<section id="browse" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-search text-primary text-xl"></i>
						</div>
						Browse events
					</h2>
					<p class="text-muted-foreground">There are three ways to look at what's coming up:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><a href="{{ url('/events') }}" class="text-primary hover:underline">Event Listings</a> — chronological list, the default view. Best for scanning the next few days.</li>
						<li><a href="{{ route('events.grid') }}" class="text-primary hover:underline">Event Grid</a> — visual grid of cover photos. Best for flyer-driven browsing.</li>
						<li><a href="{{ route('calendar') }}" class="text-primary hover:underline">Calendar</a> — month view with everything plotted. Best for trip planning or finding free nights.</li>
					</ul>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Filter anything.</strong> Every listing supports filters across tags (AND/OR), venues, related entities, event types, age, price, and date range. Use them aggressively — the database is large. Built a view worth sharing? Hit <em>Copy Filter URL</em> for a short link that opens the same filtered list, grid, or calendar for anyone.
					</p>
					<p class="text-muted-foreground"><strong class="text-foreground">Hidden gems on the calendar page:</strong></p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><em>Export iCal</em> subscribes your phone or Google Calendar to a live feed of filtered events.</li>
						<li><em>Export TXT</em> dumps a plain-text version, great for newsletters.</li>
						<li>URLs like <code class="px-1 py-0.5 rounded bg-muted text-xs">/events/by-date/2026/06/15</code> and <code class="px-1 py-0.5 rounded bg-muted text-xs">/events/upcoming/20260615</code> are stable and shareable — link to a specific date.</li>
					</ul>
				</div>
			</section>

			<!-- ========== Follow & get updates ========== -->
			<section id="follow" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-star text-primary text-xl"></i>
						</div>
						Follow &amp; get updates
					</h2>
					<p class="text-muted-foreground">Once you're logged in:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>Click the <i class="bi bi-star"></i> star next to any event to mark yourself attending. You'll get reminder emails as the date approaches.</li>
						<li>Click <em>Follow</em> on any entity, tag, or series to subscribe. New events related to anything you follow show up in your digest emails.</li>
					</ul>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Tag follows are underrated.</strong> If you only care about, say, drum-and-bass or noise rock, follow the tag and you'll get every event that gets it — no matter who posts it or where.
					</p>
					<p class="text-muted-foreground text-sm">Manage everything you follow from your <a href="{{ route('users.profile') }}" class="text-primary hover:underline">profile</a>. Mute or unfollow anytime.</p>
					<div class="relative w-full" style="padding-bottom: 56.25%;">
						<iframe class="absolute top-0 left-0 w-full h-full rounded-lg" src="https://www.youtube.com/embed/EMVDXGbwxvA" title="Following and Getting Updates Tutorial" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
				</div>
			</section>

			<!-- ========== Add an entity ========== -->
			<section id="entities" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-people text-primary text-xl"></i>
						</div>
						Add an entity
					</h2>
					<p class="text-muted-foreground">
						If you're a venue, promoter, band, DJ, producer, artist, or shop — <a href="{{ route('entities.create') }}" class="text-primary hover:underline">add yourself</a>. It's required before you can be linked to an event.
					</p>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Pick the right role(s).</strong> An entity can have more than one. A venue can also be a promoter; an artist can also be a producer. Choose all that apply from: <em>Venue, Artist, Band, DJ, Producer, Promoter, Shop</em>.
					</p>
					<p class="text-muted-foreground">Once your entity exists, open its page and you can add:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>A cover photo (recommended ~1200×1200, square or 4:5)</li>
						<li>Contacts (email, phone — kept private unless you mark them public)</li>
						<li>External links (website, Bandcamp, Instagram, Linktree, etc.)</li>
						<li>A description and tags</li>
						<li>Related entities (e.g. members of a band, residents of a club night)</li>
					</ul>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Hidden trick: Refresh Embeds.</strong> Entity and event pages auto-embed content from links you've added (Bandcamp player, Instagram posts, YouTube, SoundCloud). If embeds look stale after you update a link, hit <em>Refresh Embeds</em> from the actions menu.
					</p>
					<div class="relative w-full" style="padding-bottom: 56.25%;">
						<iframe class="absolute top-0 left-0 w-full h-full rounded-lg" src="https://www.youtube.com/embed/Qj4f2k2x3ho" title="Entities Tutorial" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
				</div>
			</section>

			<!-- ========== Add an event ========== -->
			<section id="events" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-calendar-event text-primary text-xl"></i>
						</div>
						Add an event
					</h2>
					<p class="text-muted-foreground">
						<a href="{{ route('events.create') }}" class="text-primary hover:underline">Create an event</a>. Only three fields are required — <em>Name</em>, <em>Event Type</em>, and <em>Start At</em> — but filling more out makes it dramatically more discoverable.
					</p>
					<p class="text-muted-foreground"><strong class="text-foreground">Fields worth using:</strong></p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><em>Cover photo</em> — flyers work great. Recommended ~1200×1500 (portrait) or 1200×1200.</li>
						<li><em>Door / Show times</em> — separate fields, both display on the event page.</li>
						<li><em>Price</em> and <em>Age restriction</em></li>
						<li><em>Ticket URL</em> — generates a <code class="px-1 py-0.5 rounded bg-muted text-xs">/go/evt-####</code> short link with click tracking, and shows up as a <em>Buy Tickets</em> CTA on cards.</li>
						<li><em>Venue</em> — the where. Pick from existing entities, or add the venue as an entity first.</li>
						<li><em>Related entities</em> — every promoter, band, DJ, opener. The more you list, the more follower feeds the event lands in.</li>
						<li><em>Tags</em> — pick from existing tags when possible. Keep it to ~6 max, and don't invent one-offs no one else will use.</li>
						<li><em>Description</em> — markdown is supported.</li>
					</ul>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Event types</strong> define what kind of show it is — Concert, Club Night, House Show, Rave, Renegade, Festival, Benefit, Activism, Pop-up, Comedy, Workshop, Open Mic, Karaoke, Film Screening, Art Opening, Live Stream, Radio Show. Pick the one that matches; it affects how the event is filtered and how it gets cross-posted.
					</p>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Promoting an instance of a series.</strong> If your event is this week's recurring night, open the series page first and create a new event from there — venue, tags, and related entities get pre-filled.
					</p>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Editing or canceling.</strong> You can edit anything you created from the event page. To cancel, edit and mark it <em>canceled</em> rather than deleting — that way people who marked themselves attending get a notification.
					</p>
					<div class="relative w-full" style="padding-bottom: 56.25%;">
						<iframe class="absolute top-0 left-0 w-full h-full rounded-lg" src="https://www.youtube.com/embed/dtqjrb1SiYw" title="Events Tutorial" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
				</div>
			</section>

			<!-- ========== AI flyer upload ========== -->
			<section id="flyer" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-magic text-primary text-xl"></i>
						</div>
						Create an event from a flyer
					</h2>
					<p class="text-muted-foreground">
						Have an event flyer image? Use the <strong class="text-foreground">Create from Flyer</strong> option on the event create page. We extract the date, venue, lineup, and ticket info and pre-fill the form — you just review and save.
					</p>
					<p class="text-muted-foreground text-sm">
						Always double-check the parsed fields. The algorithm is decent but not perfect, especially with stylized fonts and overlapping text.
					</p>
				</div>
			</section>

			<!-- ========== Add a series ========== -->
			<section id="series" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-collection text-primary text-xl"></i>
						</div>
						Add a series
					</h2>
					<p class="text-muted-foreground">
						<a href="{{ route('series.create') }}" class="text-primary hover:underline">Use a series</a> for anything recurring — weekly residencies, monthly parties, biweekly jams. Set the recurrence (e.g. "Every Tuesday at 10 PM") and {{ config('app.app_name') }} automatically projects future instances onto the calendar in light blue as <em>Next Edition</em>.
					</p>
					<p class="text-muted-foreground">
						When the specifics are locked in (lineup, cover, etc.), create the concrete event from the series page. The new event inherits the series' venue, tags, and related entities.
					</p>
					<p class="text-muted-foreground text-sm">
						<strong class="text-foreground">Good fits for a series:</strong> weekly DJ nights, monthly metal shows, biweekly jams, recurring open mics.
						<strong class="text-foreground">Bad fits:</strong> a one-off festival, a tour stop.
					</p>
				</div>
			</section>

			<!-- ========== Photos & media ========== -->
			<section id="photos" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-image text-primary text-xl"></i>
						</div>
						Photos &amp; media
					</h2>
					<p class="text-muted-foreground">Cover photos for events and entities are stored on our media service. A few guidelines:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>Formats: JPG, PNG, WebP, GIF</li>
						<li>Recommended dimensions: ~1200×1200 (square) for entities, ~1200×1500 (portrait) for event flyers</li>
						<li>Anything significantly smaller will look soft on retina screens</li>
					</ul>
					<p class="text-muted-foreground">
						<strong class="text-foreground">Embedded media.</strong> Paste a YouTube, SoundCloud, Spotify, Bandcamp, Mixcloud, Vimeo, or Instagram link into an event/entity/series description (or as an external link on an entity) and we render the rich embed automatically. No special syntax required.
					</p>
				</div>
			</section>

			<!-- ========== Tags ========== -->
			<section id="tags" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-tags text-primary text-xl"></i>
						</div>
						Tags
					</h2>
					<p class="text-muted-foreground">
						<a href="{{ url('/tags') }}" class="text-primary hover:underline">Tags</a> work the same across events, series, entities, and forum threads. They're how people find each other's stuff.
					</p>
					<p class="text-muted-foreground"><strong class="text-foreground">Etiquette:</strong></p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>Use existing tags whenever possible — type the first few letters and pick from the dropdown.</li>
						<li>Cap it at ~6. More than that dilutes the signal.</li>
						<li>No one-offs. "My-cousin's-birthday" isn't a tag; the event title is.</li>
						<li>Tags can be genres (darkwave, dubstep), formats (live-music, dj, vinyl), vibes (weird, cozy), or contexts (benefit, pride, family-friendly).</li>
					</ul>
				</div>
			</section>

			<!-- ========== Forum ========== -->
			<section id="forum" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-chat-square-text text-primary text-xl"></i>
						</div>
						Forum &amp; threads
					</h2>
					<p class="text-muted-foreground">
						The <a href="{{ url('/forums') }}" class="text-primary hover:underline">forum</a> is for everything that's not an event listing — show announcements with extra context, scene discussion, gear questions, room rentals, after-show reviews. <a href="{{ route('threads.create') }}" class="text-primary hover:underline">Start a thread</a> or reply to existing ones.
					</p>
					<p class="text-muted-foreground">
						Threads can be <strong class="text-foreground">attached to an event</strong> so the conversation lives next to the listing. Tag your thread the same way you'd tag an event.
					</p>
					<p class="text-muted-foreground text-sm">Keep it on-topic, no spam, no harassment. Reports go to the moderators.</p>
				</div>
			</section>

			<!-- ========== Social syndication ========== -->
			<section id="syndication" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-megaphone text-primary text-xl"></i>
						</div>
						Social syndication
					</h2>
					<p class="text-muted-foreground">Events you post don't just live on {{ config('app.app_name') }}. They also:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>Get included in the weekly digest emails sent to subscribers</li>
						<li>Are eligible for cross-posting to our Instagram</li>
					</ul>
					<p class="text-muted-foreground text-sm">The more complete your event listing (cover photo especially), the better it presents downstream.</p>
				</div>
			</section>

			<!-- ========== Video tutorials ========== -->
			<section id="videos" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-play-circle text-primary text-xl"></i>
						</div>
						Video tutorials
					</h2>
					<h3 class="text-lg font-semibold text-foreground">Breakdown of the Homepage</h3>
					<div class="relative w-full" style="padding-bottom: 56.25%;">
						<iframe class="absolute top-0 left-0 w-full h-full rounded-lg" src="https://www.youtube.com/embed/TtV17-d1GNU" title="Homepage Breakdown Tutorial" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
					</div>
					<p class="text-xs text-muted-foreground italic">Older walkthroughs are embedded in the sections above. Some predate the latest UI refresh — refer to the prose for the current behavior if anything looks off.</p>
				</div>
			</section>

			<!-- ========== FAQ ========== -->
			<section id="faq" class="card-tw" x-data="{ open: null }">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-question-circle text-primary text-xl"></i>
						</div>
						Frequently asked questions
					</h2>
					<div class="divide-y divide-border">
						@php
							$faqs = [
								['How do I claim an existing entity?', 'Email geoff.maddock at gmail.com from an address that proves you\'re affiliated. Claimed entities can be edited by their owner.'],
								['Why was my event edited?', 'Moderators occasionally fix typos, add missing venues, or clean up tags. If you disagree with an edit, post in the forum or email.'],
								['Can the same event have multiple venues?', 'No — pick the primary venue and add the others as related entities.'],
								['A series instance isn\'t showing up on the calendar — why?', 'Series occurrences appear in light blue until someone creates a concrete event for that date. If a date is missing entirely, edit the series and check the recurrence rule.'],
								['How do I delete an event?', 'Edit it and mark it canceled. True deletes require contacting a moderator.'],
								['How do I report a duplicate event?', 'Open the duplicate, scroll to the bottom, and reply in the attached thread (or start one). A moderator will merge.'],
								['Do I need an account to browse?', 'No — browsing events, entities, series, and the calendar is fully public. You only need to register to post content, follow things, or mark yourself attending.'],
								['Is there a mobile app?', 'No native app, but the site is fully responsive and works well as a phone bookmark or PWA.'],
							];
						@endphp
						@foreach ($faqs as $i => $faq)
							<div>
								<button
									type="button"
									@click="open === {{ $i }} ? open = null : open = {{ $i }}"
									class="w-full flex items-center justify-between text-left py-4 text-foreground hover:text-primary transition-colors">
									<span class="font-medium pr-4">{{ $faq[0] }}</span>
									<i class="bi" :class="open === {{ $i }} ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
								</button>
								<div x-show="open === {{ $i }}" x-collapse class="pb-4 text-muted-foreground">
									{{ $faq[1] }}
								</div>
							</div>
						@endforeach
					</div>
				</div>
			</section>

			<!-- ========== For developers ========== -->
			<section id="developers" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-braces text-primary text-xl"></i>
						</div>
						For developers
					</h2>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>
							<strong class="text-foreground">REST API:</strong>
							<a href="{{ url('/api/docs') }}" class="text-primary hover:underline">/api/docs</a>
							— read and write events, entities, series, tags, photos, contacts, links, and more. Authenticate with a Sanctum token (<code class="px-1 py-0.5 rounded bg-muted text-xs">POST /api/auth/token</code>) or HTTP Basic auth.
						</li>
						<li>
							<strong class="text-foreground">Source:</strong>
							<a href="https://github.com/geoff-maddock/events-tracker" class="text-primary hover:underline" target="_blank" rel="noopener">github.com/geoff-maddock/events-tracker</a>
							— Laravel + PHP + MySQL + Vue. PRs welcome.
						</li>
					</ul>
				</div>
			</section>

			<!-- ========== Quick links ========== -->
			<section id="quick-links" class="card-tw">
				<div class="p-6">
					<h2 class="text-2xl font-semibold text-foreground mb-4">Quick links</h2>
					<div class="grid grid-cols-2 md:grid-cols-3 gap-3">
						@guest
						<a href="{{ route('register') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-person-plus mr-2"></i> Register
						</a>
						<a href="{{ route('login') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-box-arrow-in-right mr-2"></i> Login
						</a>
						@endguest
						<a href="{{ route('calendar') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-calendar3 mr-2"></i> Calendar
						</a>
						<a href="{{ route('events.grid') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-grid mr-2"></i> Event Grid
						</a>
						<a href="{{ url('/tags') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-tags mr-2"></i> Tags
						</a>
						<a href="{{ url('/forums') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-chat-square-text mr-2"></i> Forum
						</a>
						<a href="{{ route('events.create') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-plus-lg mr-2"></i> Add Event
						</a>
						<a href="{{ route('series.create') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-plus-lg mr-2"></i> Add Series
						</a>
						<a href="{{ route('entities.create') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-plus-lg mr-2"></i> Add Entity
						</a>
						<a href="{{ route('threads.create') }}" class="inline-flex items-center px-3 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors text-sm">
							<i class="bi bi-plus-lg mr-2"></i> Add Thread
						</a>
					</div>
				</div>
			</section>

			<!-- Still stuck -->
			<div class="card-tw p-6 text-center text-muted-foreground">
				<p>Still stuck? Email <a href="mailto:geoff.maddock@gmail.com" class="text-primary hover:underline">geoff.maddock@gmail.com</a> or post in the <a href="{{ url('/forums') }}" class="text-primary hover:underline">forum</a>. Suggestions for this help page are welcome.</p>
			</div>
		</div>
	</div>
</div>

@stop

@section('footer')
@stop
