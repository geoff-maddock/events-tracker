<!-- User Card Component -->
<article class="card-tw hover:border-primary/30 transition-all" id="user-card-{{ $user->id }}">
    <div class="p-4 flex flex-col items-center text-center">
        <!-- User Avatar -->
        <div class="mb-4">
            @if ($user->profile && $user->profile->avatar)
            <a href="{{ route('users.show', [$user->id]) }}">
                <img src="{{ Storage::disk('external')->url($user->profile->getStorageThumbnail()) }}"
                    alt="{{ $user->name }}"
                    class="w-24 h-24 rounded-full object-cover border-2 border-border hover:border-primary transition-colors">
            </a>
            @else
            <a href="{{ route('users.show', [$user->id]) }}">
                <div class="w-24 h-24 rounded-full bg-card flex items-center justify-center border-2 border-border hover:border-primary transition-colors">
                    <i class="bi bi-person text-4xl text-muted-foreground/50"></i>
                </div>
            </a>
            @endif
        </div>

        <!-- User Name -->
        <h3 class="text-lg font-semibold text-foreground hover:text-primary transition-colors mb-2">
            <a href="{{ route('users.show', [$user->id]) }}">{{ $user->name }}</a>
        </h3>

        <!-- User Status Badge -->
        @if (isset($user->user_status))
        <div class="mb-3">
            <span class="badge-tw {{ $user->user_status->name == 'Active' ? 'badge-primary-tw' : 'badge-secondary-tw' }} text-xs">
                {{ $user->user_status->name }}
            </span>
        </div>
        @endif

        <!-- User Bio/Description -->
        @if ($user->profile && $user->profile->bio)
        <p class="text-sm text-muted-foreground mb-3 line-clamp-3">{{ $user->profile->bio }}</p>
        @endif

        <!-- User Stats -->
        <div class="flex items-center justify-center gap-4 text-sm text-muted-foreground mb-3">
            <!-- Joined Date -->
            <div class="flex items-center gap-1" title="Member since">
                <i class="bi bi-calendar3"></i>
                <span>{{ $user->created_at->format('M Y') }}</span>
            </div>

            <!-- Events Count (if available) -->
            @if (isset($user->events_count))
            <div class="flex items-center gap-1" title="Events created">
                <i class="bi bi-calendar-event"></i>
                <span>{{ $user->events_count }}</span>
            </div>
            @endif

            <!-- Threads Count (if available) -->
            @if (isset($user->threads_count))
            <div class="flex items-center gap-1" title="Threads created">
                <i class="bi bi-chat-dots"></i>
                <span>{{ $user->threads_count }}</span>
            </div>
            @endif
        </div>

        <!-- User Groups/Roles -->
        @unless ($user->groups->isEmpty())
        <div class="flex flex-wrap gap-1 justify-center">
            @foreach ($user->groups->take(3) as $group)
            <span class="badge-tw badge-accent-tw text-xs">
                {{ $group->label }}
            </span>
            @endforeach
            @if ($user->groups->count() > 3)
            <span class="text-xs text-muted-foreground/50">+{{ $user->groups->count() - 3 }}</span>
            @endif
        </div>
        @endunless
    </div>

    <!-- Card Footer - Actions -->
    @if ($signedIn)
    <div class="px-4 py-3 border-t border-border flex items-center justify-center gap-3">
        <!-- View Profile Button -->
        <a href="{{ route('users.show', [$user->id]) }}"
            class="text-muted-foreground hover:text-primary transition-colors"
            title="View profile">
            <i class="bi bi-person-circle"></i>
        </a>

        <!-- Edit Button (only for own profile or admin) -->
        @if (Auth::user()->id == $user->id || Auth::user()->hasGroup('super_admin'))
        <a href="{{ route('users.edit', [$user->id]) }}"
            class="text-muted-foreground hover:text-primary transition-colors"
            title="Edit profile">
            <i class="bi bi-pencil"></i>
        </a>
        @endif
    </div>
    @endif
</article>
