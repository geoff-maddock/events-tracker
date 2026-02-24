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
        :value="old('name', $thread->name ?? '')"
        placeholder="Succinct title for your thread."
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Body --}}
<x-ui.form-group
    name="body"
    label="Body"
    :error="$errors->first('body')">
    <x-ui.textarea
        name="body"
        id="body"
        :hasError="$errors->has('body')"
        rows="6"
        placeholder="Enter your full message content here.  No HTML or Markdown.">{{ old('body', $thread->body ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Forum and Category --}}
<div class="grid grid-cols-12 gap-4">
<input type="hidden" name="forum_id" value="{{ old('forum_id', $thread->forum_id ?? 1) }}">
</div>

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
            <option value="{{ $id }}" {{ old('visibility_id', $thread->visibility_id ?? 3) == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Related Entities --}}
<x-ui.form-group
    name="entity_list"
    label="Related Entities"
    :error="$errors->first('entities')"
    helpText="Choose related artists, producers, DJs">
    <x-ui.select
        name="entity_list[]"
        id="entity_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Choose a related artist, producer, dj (optional)"
        data-tags="false"
        multiple
        :hasError="$errors->has('entities')">
        @foreach($entityOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </x-ui.select>
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
        data-placeholder="Choose a tag (optional)"
        data-tags="true"
        multiple
        :hasError="$errors->has('tags')">
        @foreach($tagOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-4 pt-4">
    <x-ui.button type="submit" variant="default">
        {{ isset($action) && $action == 'update' ? 'Update Thread' : 'Add Thread' }}
    </x-ui.button>
</div>
