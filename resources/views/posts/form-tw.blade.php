{{-- Post Body --}}
<x-ui.form-group
    name="body"
    label="Body"
    :error="$errors->first('body')"
    required>
    <x-ui.textarea
        name="body"
        id="body"
        :hasError="$errors->has('body')"
        rows="6"
        placeholder="Write your post...">{{ old('body', $post->body ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Tags --}}
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
        data-tags="true"
        multiple
        :hasError="$errors->has('tags')">
        @foreach($tagOptions as $id => $name)
            <option value="{{ $id }}" {{ in_array($id, old('tag_list', $post->tags->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
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
            <option value="{{ $id }}" {{ old('visibility_id', $post->visibility_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Hidden Thread ID --}}
<input type="hidden" name="thread_id" value="{{ $post->thread_id ?? '' }}" id="{{ $post->thread_id ?? '' }}">

{{-- Submit Button --}}
<div class="flex items-center gap-3">
    <x-ui.button type="submit">
        {{ isset($action) && $action == 'update' ? 'Update Post' : 'Add Post' }}
    </x-ui.button>
    <a href="{{ isset($post->thread) ? route('threads.show', $post->thread) : route('threads.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
