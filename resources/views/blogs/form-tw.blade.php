{{-- Blog Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $blog->name ?? '')"
        placeholder="Enter blog name"
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
        :value="old('slug', $blog->slug ?? '')"
        placeholder="blog-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- Body --}}
<x-ui.form-group
    name="body"
    label="Body"
    :error="$errors->first('body')">
    <x-ui.textarea
        name="body"
        id="body"
        :value="old('body', $blog->body ?? '')"
        placeholder="Enter blog content"
        :hasError="$errors->has('body')"
        rows="10" />
</x-ui.form-group>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    {{-- Menu --}}
    <x-ui.form-group
        name="menu_id"
        label="Menu"
        :error="$errors->first('menu_id')">
        <x-ui.select
            name="menu_id"
            id="menu_id"
            :hasError="$errors->has('menu_id')">
            @foreach($menuOptions as $id => $name)
                <option value="{{ $id }}" {{ old('menu_id', $blog->menu_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.form-group>

    {{-- Content Type --}}
    <x-ui.form-group
        name="content_type_id"
        label="Content Type"
        :error="$errors->first('content_type_id')">
        <x-ui.select
            name="content_type_id"
            id="content_type_id"
            :hasError="$errors->has('content_type_id')">
            @foreach($contentTypeOptions as $id => $name)
                <option value="{{ $id }}" {{ old('content_type_id', isset($blog->contentType) ? $blog->contentType->id : '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
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
                <option value="{{ $id }}" {{ old('visibility_id', isset($blog->visibility) ? $blog->visibility->id : '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.form-group>
</div>

{{-- Sort Order --}}
<x-ui.form-group
    name="sort_order"
    label="Sort Order"
    :error="$errors->first('sort_order')">
    <x-ui.select
        name="sort_order"
        id="sort_order"
        :hasError="$errors->has('sort_order')">
        <option value="0" {{ old('sort_order', $blog->sort_order ?? 0) == 0 ? 'selected' : '' }}>Desc</option>
        <option value="1" {{ old('sort_order', $blog->sort_order ?? 0) == 1 ? 'selected' : '' }}>Asc</option>
    </x-ui.select>
</x-ui.form-group>

{{-- Related Entities --}}
<x-ui.form-group
    name="entity_list"
    label="Related Entities"
    :error="$errors->first('entities')"
    helpText="Choose related artists, producers, or DJs">
    <select
        name="entity_list[]"
        id="entity_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Choose a related artist, producer, dj"
        data-tags="false"
        multiple>
        @foreach($entityOptions as $id => $name)
            <option value="{{ $id }}" {{ in_array($id, old('entity_list', isset($blog) ? $blog->entities->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
</x-ui.form-group>

{{-- Tags --}}
<x-ui.form-group
    name="tag_list"
    label="Tags"
    :error="$errors->first('tags')"
    helpText="Add or create tags">
    <select
        name="tag_list[]"
        id="tag_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Choose a tag"
        data-tags="true"
        multiple>
        @foreach($tagOptions as $id => $name)
            <option value="{{ $id }}" {{ in_array($id, old('tag_list', isset($blog) ? $blog->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Blog' : 'Add Blog' }}
    </x-ui.button>
    <a href="{{ url('/blogs') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
