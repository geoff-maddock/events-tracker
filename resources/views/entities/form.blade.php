{{-- Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $entity->name ?? '')"
        placeholder="Entity name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Slug --}}
<x-ui.form-group
    name="slug"
    label="Slug"
    :error="$errors->first('slug')"
    helpText="Unique name for this entity (will validate)">
    <x-ui.input
        type="text"
        name="slug"
        id="slug"
        :value="old('slug', $entity->slug ?? '')"
        placeholder="unique-entity-name"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Short Description --}}
<x-ui.form-group
    name="short"
    label="Short Description"
    :error="$errors->first('short')">
    <x-ui.input
        type="text"
        name="short"
        id="short"
        :value="old('short', $entity->short ?? '')"
        placeholder="Add a brief description of this entity"
        :hasError="$errors->has('short')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="In Depth"
    :error="$errors->first('description')"
    helpText="Add a more in depth description here">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="4"
        placeholder="Add a more in depth description here">{{ old('description', $entity->description ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Entity Type and Status --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="entity_type_id"
            label="Type"
            :error="$errors->first('entity_type_id')">
            <x-ui.select
                name="entity_type_id"
                id="entity_type_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('entity_type_id')">
                <option value="">Select type</option>
                @foreach($entityTypeOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('entity_type_id', $entity->entity_type_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="entity_status_id"
            label="Status"
            :error="$errors->first('entity_status_id')">
            <x-ui.select
                name="entity_status_id"
                id="entity_status_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('entity_status_id')">
                <option value="">Select status</option>
                @foreach($entityStatusOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('entity_status_id', $entity->entity_status_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Social Media Usernames --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="facebook_username"
            label="FB Username"
            :error="$errors->first('facebook_username')">
            <x-ui.input
                type="text"
                name="facebook_username"
                id="facebook_username"
                :value="old('facebook_username', $entity->facebook_username ?? '')"
                placeholder="Add the related facebook username if there is one"
                :hasError="$errors->has('facebook_username')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="twitter_username"
            label="Twitter Username"
            :error="$errors->first('twitter_username')">
            <x-ui.input
                type="text"
                name="twitter_username"
                id="twitter_username"
                :value="old('twitter_username', $entity->twitter_username ?? '')"
                placeholder="Add the related twitter username if there is one"
                :hasError="$errors->has('twitter_username')" />
        </x-ui.form-group>
    </div>
</div>

<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="instagram_username"
            label="Instagram Username"
            :error="$errors->first('instagram_username')">
            <x-ui.input
                type="text"
                name="instagram_username"
                id="instagram_username"
                :value="old('instagram_username', $entity->instagram_username ?? '')"
                placeholder="Add the related instagram username if there is one"
                :hasError="$errors->has('instagram_username')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="started_at"
            label="Started At"
            :error="$errors->first('started_at')">
            <x-ui.input
                type="datetime-local"
                name="started_at"
                id="started_at"
                :value="old('started_at', isset($entity->started_at) ? $entity->started_at->format('Y-m-d\TH:i') : '')"
                :hasError="$errors->has('started_at')" />
        </x-ui.form-group>
    </div>
</div>

{{-- Roles and Tags --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="role_list"
            label="Roles"
            :error="$errors->first('roles')">
            <x-ui.select
                name="role_list[]"
                id="role_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Choose a role"
                data-tags="false"
                multiple
                :hasError="$errors->has('roles')">
                @foreach($roleOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="tag_list"
            label="Tags"
            :error="$errors->first('tags')">
            <x-ui.select
                name="tag_list[]"
                id="tag_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Choose a tag"
                data-tags="false"
                multiple
                :hasError="$errors->has('tags')">
                @foreach($tagOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Aliases --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="alias_list"
            label="Aliases"
            :error="$errors->first('aliases')">
            <x-ui.select
                name="alias_list[]"
                id="alias_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Choose an alias"
                data-tags="true"
                multiple
                :hasError="$errors->has('aliases')">
                @foreach($aliasOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="created_by"
            label="Owner"
            :error="$errors->first('created_by')">
            <x-ui.select
                name="created_by"
                id="created_by"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('created_by')">
                <option value="">Select owner</option>
                @foreach($userOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('created_by', $entity->created_by ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Submit Button --}}
<div class="flex items-center gap-4 pt-4">
    <x-ui.button type="submit" variant="default">
        {{ isset($action) && $action == 'update' ? 'Update Entity' : 'Add Entity' }}
    </x-ui.button>
</div>
