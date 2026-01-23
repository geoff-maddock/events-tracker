@props([
    'name' => '',
    'label' => '',
    'error' => null,
    'required' => false,
    'helpText' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if($label)
        <x-ui.label :for="$name" :required="$required">
            {{ $label }}
        </x-ui.label>
    @endif

    <div>
        {{ $slot }}
    </div>

    @if($error)
        <p class="text-sm text-destructive">{{ $error }}</p>
    @endif

    @if($helpText && !$error)
        <p class="text-sm text-muted-foreground">{{ $helpText }}</p>
    @endif
</div>
