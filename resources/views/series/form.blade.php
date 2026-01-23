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
        :value="old('name', $series->name ?? '')"
        placeholder="Use a clear, simple and descriptive series title"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Slug --}}
<x-ui.form-group
    name="slug"
    label="Slug"
    :error="$errors->first('slug')"
    helpText="Unique name for this series (will validate)">
    <x-ui.input
        type="text"
        name="slug"
        id="slug"
        :value="old('slug', $series->slug ?? '')"
        placeholder="unique-series-name"
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
        :value="old('short', $series->short ?? '')"
        placeholder="A concise one-sentence description of the series"
        :hasError="$errors->has('short')" />
</x-ui.form-group>

{{-- Description --}}
<x-ui.form-group
    name="description"
    label="Description"
    :error="$errors->first('description')"
    helpText="Detailed description of the series including all relevant info not in other fields">
    <x-ui.textarea
        name="description"
        id="description"
        :hasError="$errors->has('description')"
        rows="4"
        placeholder="Detailed description of the series including all relevant info not in other fields">{{ old('description', $series->description ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Founded At and Cancelled At --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="founded_at"
            label="Founded At"
            :error="$errors->first('founded_at')">
            <x-ui.input
                type="datetime-local"
                name="founded_at"
                id="founded_at"
                :value="old('founded_at', isset($series->founded_at) ? $series->founded_at->format('Y-m-d\TH:i') : '')"
                :hasError="$errors->has('founded_at')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="cancelled_at"
            label="Cancelled At"
            :error="$errors->first('cancelled_at')">
            <x-ui.input
                type="datetime-local"
                name="cancelled_at"
                id="cancelled_at"
                :value="old('cancelled_at', isset($series->cancelled_at) ? $series->cancelled_at->format('Y-m-d\TH:i') : '')"
                :hasError="$errors->has('cancelled_at')" />
        </x-ui.form-group>
    </div>
</div>

{{-- Occurrence Pattern --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-3">
        <x-ui.form-group
            name="occurrence_type_id"
            label="Occurrence Type"
            :error="$errors->first('occurrence_type_id')">
            <x-ui.select
                name="occurrence_type_id"
                id="occurrence_type_id"
                :hasError="$errors->has('occurrence_type_id')">
                <option value="">Select type</option>
                @foreach($occurrenceTypeOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('occurrence_type_id', $series->occurrence_type_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-3">
        <x-ui.form-group
            name="occurrence_week_id"
            label="Occurrence Week"
            :error="$errors->first('occurrence_week_id')">
            <x-ui.select
                name="occurrence_week_id"
                id="occurrence_week_id"
                :hasError="$errors->has('occurrence_week_id')">
                <option value="">Select week</option>
                @foreach($weekOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('occurrence_week_id', $series->occurrence_week_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-3">
        <x-ui.form-group
            name="occurrence_day_id"
            label="Occurrence Day"
            :error="$errors->first('occurrence_day_id')">
            <x-ui.select
                name="occurrence_day_id"
                id="occurrence_day_id"
                :hasError="$errors->has('occurrence_day_id')">
                <option value="">Select day</option>
                @foreach($dayOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('occurrence_day_id', $series->occurrence_day_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-3">
        <x-ui.form-group
            name="hold_date"
            label="Hold Date"
            :error="$errors->first('hold_date')">
            <div class="flex items-center h-9">
                <input type="checkbox"
                    name="hold_date"
                    id="hold_date"
                    value="1"
                    {{ old('hold_date', $series->hold_date ?? false) ? 'checked' : '' }}
                    class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring focus:ring-2">
                <label for="hold_date" class="ml-2 text-sm text-foreground">
                    Hold date
                </label>
            </div>
        </x-ui.form-group>
    </div>
</div>

{{-- Event Type, Venue, Promoter --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="event_type_id"
            label="Event Type"
            :error="$errors->first('event_type_id')">
            <x-ui.select
                name="event_type_id"
                id="event_type_id"
                class="select2"
                data-theme="tailwind"
                :hasError="$errors->has('event_type_id')">
                <option value="">Select event type</option>
                @foreach($eventTypeOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('event_type_id', $series->event_type_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
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
                    <option value="{{ $id }}" {{ old('venue_id', $series->venue_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
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
                    <option value="{{ $id }}" {{ old('promoter_id', $series->promoter_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Additional Time Fields (Collapsible) --}}
<div class="collapse @if(isset($series->soundcheck_at) || isset($series->door_at)) show @endif" id="form-time">
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
            <x-ui.form-group
                name="soundcheck_at"
                label="Soundcheck At"
                :error="$errors->first('soundcheck_at')">
                <x-ui.input
                    type="datetime-local"
                    name="soundcheck_at"
                    id="soundcheck_at"
                    :value="old('soundcheck_at', isset($series->soundcheck_at) ? $series->soundcheck_at->format('Y-m-d\TH:i') : '')"
                    :hasError="$errors->has('soundcheck_at')" />
            </x-ui.form-group>
        </div>

        <div class="col-span-12 md:col-span-6">
            <x-ui.form-group
                name="door_at"
                label="Door At"
                :error="$errors->first('door_at')">
                <x-ui.input
                    type="datetime-local"
                    name="door_at"
                    id="door_at"
                    :value="old('door_at', isset($series->door_at) ? $series->door_at->format('Y-m-d\TH:i') : '')"
                    :hasError="$errors->has('door_at')" />
            </x-ui.form-group>
        </div>
    </div>
</div>

{{-- Start, End, Length --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="start_at"
            label="Start At"
            :error="$errors->first('start_at')">
            <template x-slot:label>
                <div class="flex items-center justify-between">
                    <x-ui.label for="start_at">Start At</x-ui.label>
                    <button type="button" class="text-muted-foreground hover:text-foreground" title="Show additional time options" data-bs-toggle="collapse" data-bs-target="#form-time">
                        <i class="bi bi-clock-fill"></i>
                    </button>
                </div>
            </template>
            <x-ui.input
                type="datetime-local"
                name="start_at"
                id="start_at"
                :value="old('start_at', isset($series->start_at) ? $series->start_at->format('Y-m-d\TH:i') : '')"
                :hasError="$errors->has('start_at')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="end_at"
            label="End At"
            :error="$errors->first('end_at')">
            <x-ui.input
                type="datetime-local"
                name="end_at"
                id="end_at"
                :value="old('end_at', isset($series->end_at) ? $series->end_at->format('Y-m-d\TH:i') : '')"
                :hasError="$errors->has('end_at')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="length"
            label="Length (hours)"
            :error="$errors->first('length')">
            <x-ui.input
                type="text"
                name="length"
                id="length"
                :value="old('length', isset($series) && $series->exists ? $series->getRawOriginal('length') : '')"
                placeholder="2.5"
                :hasError="$errors->has('length')" />
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
                :value="old('presale_price', $series->presale_price ?? '')"
                placeholder="10.00"
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
                :value="old('door_price', $series->door_price ?? '')"
                placeholder="15.00"
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
                <option value="0" {{ old('min_age', $series->min_age ?? '0') == '0' ? 'selected' : '' }}>All Ages</option>
                <option value="18" {{ old('min_age', $series->min_age ?? '') == '18' ? 'selected' : '' }}>18</option>
                <option value="21" {{ old('min_age', $series->min_age ?? '') == '21' ? 'selected' : '' }}>21</option>
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Links --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="primary_link"
            label="Primary Link"
            :error="$errors->first('primary_link')">
            <x-ui.input
                type="url"
                name="primary_link"
                id="primary_link"
                :value="old('primary_link', $series->primary_link ?? '')"
                placeholder="https://primarylink.com"
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
                :value="old('ticket_link', $series->ticket_link ?? '')"
                placeholder="https://ticketlink.com"
                :hasError="$errors->has('ticket_link')" />
        </x-ui.form-group>
    </div>
</div>

{{-- Social Media --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="facebook_username"
            label="Facebook Username"
            :error="$errors->first('facebook_username')">
            <x-ui.input
                type="text"
                name="facebook_username"
                id="facebook_username"
                :value="old('facebook_username', $series->facebook_username ?? '')"
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
                :value="old('instagram_username', $series->instagram_username ?? '')"
                :hasError="$errors->has('instagram_username')" />
        </x-ui.form-group>
    </div>

    <div class="col-span-12 md:col-span-4">
        <x-ui.form-group
            name="twitter_username"
            label="Twitter Username"
            :error="$errors->first('twitter_username')">
            <x-ui.input
                type="text"
                name="twitter_username"
                id="twitter_username"
                :value="old('twitter_username', $series->twitter_username ?? '')"
                :hasError="$errors->has('twitter_username')" />
        </x-ui.form-group>
    </div>
</div>

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

{{-- Tags --}}
<x-ui.form-group
    name="tag_list"
    label="Tags"
    :error="$errors->first('tags')"
    helpText="Choose keywords that describe this event series">
    <x-ui.select
        name="tag_list[]"
        id="tag_list"
        class="select2"
        data-theme="tailwind"
        data-placeholder="Choose a keyword tag that describes this event series"
        data-tags="false"
        multiple
        :hasError="$errors->has('tags')">
        @foreach($tagOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Visibility and Owner --}}
<div class="grid grid-cols-12 gap-4">
    <div class="col-span-12 md:col-span-6">
        <x-ui.form-group
            name="visibility_id"
            label="Visibility"
            :error="$errors->first('visibility_id')">
            <x-ui.select
                name="visibility_id"
                id="visibility_id"
                :hasError="$errors->has('visibility_id')">
                <option value="">Select visibility</option>
                @foreach($visibilityOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('visibility_id', $series->visibility_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
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
                    <option value="{{ $id }}" {{ old('created_by', $series->created_by ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>

{{-- Hidden event link field --}}
@if (isset($eventLinkId))
<input type="hidden" name="eventLinkId" value="{{ $eventLinkId }}">
@endif

{{-- Submit Button --}}
<div class="flex items-center gap-4 pt-4">
    <x-ui.button type="submit" variant="default">
        {{ isset($action) && $action == 'update' ? 'Update Series' : 'Add Series' }}
    </x-ui.button>
</div>
