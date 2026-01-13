{{-- Category Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $category->name ?? '')"
        placeholder="Enter category name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Forum --}}
<x-ui.form-group
    name="forum_id"
    label="Forum"
    :error="$errors->first('forum_id')">
    <x-ui.select
        name="forum_id"
        id="forum_id"
        :hasError="$errors->has('forum_id')">
        <option value="">Select forum</option>
        @foreach($forumOptions as $id => $name)
            <option value="{{ $id }}" {{ old('forum_id', $category->forum_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Category' : 'Add Category' }}
    </x-ui.button>
    <a href="{{ route('categories.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
