{{-- Tag Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $tag->name ?? '')"
        placeholder="Enter tag name"
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
        :value="old('slug', $tag->slug ?? '')"
        placeholder="tag-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="Description"
    :error="$errors->first('description')"
    helpText="Optional description for this tag">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="3"
        placeholder="Enter a description for this tag">{{ old('description', $tag->description ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Tag Type --}}
<x-ui.form-group
    name="tag_type_id"
    label="Type"
    :error="$errors->first('tag_type_id')">
    <x-ui.select
        name="tag_type_id"
        id="tag_type_id"
        class="select2"
        data-theme="tailwind"
        :hasError="$errors->has('tag_type_id')">
        <option value="">Select tag type</option>
        @foreach($tagTypes as $id => $name)
            <option value="{{ $id }}" {{ old('tag_type_id', $tag->tag_type_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Tag' : 'Add Tag' }}
    </x-ui.button>
    <a href="{{ route('tags.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
