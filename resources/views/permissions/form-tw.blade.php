{{-- Permission Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $permission->name ?? '')"
        placeholder="Enter permission name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Label --}}
<x-ui.form-group
    name="label"
    label="Label"
    :error="$errors->first('label')"
    helpText="Descriptive label for the permission">
    <x-ui.input
        type="text"
        name="label"
        id="label"
        :value="old('label', $permission->label ?? '')"
        placeholder="Descriptive label"
        :hasError="$errors->has('label')" />
</x-ui.form-group>

{{-- Level --}}
<x-ui.form-group
    name="level"
    label="Level"
    :error="$errors->first('level')"
    helpText="Corresponding access level">
    <x-ui.input
        type="number"
        name="level"
        id="level"
        :value="old('level', $permission->level ?? '')"
        placeholder="Access level"
        :hasError="$errors->has('level')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="In Depth Description"
    :error="$errors->first('description')">
    <x-ui.textarea
        name="description"
        id="description"
        :value="old('description', $permission->description ?? '')"
        placeholder="Add a more in-depth description here"
        :hasError="$errors->has('description')"
        rows="4" />
</x-ui.form-group>

{{-- Groups --}}
<x-ui.form-group
    name="group_list"
    label="Groups"
    :error="$errors->first('groups')"
    helpText="Assign this permission to groups">
    <select
        name="group_list[]"
        id="group_list"
        class="form-control select2 {{ $errors->has('groups') ? 'border-destructive' : '' }}"
        data-placeholder="Choose a group"
        data-tags="true"
        multiple>
        @foreach($groupOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Permission' : 'Add Permission' }}
    </x-ui.button>
    <a href="{{ url('/permissions') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
