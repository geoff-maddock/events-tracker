@if (isset($entities) && count($entities) > 0)

<ul class="space-y-2">
    @foreach ($entities as $entity)
        <li class="flex items-start gap-3 p-3 bg-card border border-border rounded-lg hover:bg-accent/50 transition-colors {{ $entity->entityStatus && $entity->entityStatus->name === 'Inactive' ? 'opacity-60' : '' }}">
            @if ($primary = $entity->getPrimaryPhoto())
                <div class="flex-shrink-0">
                    <a href="{{ route('entities.show', [$entity->slug]) }}">
                        <img src="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" alt="{{ $entity->name }}" class="w-12 h-12 rounded-lg object-cover">
                    </a>
                </div>
            @endif

            <div class="flex-grow min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('entities.show', [$entity->slug]) }}" class="font-medium text-foreground hover:text-primary transition-colors">
                        {{ $entity->name }}
                    </a>

                    @if ($entity->entityStatus && $entity->entityStatus->name === 'Inactive')
                        <span class="text-xs text-amber-500">[Inactive]</span>
                    @endif

                    @if ($signedIn && $entity->ownedBy($user))
                        <a href="{{ route('entities.edit', ['entity' => $entity->slug]) }}" class="text-muted-foreground hover:text-foreground transition-colors">
                            <i class="bi bi-pencil text-sm"></i>
                        </a>
                    @endif

                    @if ($signedIn)
                        @if ($follow = $entity->followedBy($user))
                            <a href="{{ route('entities.unfollow', ['id' => $entity->id]) }}" title="Click to unfollow" class="text-primary hover:text-primary/80 transition-colors">
                                <i class="bi bi-dash-circle-fill"></i>
                            </a>
                        @else
                            <a href="{{ route('entities.follow', ['id' => $entity->id]) }}" title="Click to follow" class="text-muted-foreground hover:text-primary transition-colors">
                                <i class="bi bi-plus-circle-fill"></i>
                            </a>
                        @endif
                    @endif
                </div>

                @if ($entity->entityType)
                    <div class="text-sm text-muted-foreground mt-1">
                        {{ $entity->entityType->name }}
                    </div>
                @endif

                @if ($entity->getPrimaryLocationAddress())
                    <div class="text-sm text-muted-foreground">
                        {{ $entity->getPrimaryLocationAddress() }}
                        @if ($entity->getPrimaryLocation() && $entity->getPrimaryLocation()->neighborhood)
                            - {{ $entity->getPrimaryLocation()->neighborhood }}
                        @endif
                    </div>
                @endif

                @unless ($entity->roles->isEmpty())
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach ($entity->roles as $role)
                            <a href="/entities/role/{{ $role->name }}" class="badge-tw badge-secondary-tw text-xs">
                                {{ $role->name }}
                            </a>
                        @endforeach
                    </div>
                @endunless
            </div>
        </li>
    @endforeach
</ul>

@else
    <div class="text-center py-8 text-muted-foreground italic">
        No entities found
    </div>
@endif
