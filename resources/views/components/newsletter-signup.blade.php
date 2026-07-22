@props(['source' => 'site'])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-card/50 p-4']) }}>
    <h3 class="font-semibold text-foreground mb-1">
        <i class="bi bi-envelope-heart text-primary mr-1" aria-hidden="true"></i>
        Essential Events, weekly
    </h3>
    <p class="text-sm text-muted-foreground mb-3">
        A curated digest of the events you shouldn't miss, in your inbox every Monday. No account required.
    </p>
    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex flex-col sm:flex-row gap-2">
        @csrf
        <input type="hidden" name="source" value="{{ $source }}">
        {{-- honeypot - hidden from humans, bots fill it and get silently dropped --}}
        <div class="hidden" aria-hidden="true">
            <label>Leave this field empty
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>
        </div>
        <x-ui.input
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="you@example.com"
            required
            aria-label="Email address"
            class="sm:max-w-xs"
            :hasError="$errors->has('email')" />
        <x-ui.button type="submit">Subscribe</x-ui.button>
    </form>
    @error('email')
    <p class="text-sm text-destructive mt-1">{{ $message }}</p>
    @enderror
</div>
