{{--
    The "nothing to show" message of a list or a page section.

        <x-empty-state icon="bi-calendar-x">No matching events found.</x-empty-state>

    icon     string|null  a Bootstrap Icons class, shown above the message
    boxed    bool         draw it as a card (for a list that sits straight on the page)
    compact  bool         less padding, for a section inside a card or a column

    Default slot: the message. `actions` slot: links offered below it.
--}}
@props(['icon' => null, 'boxed' => false, 'compact' => false])
<div {{ $attributes->class(['text-center', $compact ? 'py-8' : 'py-12', 'px-4 bg-card rounded-lg border border-border' => $boxed]) }}>
    @if ($icon)
    <i class="bi {{ $icon }} text-4xl text-muted-foreground/50 mb-3 block" aria-hidden="true"></i>
    @endif
    <p class="text-muted-foreground">{{ $slot }}</p>
    @isset($actions)
    <div class="mt-4">{{ $actions }}</div>
    @endisset
</div>
