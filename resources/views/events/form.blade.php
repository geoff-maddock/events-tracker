{{-- Event Name and Slug --}}
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
                :value="old('name', $event->name ?? '')"
                placeholder="Use a clear, simple and descriptive event title"
                :hasError="$errors->has('name')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="slug"
            label="Slug"
            :error="$errors->first('slug')"
            helpText="Unique name for this event (will validate)">
            <x-ui.input
                type="text"
                name="slug"
                id="slug"
                :value="old('slug', $event->slug ?? '')"
                placeholder="unique-event-name"
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
        :value="old('short', $event->short ?? '')"
        placeholder="A concise one-sentence description of the event"
        :hasError="$errors->has('short')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="Description"
    :error="$errors->first('description')"
    helpText="Detailed description of the event including all relevant info not in other fields">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="4"
        placeholder="Detailed description of the event including all relevant info not in other fields">{{ old('description', $event->description ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Visibility, Event Type, Promoter, Venue --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="visibility_id"
            label="Visibility"
            :error="$errors->first('visibility_id')"
            required>
            <x-ui.select
                name="visibility_id"
                id="visibility_id"
                :hasError="$errors->has('visibility_id')">
                @foreach($visibilityOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('visibility_id', $event->visibility->id ?? 3) == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="event_type_id"
            label="Event Type"
            :error="$errors->first('event_type_id')"
            required>
            <x-ui.select
                name="event_type_id"
                id="event_type_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('event_type_id')">
                <option value="">Select event type</option>
                @foreach($eventTypeOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('event_type_id', $event->event_type_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="promoter_id"
            label="Promoter"
            :error="$errors->first('promoter_id')">
            <x-ui.select
                name="promoter_id"
                id="promoter_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('promoter_id')">
                <option value="">Select promoter</option>
                @foreach($promoterOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('promoter_id', $event->promoter_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="venue_id"
            label="Venue"
            :error="$errors->first('venue_id')">
            <x-ui.select
                name="venue_id"
                id="venue_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('venue_id')">
                <option value="">Select venue</option>
                @foreach($venueOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('venue_id', $event->venue_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>


{{-- Start, End, Cancelled Times --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="start_at"
            label="Start At"
            :error="$errors->first('start_at')"
            required>
            <template x-slot:label>
                <div class="flex items-center justify-between">
                    <x-ui.label for="start_at" required>Start At</x-ui.label>
                    <button type="button" class="text-muted-foreground hover:text-foreground" title="Show additional time options" data-bs-toggle="collapse" data-bs-target="#form-time">
                        <i class="bi bi-clock-fill"></i>
                    </button>
                </div>
            </template>
            <x-ui.datetime-picker
                name="start_at"
                id="start_at"
                :value="old('start_at', isset($event->start_at) ? $event->start_at->format('Y-m-d H:i') : '')"
                :hasError="$errors->has('start_at')"
                placeholder="Select start date and time" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="end_at"
            label="End At"
            :error="$errors->first('end_at')">
            <x-ui.datetime-picker
                name="end_at"
                id="end_at"
                :value="old('end_at', isset($event->end_at) ? $event->end_at->format('Y-m-d H:i') : '')"
                :hasError="$errors->has('end_at')"
                placeholder="Select end date and time" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="cancelled_at"
            label="Cancelled At"
            :error="$errors->first('cancelled_at')">
            <x-ui.datetime-picker
                name="cancelled_at"
                id="cancelled_at"
                :value="old('cancelled_at', isset($event->cancelled_at) ? $event->cancelled_at->format('Y-m-d H:i') : '')"
                :hasError="$errors->has('cancelled_at')"
                placeholder="Select cancellation date" />
        </x-ui.form-group>
    </div>
</div>

{{-- Pricing and Age --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="presale_price"
            label="Presale Price"
            :error="$errors->first('presale_price')">
            <x-ui.input
                type="text"
                name="presale_price"
                id="presale_price"
                :value="old('presale_price', $event->presale_price ?? '')"
                placeholder="0.00"
                :hasError="$errors->has('presale_price')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="door_price"
            label="Door Price"
            :error="$errors->first('door_price')">
            <x-ui.input
                type="text"
                name="door_price"
                id="door_price"
                :value="old('door_price', $event->door_price ?? '')"
                placeholder="0.00"
                :hasError="$errors->has('door_price')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="min_age"
            label="Min Age"
            :error="$errors->first('min_age')">
            <x-ui.select
                name="min_age"
                id="min_age"
                :hasError="$errors->has('min_age')">
                <option value="0" {{ old('min_age', $event->min_age ?? '0') == '0' ? 'selected' : '' }}>All Ages</option>
                <option value="18" {{ old('min_age', $event->min_age ?? '') == '18' ? 'selected' : '' }}>18+</option>
                <option value="21" {{ old('min_age', $event->min_age ?? '') == '21' ? 'selected' : '' }}>21+</option>
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Primary Link and Ticket Link --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="primary_link"
            label="Primary Link"
            :error="$errors->first('primary_link')"
            helpText="Primary link to the event on the web, if one exists (not required)">
            <x-ui.input
                type="url"
                name="primary_link"
                id="primary_link"
                :value="old('primary_link', $event->primary_link ?? '')"
                placeholder="https://example.com/event"
                :hasError="$errors->has('primary_link')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="ticket_link"
            label="Ticket Link"
            :error="$errors->first('ticket_link')">
            <x-ui.input
                type="url"
                name="ticket_link"
                id="ticket_link"
                :value="old('ticket_link', $event->ticket_link ?? '')"
                placeholder="https://tickets.example.com/event"
                :hasError="$errors->has('ticket_link')" />
        </x-ui.form-group>
    </div>
</div>

{{-- Series --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="series_id"
            label="Series"
            :error="$errors->first('series_id')"
            helpText="Link to an existing event series">
            <x-ui.select
                name="series_id"
                id="series_id"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Link to an existing event series"
                :hasError="$errors->has('series_id')">
                <option value="">No series</option>
                @foreach($seriesOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('series_id', $event->series_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
        <div class="flex items-center justify-end gap-2 text-sm text-muted-foreground mt-1">
            <a href="/series/create" target="_blank" class="hover:text-foreground inline-flex items-center gap-1">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Event Series
            </a>
            <button type="button" class="hover:text-foreground" title="Add your event as a series if it's occurring on an ongoing basis (weekly, monthly, etc.)">
                <i class="bi bi-question-octagon-fill"></i>
            </button>
        </div>
    </div>
</div>

{{-- Related Entities and Tags --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="entity_list"
            label="Related Entities"
            :error="$errors->first('entities')">
            <select
                name="entity_list[]"
                id="entity_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Choose related artists, producers, djs, bands, etc."
                data-tags="false"
                multiple>
                @foreach($entityOptions as $id => $name)
                    <option value="{{ $id }}" {{ in_array($id, old('entity_list', isset($event) ? $event->entities->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </x-ui.form-group>
        <div class="flex items-center justify-end gap-2 text-sm text-muted-foreground mt-1">
            <a href="/entities/create" target="_blank" class="hover:text-foreground inline-flex items-center gap-1">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Entity
            </a>
            <button type="button" class="hover:text-foreground" title="Add an entity to create a link between your event and performers, venues, promoters, etc. that is missing from the list.">
                <i class="bi bi-question-octagon-fill"></i>
            </button>
        </div>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="tag_list"
            label="Tags"
            :error="$errors->first('tags')">
            <select
                name="tag_list[]"
                id="tag_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Choose a keyword tag that describes this event"
                data-maximum-selection-length="10"
                data-tags="false"
                multiple>
                @foreach($tagOptions as $id => $name)
                    <option value="{{ $id }}" {{ in_array($id, old('tag_list', isset($event) ? $event->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </x-ui.form-group>
        <div class="flex items-center justify-end gap-2 text-sm text-muted-foreground mt-1">
            <a href="/tags/create" target="_blank" class="hover:text-foreground inline-flex items-center gap-1">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Keyword Tag
            </a>
            <button type="button" class="hover:text-foreground" title="Add a keyword if the genre or category of your event is missing from the existing keyword tag list.">
                <i class="bi bi-question-octagon-fill"></i>
            </button>
        </div>
    </div>
</div>

{{-- Owner, Do Not Repost --}}
<div class="grid grid-cols-12 gap-4">
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
                    <option value="{{ $id }}" {{ old('created_by', $event->created_by ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <div class="flex items-center h-9 mt-8">
            <input type="hidden" name="do_not_repost" value="0">
            <input
                type="checkbox"
                name="do_not_repost"
                id="do_not_repost"
                value="1"
                {{ old('do_not_repost', $event->do_not_repost ?? false) ? 'checked' : '' }}
                class="h-4 w-4 rounded border-input text-primary focus:ring-ring">
            <label for="do_not_repost" class="ml-2 text-sm text-foreground cursor-pointer">
                Do not repost on socials
            </label>
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="flex items-center gap-4 pt-4">
    <x-ui.button type="submit" variant="default">
        {{ isset($action) && $action == 'update' ? 'Update Event' : 'Add Event' }}
    </x-ui.button>
</div>

@section('footer')
<script>
    // Initialize Select2 with Tailwind theme
    $(document).ready(function() {
        $('#event_type_id, #venue_id, #promoter_id, #series_id, #created_by').select2({
            theme: 'tailwind',
            width: '100%'
        });

        $('#entity_list').select2({
            theme: 'tailwind',
            width: '100%',
            placeholder: 'Choose related artists, producers, djs, bands, etc.',
            tags: false
        });

        $('#tag_list').select2({
            theme: 'tailwind',
            width: '100%',
            placeholder: 'Choose a keyword tag that describes this event',
            maximumSelectionLength: 10,
            tags: false
        });
    });
</script>
@endsection
