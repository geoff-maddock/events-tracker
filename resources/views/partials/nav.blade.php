<nav class="navbar navbar-expand-md navbar-background navbar-dark">
	<div class="container-fluid max-viewport">
		<div class="navbar-header nav-title">
			<span class="{{ Request::is('/') ? 'active' : '' }} site-title">
				<a class="navbar-brand p-3" data-toggle="tooltip"  data-placement="bottom"  data-delay='{"show":"500", "hide":"100"}' title="" href="{{ url('/') }}">{{ config('app.app_name')}} </a>
			</span>
		</div>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-collapsable" aria-controls="navbar-collapsable" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		  </button>
		
		<div class="collapse navbar-collapse" id="navbar-collapsable">
			<ul class="navbar-nav me-auto">
				<li class="nav-item dropdown {{ Request::is('events') ? 'active' : '' }}">
		          <a href="#" class="nav-link dropdown-toggle" id="event-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"  title="Show paginated list of events">Events <span class="caret"></span></a>
		          <ul class="dropdown-menu" aria-labelledby="event-dropdown">
					<li class="{{ Request::is('events/future') ? 'active' : '' }}">
						<a href="{{ url('/events/future') }}" class="dropdown-item">Events Future</a>
					</li>
					<li class="{{ Request::is('events') ? 'active' : '' }}">
						<a href="{{ url('/events') }}" class="dropdown-item">Event Index</a>
					</li>
					<li class="{{ Request::is('events/grid') ? 'active' : '' }}"><a href="{{ url('/events/grid') }}" class="dropdown-item">Event Grid</a></li>
					<li class="{{ Request::is('events/week') ? 'active' : '' }}"><a href="{{ url('/events/week') }}" class="dropdown-item">Week's Events</a></li>
                    <li class="{{ Request::is('events/attending') ? 'active' : '' }}"><a href="{{ url('/events/attending') }}" class="dropdown-item">Events Attending</a></li>
		            <li class="{{ Request::is('events/feed') ? 'active' : '' }}"><a href="{{ url('/events/feed') }}" target="_blank" rel="noopener" class="dropdown-item">Events Text Only</a></li>
		            <li class="{{ Request::is('events/create') ? 'active' : '' }}"><a href="{!! url('/events/create') !!}" class="dropdown-item">Add Event</a></li>
					<li role="separator" class="divider"><hr class="dropdown-divider"></li>
					<li class="{{ Request::is('photos') ? 'active' : '' }}"><a href="{{ url('/photos') }}" class="dropdown-item">Photos</a></li>
					<li role="separator" class="divider"><hr class="dropdown-divider"></li>
                    <li class="{{ Request::is('series') ? 'active' : '' }}"><a href="{{ url('/series') }}" class="dropdown-item">Event Series</a></li>
					<li class="{{ Request::is('series/following') ? 'active' : '' }}">
						<a href="{{ url('/series/following') }}" class="dropdown-item">Series Following</a>
					</li>
		            <li class="{{ Request::is('series/create') ? 'active' : '' }}"><a href="{!! url('/series/create') !!}" title="Add a reoccurring event series." class="dropdown-item">Add Series</a></li>
		          </ul>
		        </li>
				<li class="nav-item dropdown {{ Request::is('entities') ? 'active' : '' }}">
		          <a href="#" class="nav-link dropdown-toggle" id="entity-dropdown" data-hover="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Show paginated list of entities">Entities <span class="caret"></span></a>
		          <ul class="dropdown-menu" aria-labelledby="entity-dropdown">
					<li class="{{ Request::is('entities') ? 'active' : '' }}">
						<a href="{{ url('/entities') }}" class="dropdown-item">Entity Index</a>
					</li>
		          @foreach ($roles as $role)
		          	<li class="{{ Request::is(strtolower('entities/role/'.$role->name)) ? 'active' : '' }}">
						<a href="{{ url('/entities/role/'.strtolower($role->name)) }}" class="dropdown-item">{{ $role->name }}</a></li>
		          @endforeach
					<li class="{{ Request::is('entities/following') ? 'active' : '' }}">
						<a href="{{ url('/entities/following') }}" class="dropdown-item">Entities Following</a>
					</li>
		            <li class="{{ Request::is('entities/create') ? 'active' : '' }}">
						<a href="{!! url('/entities/create') !!}" class="dropdown-item">Add Entity</a>
					</li>
		          </ul>
		        </li>
				<li class="nav-item dropdown {{ Request::is('calendar') ? 'active' : '' }}">
		          <a href="#" class="nav-link dropdown-toggle" id="calendar-dropdown" data-hover="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Show a calendar view of events.">Calendar <span class="caret"></span></a>
		          <ul class="dropdown-menu" aria-labelledby="calendar-dropdown">
		            <li class="{{ Request::is('calendar') ? 'active' : '' }}"><a href="{!! url('/calendar') !!}" class="dropdown-item">Full Calendar</a></li>					  
		            <li class="{{ Request::is('calendar/free') ? 'active' : '' }}"><a href="{!! url('/calendar/free') !!}"  class="dropdown-item">Free Shows</a></li>
		            <li class="{{ Request::is('calendar/min-age/0') ? 'active' : '' }}"><a href="{!! url('/calendar/min-age/0') !!}"  class="dropdown-item">All Ages</a></li>
		            <li class="{{ Request::is('calendar/type/club night') ? 'active' : '' }}"><a href="{!! url('/calendar/type/club night') !!}"  class="dropdown-item">Club Night</a></li>
					<li class="{{ Request::is('calendar/type/concert') ? 'active' : '' }}"><a href="{!! url('/calendar/type/concert') !!}"  class="dropdown-item">Live Concert</a></li>
					<li class="{{ Request::is('calendar/type/live stream') ? 'active' : '' }}"><a href="{!! url('/calendar/type/live stream') !!}"  class="dropdown-item">LiveStream</a></li>
					<li class="{{ Request::is('calendar/attending') ? 'active' : '' }}"><a href="{!! url('/calendar/attending') !!}"  class="dropdown-item">Attending</a></li>
					<li class="{{ Request::is('tag-calendar') ? 'active' : '' }}"><a href="{!! url('/tag-calendar') !!}"  class="dropdown-item">Keyword Tags</a></li>
		          </ul>
		        </li>
                <!-- MORE only shown when collapsed down to medium desktops or smaller -->
				<li class="nav-item dropdown d-xl-none {{ Request::is('forum') ? 'active' : '' }}">
					<a href="#" class="nav-link dropdown-toggle visible-xs-block visible-sm-block visible-md-block" data-hover="dropdown" id="more-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More <span class="caret"></span></a>
					<ul class="dropdown-menu" aria-labelledby="more-dropdown">
						@isset ($hasForum)
						<li class="{{ Request::is('threads') ? 'active' : '' }}"><a href="{{ url('/threads')}}"  class="dropdown-item" title="Show a list of discussion forums.">Forum</a></li>
						@endisset
						<li class="{{ Request::is('tags') ? 'active' : '' }}"><a href="{{ url('/tags') }}"  class="dropdown-item" title="Show a list of tag topics.">Tags</a></li>
						<li class="{{ Request::is('users') ? 'active' : '' }}"><a href="{{ url('/users') }}"  class="dropdown-item" title="Show a list of registered users.">Users</a></li>
					</ul>
				</li>
                <!-- MORE end -->
				@isset ($hasForum)
				<li class="nav-item {{ Request::is('threads') ? 'active' : '' }} d-none d-xl-block">
					<a href="{{ url('/threads')}}" title="Show a list of discussion forums." class="nav-link">Forum</a>
				</li>
				@endisset
				<li class="nav-item {{ Request::is('tags') ? 'active' : '' }} d-none d-xl-block">
					<a href="{{ url('/tags') }}" title="Show a list of keyword topics." class="nav-link">Keywords</a>
				</li>
				@if (!Auth::guest())
					<li class="{{ Request::is('users') ? 'active' : '' }} d-none d-xl-block">
						<a href="{{ url('/users') }}" title="Show a list of registered users." class="nav-link">Users</a>
					</li>
		        @endif
		        <li class="mx-2 d-none d-sm-none d-md-block">
				  <form class="navbar-form navbar-left" role="search" action="/search">
					<div class="form-group">
					  <input type="text" class="form-control form-background" placeholder="Search" name="keyword"  title="Search" aria-label="Search" value="{{ isset($search) ? $search : '' }}">
					</div>
				  </form>
		     	</li>
			</ul>

			<ul class="navbar-nav navbar-right">

				@if (Auth::guest())
					@foreach ($menus as $menu)
						<li class="nav-item">
							<a href="{{ url('/menus/'.$menu->id.'/content') }}" class="d-none d-xl-block nav-link" title="{{ $menu->body}}">{{ $menu->name  }}</a>
						</li>
					@endforeach
					<li class="nav-item">
						<a href="{{ url('/login') }}" class="d-none d-xl-block nav-link" title="Log in with a registered account." >Login</a>
					</li>
					<li class="	nav-item">
						<a href="{{ url('/register') }}" class="d-none d-xl-block nav-link" title="Register a new user account.">Register</a>
					</li>
                    <li class="nav-item d-xl-none">
						<a href="{{ url('/login') }}" class="visible-xs-block visible-sm-block visible-md-block visible-lg-block nav-link">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
							<path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
						  </svg>
						</a>
					</li>
                    <li class="nav-item d-xl-none">
						<a href="{{ url('/register') }}" class="visible-xs-block visible-sm-block visible-md-block visible-lg-block nav-link">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-flag-fill" viewBox="0 0 16 16">
								<path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12.435 12.435 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A19.626 19.626 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a19.587 19.587 0 0 0 1.349-.476l.019-.007.004-.002h.001"/>
							</svg>
						</a>
					</li>
				@else
						@foreach ($menus as $menu)
							<li class="nav-item d-none d-xl-block"><a href="{{ url('/menus/'.$menu->id.'/content') }}" class="nav-link visible-lg">{{ $menu->name  }}</a></li>
						@endforeach

					<li class="nav-item dropdown">
							<a href="#" class="nav-link dropdown-toggle d-xl-none" id="profile-dropdown-icon" data-hover="dropdown" data-bs-toggle="dropdown"  aria-haspopup="true" aria-expanded="false">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
									<path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
								  </svg>
							</a>
							<a href="#" class="nav-link dropdown-toggle d-none d-xl-block" id="profile-dropdown" data-hover="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User settings.">{{ Auth::user()->name }} <span class="caret"></span></a>
							<ul class="dropdown-menu" aria-labelledby="profile-dropdown">

							<li class="nav-item"><a href="{{ url('/users/'.Auth::user()->id) }}" class="dropdown-item">Profile</a></li>
							@can('show_activity')
							<li class="nav-item"><a href="{{ url('/activity')}}" class="dropdown-item">Activity</a></li>
							@endcan
							@can('show_admin')
								<li role="separator" class="divider dropdown-divider"></li>
								<li class="nav-item"><a href="#" class="dropdown-item"><b>Admin</b></a></li>
								<li class="nav-item"><a href="{{ url('/blogs')}}" class="dropdown-item">Blogs</a></li>
								<li class="nav-item"><a href="{{ url('/categories')}}" title="Show thread categories" class="dropdown-item">Categories</a></li>
								<li class="nav-item"><a href="{{ url('/forums')}}" title="Show discussion forums" class="dropdown-item">Forum</a></li>
								<li class="nav-item"><a href="{{ url('/groups')}}" title="Show a list of user permission groups" class="dropdown-item">Groups</a></li>
								<li class="nav-item"><a href="{{ url('/menus')}}" class="dropdown-item">Menus</a></li>
								<li class="nav-item"><a href="{{ url('/permissions')}}" title="Show a list of user permissions" class="dropdown-item">Permissions</a></li>
								<li class="nav-item"><a href="{{ url('/posts')}}" title="Show all the latests posts" class="dropdown-item">Posts</a></li>
								<li class="nav-item"><a href="{{ url('/tools')}}" class="dropdown-item">Tools</a></li>
							@endcan

							<li role="separator" class="divider dropdown-divider"></li>
							<li>
								<a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item">Logout</a>
								<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">{{ csrf_field() }}</form>
							</li>
						</ul>
					</li>
				@endif

					<li class="dropdown nav-item">
						<a href="#" class="nav-link dropdown-toggle d-xs-none" id="help-dropdown" data-hover="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Help">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle-fill" viewBox="0 0 16 16">
								<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247zm2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/>
							  </svg>
						</a>
						<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="help-dropdown">
							<li class="nav-item"><a href="{{ url('/help')}}" class="dropdown-item">Help</a></li>
							<li class="nav-item"><a href="{{ url('/privacy')}}" class="dropdown-item">Privacy</a></li>
							<li class="nav-item"><a href="{{ url('/register') }}" class="dropdown-item d-md-none d-lg-none d-xl-none">Register</a></li>
							<li class="nav-item"><a href="mailto:{{ config('app.feedback') }}" title="Send email to {{ config('app.feedback') }}" class="dropdown-item">Feedback</a></li>
							@foreach ($menus as $menu)
							<li class="nav-item d-xl-none"><a href="{{ url('/menus/'.$menu->id.'/content') }}" class="dropdown-item visible-lg">{{ $menu->name  }}</a></li>
							@endforeach
						</ul>
					</li>
			</ul>
		</div>
	</div>
</nav>
