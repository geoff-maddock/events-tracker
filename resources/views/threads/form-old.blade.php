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
        placeholder="Thread title"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="Description"
    :error="$errors->first('description')">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="2"
        placeholder="Brief description">{{ old('description', $thread->description ?? '') }}</x-ui.textarea>
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
        placeholder="Full thread content">{{ old('body', $thread->body ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Forum and Category --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="forum_id"
            label="Forum"
            :error="$errors->first('forum_id')">
            <x-ui.select
                name="forum_id"
                id="forum_id"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Select a forum"
                :hasError="$errors->has('forum_id')">
                <option value="">Select a forum</option>
                @foreach($forumOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('forum_id', $thread->forum_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="thread_category_id"
            label="Thread Category"
            :error="$errors->first('thread_category_id')">
            <x-ui.select
                name="thread_category_id"
                id="thread_category_id"
                :hasError="$errors->has('thread_category_id')">
                <option value="">Select category</option>
                @foreach($threadCategoryOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('thread_category_id', $thread->thread_category_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
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
        data-placeholder="Choose a related artist, producer, dj"
        data-tags="false"
        multiple
        :hasError="$errors->has('entities')">
        @foreach($entityOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Related Event --}}
<x-ui.form-group
    name="event_id"
    label="Event"
    :error="$errors->first('event_id')">
    <x-ui.select
        name="event_id"
        id="event_id"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Select an event"
        :hasError="$errors->has('event_id')">
        <option value="">Select an event</option>
        @foreach($eventOptions as $id => $name)
            <option value="{{ $id }}" {{ old('event_id', $thread->event_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Related Series --}}
<x-ui.form-group
    name="series_list"
    label="Related Series"
    :error="$errors->first('series')">
    <x-ui.select
        name="series_list[]"
        id="series_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Choose a related series"
        data-tags="true"
        multiple
        :hasError="$errors->has('series')">
        @foreach($seriesOptions as $id => $name)
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
        data-placeholder="Choose a tag"
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
