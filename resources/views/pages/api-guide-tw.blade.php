@extends('layouts.app-tw')

@section('title', 'Using the API')

@section('description', 'A plain-language guide to the ' . config('app.app_name') . ' REST API: your site account is your API account, how to authenticate, filter and page through lists, create records, and where to find the full endpoint reference.')

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">Using the API</h1>
		<p class="text-base md:text-lg text-muted-foreground">
			Everything you can see and do on {{ config('app.app_name') }} is also available as JSON, so you can build scripts, importers, bots, and apps on top of it. <br>
			This page is the plain-language introduction. The full endpoint reference lives at <a href="{{ url('/api/docs') }}" class="text-primary hover:underline">/api/docs</a>.
		</p>
		<p class="text-xs text-muted-foreground mt-2">Last updated: September 2026 · <a href="{{ url('/help') }}" class="text-primary hover:underline">Back to Help</a></p>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-[16rem_1fr] gap-6">
		<!-- Sticky Table of Contents (lg+) -->
		<aside class="hidden lg:block">
			<nav class="sticky top-20 card-tw p-4 text-sm">
				<h2 class="font-semibold text-foreground mb-3 uppercase tracking-wide text-xs text-muted-foreground">On this page</h2>
				<ul class="space-y-1.5">
					<li><a href="#what" class="text-muted-foreground hover:text-primary">What the API is</a></li>
					<li><a href="#account" class="text-muted-foreground hover:text-primary">Your account is your API account</a></li>
					<li><a href="#authentication" class="text-muted-foreground hover:text-primary">Authentication</a></li>
					<li><a href="#reading" class="text-muted-foreground hover:text-primary">Reading data</a></li>
					<li><a href="#writing" class="text-muted-foreground hover:text-primary">Writing data</a></li>
					<li><a href="#limits" class="text-muted-foreground hover:text-primary">Rate limits &amp; etiquette</a></li>
					<li><a href="#next" class="text-muted-foreground hover:text-primary">Where to go next</a></li>
				</ul>
			</nav>
		</aside>

		<!-- Main content -->
		<div class="min-w-0 space-y-6">

			<!-- ========== What the API is ========== -->
			<section id="what" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-braces text-primary text-xl"></i>
						</div>
						What the API is
					</h2>
					<p class="text-muted-foreground">
						A JSON REST API over the same data the site shows: events, entities (venues, artists, promoters, DJs…), series, tags, photos, links, contacts, forum threads, posts, blogs, and more.
					</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><strong class="text-foreground">Base URL:</strong> <code class="px-1 py-0.5 rounded bg-muted text-xs">{{ url('/api') }}</code></li>
						<li><strong class="text-foreground">Format:</strong> requests and responses are JSON. Send <code class="px-1 py-0.5 rounded bg-muted text-xs">Accept: application/json</code> on every request so errors come back as JSON too.</li>
						<li><strong class="text-foreground">Reference:</strong> every endpoint, parameter, and response shape is listed at <a href="{{ url('/api/docs') }}" class="text-primary hover:underline">/api/docs</a>.</li>
					</ul>
				</div>
			</section>

			<!-- ========== Account ========== -->
			<section id="account" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-person-badge text-primary text-xl"></i>
						</div>
						Your account is your API account
					</h2>
					<p class="text-muted-foreground">
						There is no separate developer signup. If you have a site account, you already have API access: <a href="{{ route('register') }}" class="text-primary hover:underline">register</a> if you don't.
					</p>
					<p class="text-muted-foreground">
						API calls run as <em>your</em> user, with exactly the same permissions and visibility rules as the website:
					</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li>You see public content, plus any private content you'd be able to see when logged in.</li>
						<li>You can edit or delete only what you own, unless your account has admin rights.</li>
						<li>Anything you create is attributed to your account.</li>
					</ul>
					<p class="text-muted-foreground">
						In short: if you can't do it in the browser, you can't do it through the API either.
					</p>
				</div>
			</section>

			<!-- ========== Authentication ========== -->
			<section id="authentication" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-key text-primary text-xl"></i>
						</div>
						Authentication
					</h2>
					<p class="text-muted-foreground">Most endpoints require you to identify yourself. There are two ways to do it.</p>

					<h3 class="text-lg font-semibold text-foreground">Option 1: HTTP Basic auth</h3>
					<p class="text-muted-foreground">
						Send your site email and password on each request (<code class="px-1 py-0.5 rounded bg-muted text-xs">Authorization: Basic base64(email:password)</code>). It's the simplest option for quick scripts. Most tools handle the encoding for you:
					</p>
					<pre class="bg-muted rounded-lg p-4 text-xs overflow-x-auto"><code>curl -u 'you&#64;example.com:your-password' \
  -H 'Accept: application/json' \
  {{ url('/api/events') }}</code></pre>

					<h3 class="text-lg font-semibold text-foreground">Option 2: Bearer token</h3>
					<p class="text-muted-foreground">
						If you'd rather not store your password in a script, exchange it once for a token and send that instead.
					</p>
					<ol class="list-decimal list-inside space-y-2 text-muted-foreground">
						<li>
							Create a token with basic auth and a <code class="px-1 py-0.5 rounded bg-muted text-xs">token_name</code> of your choice:
							<pre class="bg-muted rounded-lg p-4 text-xs overflow-x-auto mt-2"><code>curl -u 'you&#64;example.com:your-password' \
  -H 'Accept: application/json' \
  -d 'token_name=my-importer' \
  {{ url('/api/tokens/create') }}

