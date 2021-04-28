<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Toggle Navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<span class="{{ Request::is('/') ? 'active' : '' }} site-title"><a class="navbar-brand" data-toggle="tooltip"  data-placement="bottom"  data-delay='{"show":"500", "hide":"100"}' title="Return to the home page." href="{{ url('/') }}">{{ config('app.app_name')}} </a></span>
		</div>

		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav ">
				<li class="dropdown {{ Request::is('events') ? 'active' : '' }}">
		          <a href="{{ url('/events') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false" data-toggle="xtooltipx"  data-placement="bottom"  title="Show paginated list of events">Events <span class="caret"></span></a>
		          <ul class="dropdown-menu">
					<li class="{{ Request::is('events') ? 'active' : '' }}"><a href="{{ url('/events') }}">Event Index</a></li>
					<li class="{{ Request::is('events/grid') ? 'active' : '' }}"><a href="{{ url('/events/grid') }}">Event Grid</a></li>
                    <li class="{{ Request::is('events/week') ? 'active' : '' }}"><a href="{{ url('/events/week') }}">Week's Events</a></li>
                    <li class="{{ Request::is('events/attending') ? 'active' : '' }}"><a href="{{ url('/events/attending') }}">Events Attending</a></li>
		            <li class="{{ Request::is('events/feed') ? 'active' : '' }}"><a href="{{ url('/events/feed') }}" target="_blank" rel="noopener">Events Text Only</a></li>
		            <li class="{{ Request::is('events/create') ? 'active' : '' }}"><a href="{!! url('/events/create') !!}">Add Event</a></li>
                      <li role="separator" class="divider"></li>
                    <li class="{{ Request::is('series') ? 'active' : '' }}"><a href="{{ url('/series') }}">Event Series</a></li>
		            <li class="{{ Request::is('series/create') ? 'active' : '' }}"><a href="{!! url('/series/create') !!}" title="Add a reoccurring event series.">Add Series</a></li>
		          </ul>
		        </li>
				<li class="dropdown {{ Request::is('entities') ? 'active' : '' }}">
		          <a href="{{ url('/entities') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="Show paginated list of entities">Entities <span class="caret"></span></a>
		          <ul class="dropdown-menu">
		          @foreach ($roles as $role)
		          	<li class="{{ Request::is(strtolower('entities/role/'.$role->name)) ? 'active' : '' }}"><a href="{{ url('/entities/role/'.strtolower($role->name)) }}">{{ $role->name }}</a></li>
		          @endforeach
		            <li class="{{ Request::is('entities/create') ? 'active' : '' }}"><a href="{!! url('/entities/create') !!}" >Add Entity</a></li>
		          </ul>
		        </li>
				<li class="dropdown {{ Request::is('calendar') ? 'active' : '' }}">
		          <a href="{{ url('/calendar') }}" class="dropdown-toggle" data-hover="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="Show a calendar view of events.">Calendar <span class="caret"></span></a>
		          <ul class="dropdown-menu">
		            <li class="{{ Request::is('calendar/free') ? 'active' : '' }}"><a href="{!! url('/calendar/free') !!}" >Free Shows</a></li>
		            <li class="{{ Request::is('calendar/min-age/0') ? 'active' : '' }}"><a href="{!! url('/calendar/min-age/0') !!}" >All Ages</a></li>
		            <li class="{{ Request::is('calendar/type/club night') ? 'active' : '' }}"><a href="{!! url('/calendar/type/club night') !!}" >Club Night</a></li>
					<li class="{{ Request::is('calendar/type/concert') ? 'active' : '' }}"><a href="{!! url('/calendar/type/concert') !!}" >Live Concert</a></li>
					<li class="{{ Request::is('calendar/attending') ? 'active' : '' }}"><a href="{!! url('/calendar/attending') !!}" >Attending</a></li>
		          </ul>
		        </li>
                <!-- MORE only shown when collapsed down to medium desktops or smaller -->
				<li class="dropdown visible-xs-block visible-sm-block visible-md-block">
					<a href="#" class="dropdown-toggle visible-xs-block visible-sm-block visible-md-block" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">More <span class="caret"></span></a>

					<ul class="dropdown-menu">
						@isset ($hasForum))
						<li class="{{ Request::is('threads') ? 'active' : '' }}"><a href="{{ url('/threads')}}" title="Show a list of discussion forums.">Forum</a></li>
						@endisset
						<li class="{{ Request::is('tags') ? 'active' : '' }}"><a href="{{ url('/tags') }}" title="Show a list of tag topics.">Tags</a></li>
						<li class="{{ Request::is('users') ? 'active' : '' }}"><a href="{{ url('/users') }}" title="Show a list of registered users.">Users</a></li>
					</ul>
				</li>
                <!-- MORE end -->
				@isset ($hasForum)
				<li class="{{ Request::is('threads') ? 'active' : '' }} visible-lg"><a href="{{ url('/threads')}}" title="Show a list of discussion forums.">Forum</a></li>
				@endisset
				<li class="{{ Request::is('tags') ? 'active' : '' }} visible-lg"><a href="{{ url('/tags') }}" title="Show a list of keyword topics.">Keywords</a></li>
				@if (!Auth::guest())
					<li class="{{ Request::is('users') ? 'active' : '' }} visible-lg"><a href="{{ url('/users') }}" title="Show a list of registered users.">Users</a></li>
		        @endif
		        <li>
				  <form class="navbar-form navbar-left" role="search" action="/search">
					<div class="form-group">
					  <input type="text" class="form-control" placeholder="Search" name="keyword" style="width: 150px;" value="{{ isset($slug) ? $slug : '' }}">
					</div>
				  </form>
		     	</li>
			</ul>

			<ul class="nav navbar-nav navbar-right">

				@if (Auth::guest())
					@foreach ($menus as $menu)
						<li><a href="{{ url('/menus/'.$menu->id.'/content') }}" class="visible-lg">{{ $menu->name  }}</a></li>
					@endforeach
					<li><a href="{{ url('/login') }}" class="visible-lg">Login</a></li>
					<li><a href="{{ url('/register') }}" class="visible-lg">Register</a></li>
                    <li><a href="{{ url('/login') }}" class="hidden-lg"><span class="glyphicon glyphicon-user" title="Login"></span></a></li>
                    <li><a href="{{ url('/register') }}" class="visible-md"><span class="glyphicon glyphicon-flag" title="Register"></span></a></li>
				@else
					@if ($latest)
					<li></li>
					@endif
						@foreach ($menus as $menu)
							<li><a href="{{ url('/menus/'.$menu->id.'/content') }}" class="visible-lg">{{ $menu->name  }}</a></li>
						@endforeach
					<li class="dropdown">
						<a href="{{ url('/users/'.Auth::user()->id) }}" class="dropdown-toggle visible-xs-block visible-sm-block visible-md-block" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user" title="Login"></span> <span class="caret"></span></a>
                        <a href="{{ url('/users/'.Auth::user()->id) }}" class="dropdown-toggle visible-lg" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ Auth::user()->name }} <span class="caret"></span></a>
                        <ul class="dropdown-menu">
							<li><a href="{{ url('/users/'.Auth::user()->id) }}">Profile</a></li>
							@can('show_admin')
								<li role="separator" class="divider"></li>
								<li><a href="#"><b>Admin</b></a></li>
								<li><a href="{{ url('/forums')}}" title="Show discussion forums">Forum</a></li>
								<li><a href="{{ url('/posts')}}" title="Show all the latests posts">Posts</a></li>
								<li><a href="{{ url('/permissions')}}" title="Show a list of user permissions">Permissions</a></li>
								<li><a href="{{ url('/groups')}}" title="Show a list of user permission groups">Groups</a></li>
							@endcan
							@can('show_activity')
								<li><a href="{{ url('/activity')}}">Activity</a></li>
							@endcan
							@can('show_admin')
								<li><a href="{{ url('/blogs')}}">Blogs</a></li>
								<li><a href="{{ url('/menus')}}">Menus</a></li>
								<li><a href="{{ url('/tools')}}">Tools</a></li>
							@endcan

							<li role="separator" class="divider visible-xs-block visible-sm-block"></li>
							<li><a href="{{ url('/help')}}" class="visible-xs-block visible-sm-block">Help</a></li>

							<li><a href="mailto:{{ config('app.feedback') }}" title="Send email to {{ config('app.feedback') }}" class="visible-xs-block visible-sm-block">Feedback</a></li>

							<li role="separator" class="divider"></li>
							<li>
								<a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
								<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">{{ csrf_field() }}</form>
							</li>
						</ul>
					</li>
				@endif

					<li class="dropdown ">
						<a href="{{ url('/help') }}" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="Help"><span class="glyphicon glyphicon-question-sign" title="Help"></span><span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="{{ url('/help')}}">Help</a></li>
							<li><a href="{{ url('/register') }}" class="hidden-md hidden-lg">Register</a></li>
							<li><a href="mailto:{{ config('app.feedback') }}" title="Send email to {{ config('app.feedback') }}">Feedback</a></li>
						</ul>
					</li>
			</ul>
		</div>
	</div>
</nav>
