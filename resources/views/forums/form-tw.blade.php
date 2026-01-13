{{-- Forum Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $forum->name ?? '')"
        placeholder="Enter forum name"
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
        :value="old('slug', $forum->slug ?? '')"
        placeholder="forum-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="Description"
    :error="$errors->first('description')">
    <x-ui.textarea
        name="description"
        id="description"
        :value="old('description', $forum->description ?? '')"
        placeholder="Enter forum description"
        :hasError="$errors->has('description')"
        rows="3" />
</x-ui.form-group>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Sort Order --}}
    <x-ui.form-group
        name="sort_order"
        label="Sort Order"
        :error="$errors->first('sort_order')"
        helpText="Order in which forum appears">
        <x-ui.input
            type="number"
            name="sort_order"
            id="sort_order"
            :value="old('sort_order', $forum->sort_order ?? 0)"
            placeholder="0"
            :hasError="$errors->has('sort_order')" />
    </x-ui.form-group>

    {{-- Visibility --}}
    <x-ui.form-group
        name="visibility_id"
        label="Visibility"
        :error="$errors->first('visibility_id')">
        <x-ui.select
            name="visibility_id"
            id="visibility_id"
            :hasError="$errors->has('visibility_id')">
            @foreach($visibilities as $id => $name)
                <option value="{{ $id }}" {{ old('visibility_id', $forum->visibility_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.form-group>
</div>

{{-- Active Checkbox --}}
<x-ui.form-group
    name="is_active"
    label="Active"
    :error="$errors->first('is_active')">
    <div class="flex items-center gap-2">
        <x-ui.checkbox
            name="is_active"
            id="is_active"
            value="1"
            :checked="old('is_active', $forum->is_active ?? false)"
            :hasError="$errors->has('is_active')" />
        <label for="is_active" class="text-sm text-foreground cursor-pointer">
            Forum is active and visible
        </label>
    </div>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Forum' : 'Add Forum' }}
    </x-ui.button>
    <a href="{{ url('/forums') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