# =&gt; {"token": "12|AbCdEf..."}</code></pre>
							The token is shown only once, so save it somewhere safe.
						</li>
						<li>
							Send it as a bearer token:
							<pre class="bg-muted rounded-lg p-4 text-xs overflow-x-auto mt-2"><code>curl -H 'Authorization: Bearer 12|AbCdEf...' \
  -H 'Accept: application/json' \
  {{ url('/api/auth/me') }}</code></pre>
							<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/auth/me</code> returns the user the token belongs to, which is a handy way to check it works.
						</li>
						<li>
							To revoke tokens, call <code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/tokens/invalidate</code> with a token. It deletes <strong class="text-foreground">all</strong> of your tokens, not just that one.
						</li>
					</ol>
					<p class="text-sm text-muted-foreground italic">
						A token grants the same access as your account. Scoped or read-only tokens aren't available, so treat a token like your password.
					</p>
				</div>
			</section>

			<!-- ========== Reading ========== -->
			<section id="reading" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-search text-primary text-xl"></i>
						</div>
						Reading data
					</h2>
					<p class="text-muted-foreground">
						Each kind of record has a list endpoint and a single-record endpoint, for example
						<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/events</code>,
						<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/events/{id}</code>,
						<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/entities</code>,
						<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/series</code>, and
						<code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/tags</code>.
					</p>

					<h3 class="text-lg font-semibold text-foreground">Paging</h3>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">page</code>: which page to return, starting at 1.</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">limit</code>: results per page, up to 1000.</li>
						<li>List responses include <code class="px-1 py-0.5 rounded bg-muted text-xs">data</code> plus <code class="px-1 py-0.5 rounded bg-muted text-xs">current_page</code>, <code class="px-1 py-0.5 rounded bg-muted text-xs">last_page</code>, <code class="px-1 py-0.5 rounded bg-muted text-xs">total</code>, and <code class="px-1 py-0.5 rounded bg-muted text-xs">next_page_url</code>. Keep following <code class="px-1 py-0.5 rounded bg-muted text-xs">next_page_url</code> until it's <code class="px-1 py-0.5 rounded bg-muted text-xs">null</code>.</li>
					</ul>

					<h3 class="text-lg font-semibold text-foreground">Sorting</h3>
					<p class="text-muted-foreground">
						<code class="px-1 py-0.5 rounded bg-muted text-xs">sort</code> takes a column, for example <code class="px-1 py-0.5 rounded bg-muted text-xs">events.start_at</code> or <code class="px-1 py-0.5 rounded bg-muted text-xs">entities.name</code>. <code class="px-1 py-0.5 rounded bg-muted text-xs">direction</code> is <code class="px-1 py-0.5 rounded bg-muted text-xs">asc</code> or <code class="px-1 py-0.5 rounded bg-muted text-xs">desc</code>.
					</p>

					<h3 class="text-lg font-semibold text-foreground">Filtering</h3>
					<p class="text-muted-foreground">Filters use <code class="px-1 py-0.5 rounded bg-muted text-xs">filters[field]=value</code>. Some useful ones:</p>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[name]=…</code>: name contains the text.</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[tag]=techno,house</code>: has <em>any</em> of these tags.</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[tag_all]=techno,house</code>: has <em>all</em> of these tags.</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[venue]=…</code>: events at a venue, matched by its slug (events only).</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[start_at][start]=2026-10-01&amp;filters[start_at][end]=2026-10-31</code>: events starting in a date range.</li>
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">filters[entity_type]=venue</code>: entities of one type, matched by the type's slug (entities only).</li>
					</ul>
					<p class="text-muted-foreground">
						See <a href="{{ url('/api/docs') }}" class="text-primary hover:underline">/api/docs</a> for the filters each endpoint supports.
					</p>
					<pre class="bg-muted rounded-lg p-4 text-xs overflow-x-auto"><code># Techno events in October 2026, soonest first, 50 per page
