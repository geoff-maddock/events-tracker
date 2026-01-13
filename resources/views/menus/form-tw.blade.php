{{-- Menu Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $menu->name ?? '')"
        placeholder="Enter menu name"
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
        :value="old('slug', $menu->slug ?? '')"
        placeholder="menu-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Parent Menu --}}
<x-ui.form-group
    name="menu_parent_id"
    label="Parent Menu"
    :error="$errors->first('menu_parent_id')"
    helpText="Select a parent menu (leave empty for top-level menu)">
    <x-ui.select
        name="menu_parent_id"
        id="menu_parent_id"
        :hasError="$errors->has('menu_parent_id')">
        @foreach($menuOptions as $id => $name)
            <option value="{{ $id }}" {{ old('menu_parent_id', $menu->menu_parent_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Body --}}
<x-ui.form-group
    name="body"
    label="Body"
    :error="$errors->first('body')"
    helpText="Add a more in-depth description here">
    <x-ui.textarea
        name="body"
        id="body"
        :value="old('body', $menu->body ?? '')"
        placeholder="Enter menu description"
        :hasError="$errors->has('body')"
        rows="4" />
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
        @foreach($visibilityOptions as $id => $name)
            <option value="{{ $id }}" {{ old('visibility_id', isset($menu->visibility) ? $menu->visibility->id : '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Menu' : 'Add Menu' }}
    </x-ui.button>
    <a href="{{ url('/menus') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
