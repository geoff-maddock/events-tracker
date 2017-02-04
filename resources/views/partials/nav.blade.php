<nav class="navbar navbar-default">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Toggle Navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="#">Event Repo</a>
		</div>

		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav">
				<li class="{{ Request::is('/') ? 'active' : '' }}"><a href="{{ url('/') }}">Home</a></li>
				<li class="dropdown {{ Request::is('events') ? 'active' : '' }}">
		          <a href="{{ url('/events') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Events <span class="caret"></span></a>
		          <ul class="dropdown-menu">
		            <li class="{{ Request::is('events/all') ? 'active' : '' }}"><a href="{{ url('/events/all') }}">All Events</a></li>
		            <li class="{{ Request::is('series') ? 'active' : '' }}"><a href="{{ url('/series') }}">Event Series</a></li>
		            <li class="{{ Request::is('events/feed') ? 'active' : '' }}"><a href="{{ url('/events/feed') }}" target="_blank">Events Text Only</a></li>
		            <li class="{{ Request::is('events/create') ? 'active' : '' }}"><a href="{!! url('/events/create') !!}">Add Event</a></li>
		            <li class="{{ Request::is('series/create') ? 'active' : '' }}"><a href="{!! url('/series/create') !!}">Add Series</a></li>
		          </ul>
		        </li>
				<li class="dropdown {{ Request::is('entities') ? 'active' : '' }}">
		          <a href="{{ url('/entities') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Entities <span class="caret"></span></a>
		          <ul class="dropdown-menu">
		          @foreach ($roles as $role)
		          	<li class="{{ Request::is(strtolower('entities/role/'.$role->name)) ? 'active' : '' }}"><a href="{{ url('/entities/role/'.strtolower($role->name)) }}">{{ $role->name }}</a></li>
		          @endforeach
		            <li class="{{ Request::is('entities/create') ? 'active' : '' }}"><a href="{!! url('/entities/create') !!}" >Add Entity</a></li>
		          </ul>
		        </li>
				<li class="{{ Request::is('calendar') ? 'active' : '' }}"><a href="{{ url('/calendar') }}">Calendar</a></li>
				<li class="{{ Request::is('tags') ? 'active' : '' }}"><a href="{{ url('/tags') }}">Tags</a></li>
				<li class="dropdown {{ Request::is('users') ? 'active' : '' }}">
		          <a href="{{ url('/users') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Users <span class="caret"></span></a>
		        </li>
		        <li>
		      <form class="navbar-form navbar-left" role="search" action="/search">
		        <div class="form-group">
		          <input type="text" class="form-control" placeholder="Search" name="keyword">
		        </div>
		      </form>
		      </li>
			</ul>

			<ul class="nav navbar-nav navbar-right">
				<li><a href="mailto:{{ config('app.feedback') }}" title="Send email to {{ config('app.feedback') }}">Feedback</a></li>
				<li><a href="{{ url('/help') }}">Help</a></li>
				@if (Auth::guest())
					<li><a href="{{ url('/auth/login') }}">Login</a></li>
					<li><a href="{{ url('/auth/register') }}">Register</a></li>
				@else
					@if ($latest)
					<li></li>
					@endif
					<li class="dropdown ">
						<a href="{{ url('/users/'.Auth::user()->id) }}" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ Auth::user()->name }} <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="{{ url('/users/'.Auth::user()->id) }}">Profile</a></li> 
							<li><a href="{{ url('/auth/logout') }}">Logout</a></li>
						</ul>
					</li>
				@endif 
			</ul>
		</div>
	</div>
</nav>