curl -G -u 'you&#64;example.com:your-password' \
  -H 'Accept: application/json' \
  --data-urlencode 'filters[tag]=techno' \
  --data-urlencode 'filters[start_at][start]=2026-10-01' \
  --data-urlencode 'filters[start_at][end]=2026-10-31' \
  -d 'sort=events.start_at' -d 'direction=asc' -d 'limit=50' \
  {{ url('/api/events') }}</code></pre>
				</div>
			</section>

			<!-- ========== Writing ========== -->
			<section id="writing" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-pencil-square text-primary text-xl"></i>
						</div>
						Writing data
					</h2>
					<p class="text-muted-foreground">
						Create records with <code class="px-1 py-0.5 rounded bg-muted text-xs">POST</code> to the list endpoint (for example <code class="px-1 py-0.5 rounded bg-muted text-xs">POST /api/events</code>). Replace a record with <code class="px-1 py-0.5 rounded bg-muted text-xs">PUT /api/events/{id}</code>, or change only some fields with <code class="px-1 py-0.5 rounded bg-muted text-xs">PATCH /api/events/{id}</code>. The same rules apply as on the site's forms: required fields, validation messages, and ownership checks. Validation errors come back as <code class="px-1 py-0.5 rounded bg-muted text-xs">422</code> with a message for each field.
					</p>
					<pre class="bg-muted rounded-lg p-4 text-xs overflow-x-auto"><code>curl -u 'you&#64;example.com:your-password' \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{
        "name": "Basement Session",
        "slug": "basement-session",
        "start_at": "2026-10-17 21:00:00",
        "event_type_id": 1,
        "visibility_id": 3,
        "tag_list": ["techno", 42]
      }' \
  {{ url('/api/events') }}</code></pre>
					<p class="text-sm text-muted-foreground">
						Look up valid ids with <code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/event-types</code>, <code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/visibilities</code>, <code class="px-1 py-0.5 rounded bg-muted text-xs">GET /api/entity-types</code>, and so on.
					</p>

					<h3 class="text-lg font-semibold text-foreground">Tags</h3>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><code class="px-1 py-0.5 rounded bg-muted text-xs">tag_list</code> accepts existing tag ids, tag names, or a mix of both.</li>
						<li>Names match existing tags regardless of capitalization, so <code class="px-1 py-0.5 rounded bg-muted text-xs">"diy"</code> and <code class="px-1 py-0.5 rounded bg-muted text-xs">"DIY"</code> are the same tag. A new tag is created only when nothing matches.</li>
						<li>On <code class="px-1 py-0.5 rounded bg-muted text-xs">PUT</code>, leaving out <code class="px-1 py-0.5 rounded bg-muted text-xs">tag_list</code> removes all tags. On <code class="px-1 py-0.5 rounded bg-muted text-xs">PATCH</code>, tags change only when you send <code class="px-1 py-0.5 rounded bg-muted text-xs">tag_list</code>.</li>
					</ul>

					<h3 class="text-lg font-semibold text-foreground">Photos</h3>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><strong class="text-foreground">Upload:</strong> <code class="px-1 py-0.5 rounded bg-muted text-xs">POST /api/events/{id}/photos</code> with a multipart <code class="px-1 py-0.5 rounded bg-muted text-xs">file</code> field. This also works for <code class="px-1 py-0.5 rounded bg-muted text-xs">entities</code> and <code class="px-1 py-0.5 rounded bg-muted text-xs">series</code>.</li>
						<li><strong class="text-foreground">From a URL:</strong> <code class="px-1 py-0.5 rounded bg-muted text-xs">POST /api/events/{id}/photos/from-url</code> with <code class="px-1 py-0.5 rounded bg-muted text-xs">{"url": "https://…"}</code>. The server downloads the image for you. The URL must be public https, and the image must be a jpg, png, gif, or webp of 5 MB or less.</li>
						<li>You can add photos only to records you own (or any record, as an admin). The first photo becomes the primary image.</li>
					</ul>
				</div>
			</section>

			<!-- ========== Limits ========== -->
			<section id="limits" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-speedometer2 text-primary text-xl"></i>
						</div>
						Rate limits &amp; etiquette
					</h2>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><strong class="text-foreground">240 requests per minute</strong> per logged-in user (basic auth or token).</li>
						<li><strong class="text-foreground">120 requests per minute</strong> per IP address for requests that aren't logged in.</li>
						<li>Adding photos by URL has its own limit of <strong class="text-foreground">20 requests per minute</strong> per user.</li>
						<li>After <strong class="text-foreground">20 failed logins in a minute</strong> from one IP address, basic auth from that address is refused until the minute is up, even with the right password. If your script keeps failing to log in, check your credentials before retrying.</li>
						<li>Every response includes <code class="px-1 py-0.5 rounded bg-muted text-xs">X-RateLimit-Remaining</code>. Over a limit you'll get <code class="px-1 py-0.5 rounded bg-muted text-xs">429 Too Many Requests</code> with a <code class="px-1 py-0.5 rounded bg-muted text-xs">Retry-After</code> header saying how many seconds to wait.</li>
						<li>This is a community-run site on modest hardware. Page through results instead of requesting huge lists, cache what you can, and space out bulk imports.</li>
						<li>Check for existing events, entities, and series before creating new ones so you don't make duplicates.</li>
						<li>Everything you create through the API is covered by the <a href="{{ url('/tos') }}" class="text-primary hover:underline">Terms of Service</a>, just like content added through the site.</li>
					</ul>
				</div>
			</section>

			<!-- ========== Next ========== -->
			<section id="next" class="card-tw">
				<div class="p-6 space-y-4">
					<h2 class="text-2xl font-semibold text-foreground flex items-center gap-3">
						<div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center shrink-0">
							<i class="bi bi-signpost-2 text-primary text-xl"></i>
						</div>
						Where to go next
					</h2>
					<ul class="list-disc list-inside space-y-1 text-muted-foreground">
						<li><a href="{{ url('/api/docs') }}" class="text-primary hover:underline">/api/docs</a>: the full endpoint reference.</li>
						<li><a href="https://github.com/geoff-maddock/events-tracker" class="text-primary hover:underline" target="_blank" rel="noopener">github.com/geoff-maddock/events-tracker</a>: the source code. Report API bugs in <a href="https://github.com/geoff-maddock/events-tracker/issues" class="text-primary hover:underline" target="_blank" rel="noopener">GitHub issues</a>.</li>
						<li><a href="{{ url('/help') }}" class="text-primary hover:underline">Help &amp; Tutorials</a>: how the site works (events, entities, series, tags).</li>
					</ul>
				</div>
			</section>

		</div>
	</div>
</div>

@stop

@section('footer')
@stop
