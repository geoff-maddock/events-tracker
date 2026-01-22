{{-- Link Text --}}
<x-ui.form-group
    name="text"
    label="Text"
    :error="$errors->first('text')"
    required>
    <x-ui.input
        type="text"
        name="text"
        id="text"
        :value="old('text', $link->text ?? '')"
        placeholder="Enter link text"
        :hasError="$errors->has('text')"
        autofocus />
</x-ui.form-group>

{{-- URL --}}
<x-ui.form-group
    name="url"
    label="URL"
    :error="$errors->first('url')"
    required>
    <x-ui.input
        type="url"
        name="url"
        id="url"
        :value="old('url', $link->url ?? '')"
        placeholder="https://example.com"
        :hasError="$errors->has('url')" />
</x-ui.form-group>

{{-- Title (hover text) --}}
<x-ui.form-group
    name="title"
    label="Title"
    :error="$errors->first('title')"
    helpText="Title text that will display when hovering over the link">
    <x-ui.input
        type="text"
        name="title"
        id="title"
        :value="old('title', $link->title ?? '')"
        placeholder="Title text that will display when hovering over URL"
        :hasError="$errors->has('title')" />
</x-ui.form-group>

{{-- Is Primary --}}
<div class="flex items-start gap-3">
    <x-ui.checkbox
        name="is_primary"
        id="is_primary"
        value="1"
        :checked="old('is_primary', isset($link) ? $link->is_primary : false)"
        :hasError="$errors->has('is_primary')" />
    <div class="flex-1">
        <x-ui.label for="is_primary">Is Primary</x-ui.label>
        <p class="text-xs text-muted-foreground">Mark this as the primary link</p>
        @if($errors->has('is_primary'))
            <span class="text-xs text-destructive">{{ $errors->first('is_primary') }}</span>
        @endif
    </div>
</div>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Link' : 'Add Link' }}
    </x-ui.button>
    <a href="{{ route('entities.show', $entity->slug) }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
