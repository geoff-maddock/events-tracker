{{-- Name and Slug --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
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
    </div>

    <div class="col-span-12 md:col-span-6">
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
    </div>
</div>

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

{{-- Possible duplicate warning: an entity of the same type whose name or alias matches --}}
<div id="entity-duplicate-warning"
     class="hidden rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 p-4 text-sm"
     role="alert">
    <div class="flex items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0"></i>
        <div class="min-w-0">
            <p class="font-medium text-amber-800 dark:text-amber-300 mb-2">
                An entity of this type with a matching name or alias already exists:
            </p>
            <ul id="entity-duplicate-list" class="space-y-1 text-amber-700 dark:text-amber-400 list-disc list-inside"></ul>
            <p class="mt-2 text-amber-600 dark:text-amber-500 text-xs">
                If this is the same entity, edit it instead of creating a new one. If it is genuinely different, you can still submit this form.
            </p>
        </div>
    </div>
</div>

{{-- Started At, FB Username, and Instagram Username --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
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

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="facebook_username"
            label="FB Username"
            :error="$errors->first('facebook_username')">
            <x-ui.input
                type="text"
                name="facebook_username"
                id="facebook_username"
                :value="old('facebook_username', $entity->facebook_username ?? '')"
                placeholder="Add the facebook username"
                :hasError="$errors->has('facebook_username')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="instagram_username"
            label="Instagram Username"
            :error="$errors->first('instagram_username')">
            <x-ui.input
                type="text"
                name="instagram_username"
                id="instagram_username"
                :value="old('instagram_username', $entity->instagram_username ?? '')"
                placeholder="Add the instagram username"
                :hasError="$errors->has('instagram_username')" />
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
                    <option value="{{ $id }}" {{ in_array($id, old('role_list', isset($entity) ? $entity->roles->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $name }}</option>
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
                    <option value="{{ $id }}" {{ in_array($id, old('tag_list', isset($entity) ? $entity->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $name }}</option>
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
                    <option value="{{ $id }}" {{ in_array($id, old('alias_list', isset($entity) ? $entity->aliases->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $name }}</option>
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

@section('footer')
<script>
// Duplicate check: warn when an entity of the selected type already has this
// name or alias, so aliases don't get re-created as separate entities.
(function () {
    const nameInput = document.getElementById('name');
    const typeSelect = document.getElementById('entity_type_id');
    const warning = document.getElementById('entity-duplicate-warning');
    const list = document.getElementById('entity-duplicate-list');
    if (!nameInput || !typeSelect || !warning || !list) return;

    const checkUrl = '{{ route('entities.quickCheck') }}';
    const excludeId = {{ (int) ($entity->id ?? 0) }};
    let timer = null;
    let seq = 0;

    function hide() {
        warning.classList.add('hidden');
        list.textContent = '';
    }

    function render(matches) {
        hide();
        if (!matches.length) return;
        matches.forEach(function (match) {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '/entities/' + encodeURIComponent(match.slug);
            a.target = '_blank';
            a.rel = 'noopener';
            a.className = 'underline hover:no-underline';
            a.textContent = match.name;
            li.appendChild(a);
            if (match.entity_type) {
                li.appendChild(document.createTextNode(' (' + match.entity_type + ')'));
            }
            if (match.alias) {
                li.appendChild(document.createTextNode(' \u2014 matched alias \u201c' + match.alias + '\u201d'));
            }
            list.appendChild(li);
        });
        warning.classList.remove('hidden');
    }

    function check() {
        clearTimeout(timer);
        const name = nameInput.value.trim();
        const typeId = typeSelect.value;
        if (name.length < 3 || !typeId) {
            hide();
            return;
        }
        timer = setTimeout(function () {
            const current = ++seq;
            const params = new URLSearchParams({ name: name, entity_type_id: typeId });
            if (excludeId) params.set('exclude_id', excludeId);
            fetch(checkUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.ok ? response.json() : { data: [] }; })
                .then(function (result) {
                    // Ignore out-of-order responses from earlier keystrokes
                    if (current === seq) render(result.data || []);
                })
                .catch(function () { /* advisory only; ignore failures */ });
        }, 400);
    }

    nameInput.addEventListener('input', check);
    nameInput.addEventListener('change', check);
    // Select2 fires change through jQuery, which native listeners don't see.
    $(typeSelect).on('change', check);

    // Re-check when a validation round-trip repopulates the form.
    if (nameInput.value.trim().length >= 3 && typeSelect.value) check();
})();
</script>
@endsection
