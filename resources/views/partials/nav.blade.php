<nav class="navbar navbar-default">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Toggle Navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<span class="{{ Request::is('/') ? 'active' : '' }}"><a class="navbar-brand" href="{{ url('/') }}">{{ config('app.app_name')}} </a></span>
		</div>

		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav">
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


				<li class="dropdown {{ Request::is('calendar') ? 'active' : '' }}">
		          <a href="{{ url('/calendar') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Calendar <span class="caret"></span></a>
		          <ul class="dropdown-menu">
		            <li class="{{ Request::is('calendar/free') ? 'active' : '' }}"><a href="{!! url('/calendar/free') !!}" >Free Shows</a></li>
		            <li class="{{ Request::is('calendar/min_age/0') ? 'active' : '' }}"><a href="{!! url('/calendar/min_age/0') !!}" >All Ages</a></li>
		            <li class="{{ Request::is('calendar/type/club night') ? 'active' : '' }}"><a href="{!! url('/calendar/type/club night') !!}" >Club Night</a></li>
					<li class="{{ Request::is('calendar/attending') ? 'active' : '' }}"><a href="{!! url('/calendar/attending') !!}" >Attending</a></li>
		          </ul>
		        </li>

				<li><a href="{{ url('/threads')}}">Forum</a></li> 
				<li class="{{ Request::is('tags') ? 'active' : '' }}"><a href="{{ url('/tags') }}">Tags</a></li>
				<li class="{{ Request::is('users') ? 'active' : '' }}"><a href="{{ url('/users') }}">Users</a></li>
		        <li>
		      <form class="navbar-form navbar-left" role="search" action="/search">
		        <div class="form-group">
		          <input type="text" class="form-control" placeholder="Search" name="keyword">
		        </div>
		      </form>
		      </li>
			</ul>

			<ul class="nav navbar-nav navbar-right">
				@can('show_admin')
				<li class="dropdown ">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="{{ url('/forums')}}">Forum</a></li> 
						<li><a href="{{ url('/threads')}}">Threads</a></li> 
						<li><a href="{{ url('/permissions')}}">Permissions</a></li> 
						<li><a href="{{ url('/groups')}}">Groups</a></li> 

						@can('show_activity')
						<li><a href="{{ url('/activity')}}">Activity</a></li> 
						@endcan
					</ul>
				</li>
				@endcan
				<li class="dropdown ">
					<a href="{{ url('/help') }}"  class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Help <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="{{ url('/help')}}">Help</a></li> 
						<li><a href="mailto:{{ config('app.feedback') }}" title="Send email to {{ config('app.feedback') }}">Feedback</a></li>
					</ul>
				</li>

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
							<li><a href="{{ url('/events/attending') }}">Events Attending</a></li>
							<li><a href="{{ url('/auth/logout') }}">Logout</a></li>
						</ul>
					</li>
				@endif 
			</ul>
		</div>
	</div>
</nav>
