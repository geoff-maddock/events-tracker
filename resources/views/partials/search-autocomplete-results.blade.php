{{--
    Shared results markup for the search autocomplete component.
    Included from partials/search-autocomplete.blade.php in both the inline
    dropdown and the teleported full-screen overlay.

    Optional: $inOverlay (bool) — controls the "Start typing…" empty-state hint.
--}}
@php $inOverlay = $inOverlay ?? false; @endphp

<template x-if="loading">
    <div class="px-3 py-2 text-muted-foreground">Searching&hellip;</div>
</template>
<template x-if="!loading && totalCount === 0 && query.length > 0">
    <div class="px-3 py-2 text-muted-foreground">No matches for &ldquo;<span x-text="query"></span>&rdquo;.</div>
</template>
@if($inOverlay)
<template x-if="!loading && query.length === 0 && overlay">
    <div class="px-3 py-3 text-muted-foreground">Start typing to search events, venues, artists, series, and tags.</div>
</template>
@endif

<template x-for="group in groups" :key="group.key">
    <div x-show="group.items.length > 0">
        <div class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground bg-muted/50" x-text="group.label"></div>
        <ul>
            <template x-for="(item, idx) in group.items" :key="group.key + '-' + item.id">
                <li>
                    <a :href="item.url"
                       class="block px-3 py-2 hover:bg-muted focus:bg-muted"
                       :class="{ 'bg-muted': isActive(group.key, idx) }"
                       x-on:mouseenter="setActive(group.key, idx)"
                       role="option"
                       :aria-selected="isActive(group.key, idx)">
                        <div class="font-medium text-foreground" x-html="highlight(item.name)"></div>
                        <template x-if="item.subtitle">
                            <div class="text-xs text-muted-foreground truncate" x-text="item.subtitle"></div>
                        </template>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</template>

<template x-if="query.length > 0 && totalCount > 0">
    <a x-bind:href="'/search?keyword=' + encodeURIComponent(query)"
       class="block px-3 py-2 border-t border-border text-center text-xs font-medium text-foreground hover:bg-muted">
        View all results for &ldquo;<span x-text="query"></span>&rdquo;
    </a>
</template>
