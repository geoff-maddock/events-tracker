{{--
    A labelled form field. The control goes in the slot; an x-ui.input, select,
    textarea or checkbox there picks up the error, help text and required state
    of its group by itself (aria-invalid, aria-describedby, aria-required), as
    long as its id is the group's name. When the id differs, pass it as "for".
--}}
@props([
    'name' => '',
    'label' => '',
    'error' => null,
    'required' => false,
    'helpText' => null,
    // the id of the control, when it isn't the name
    'for' => null,
])
@php
    $for = $for ?: $name;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <x-ui.label :for="$for" :required="$required">
            {{ $label }}
        </x-ui.label>
    @endif

    <div>
        {{ $slot }}
    </div>

    @if($error)
        <p id="{{ $for }}-error" class="text-sm text-destructive">{{ $error }}</p>
    @endif

    @if($helpText && !$error)
        <p id="{{ $for }}-help" class="text-sm text-muted-foreground">{{ $helpText }}</p>
    @endif
</div>
