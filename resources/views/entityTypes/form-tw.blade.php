{{-- Entity Type Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $entityType->name ?? '')"
        placeholder="Enter entity type name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Slug --}}
<x-ui.form-group
    name="slug"
    label="Slug"
    :error="$errors->first('slug')"
    helpText="Unique URL-friendly identifier (auto-generated from name)">
    <x-ui.input
        type="text"
        name="slug"
        id="slug"
        :value="old('slug', $entityType->slug ?? '')"
        placeholder="entity-type-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Short Name --}}
<x-ui.form-group
    name="short"
    label="Short Name"
    :error="$errors->first('short')"
    helpText="Short abbreviation for this entity type">
    <x-ui.input
        type="text"
        name="short"
        id="short"
        :value="old('short', $entityType->short ?? '')"
        placeholder="Short name"
        :hasError="$errors->has('short')" />
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Entity Type' : 'Add Entity Type' }}
    </x-ui.button>
    <a href="{{ url('/entity-types') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
