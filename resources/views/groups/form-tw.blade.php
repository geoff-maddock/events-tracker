{{-- Group Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $group->name ?? '')"
        placeholder="Enter group name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Label --}}
<x-ui.form-group
    name="label"
    label="Label"
    :error="$errors->first('label')"
    helpText="Descriptive label for the group">
    <x-ui.input
        type="text"
        name="label"
        id="label"
        :value="old('label', $group->label ?? '')"
        placeholder="Descriptive label for the group"
        :hasError="$errors->has('label')" />
</x-ui.form-group>

{{-- Level --}}
<x-ui.form-group
    name="level"
    label="Level"
    :error="$errors->first('level')"
    helpText="Access level for this group">
    <x-ui.input
        type="text"
        name="level"
        id="level"
        :value="old('level', $group->level ?? '')"
        placeholder="Add the corresponding access level"
        :hasError="$errors->has('level')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="In Depth"
    :error="$errors->first('description')"
    helpText="More in-depth description of this group">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="4"
        placeholder="Add a more in depth description here">{{ old('description', $group->description ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Permissions --}}
<x-ui.form-group
    name="permission_list"
    label="Permissions"
    :error="$errors->first('permissions')">
    <x-ui.select
        name="permission_list[]"
        id="permission_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Select related permissions"
        data-tags="false"
        multiple
        :hasError="$errors->has('permissions')">
        @foreach($permissionOptions as $id => $name)
            <option value="{{ $id }}" {{ in_array($id, old('permission_list', isset($group) ? $group->permissions->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Users --}}
<x-ui.form-group
    name="user_list"
    label="Users"
    :error="$errors->first('users')">
    <x-ui.select
        name="user_list[]"
        id="user_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Select related users"
        data-tags="false"
        multiple
        :hasError="$errors->has('users')">
        @foreach($userOptions as $id => $name)
            <option value="{{ $id }}" {{ in_array($id, old('user_list', isset($group) ? $group->users->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Group' : 'Add Group' }}
    </x-ui.button>
    <a href="{{ route('groups.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
