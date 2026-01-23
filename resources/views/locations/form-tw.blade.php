{{-- Location Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $location->name ?? '')"
        placeholder="Enter location name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

{{-- Slug --}}
<x-ui.form-group
    name="slug"
    label="Slug"
    :error="$errors->first('slug')"
    helpText="Unique identifier for this location (auto-generated from name)">
    <x-ui.input
        type="text"
        name="slug"
        id="slug"
        :value="old('slug', $location->slug ?? '')"
        placeholder="location-slug"
        :hasError="$errors->has('slug')" />
</x-ui.form-group>

{{-- ATTN --}}
<x-ui.form-group
    name="attn"
    label="ATTN"
    :error="$errors->first('attn')"
    helpText="To the attention of">
    <x-ui.input
        type="text"
        name="attn"
        id="attn"
        :value="old('attn', $location->attn ?? '')"
        placeholder="To the attention of"
        :hasError="$errors->has('attn')" />
</x-ui.form-group>

{{-- Address Fields --}}
<h3 class="text-lg font-semibold text-foreground mb-4 mt-6">Address</h3>

<x-ui.form-group
    name="address_one"
    label="Address Line One"
    :error="$errors->first('address_one')">
    <x-ui.input
        type="text"
        name="address_one"
        id="address_one"
        :value="old('address_one', $location->address_one ?? '')"
        placeholder="Address line one"
        :hasError="$errors->has('address_one')" />
</x-ui.form-group>

<x-ui.form-group
    name="address_two"
    label="Address Line Two"
    :error="$errors->first('address_two')">
    <x-ui.input
        type="text"
        name="address_two"
        id="address_two"
        :value="old('address_two', $location->address_two ?? '')"
        placeholder="Address line two"
        :hasError="$errors->has('address_two')" />
</x-ui.form-group>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <x-ui.form-group
        name="neighborhood"
        label="Neighborhood"
        :error="$errors->first('neighborhood')">
        <x-ui.input
            type="text"
            name="neighborhood"
            id="neighborhood"
            :value="old('neighborhood', $location->neighborhood ?? '')"
            placeholder="Neighborhood"
            :hasError="$errors->has('neighborhood')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="city"
        label="City"
        :error="$errors->first('city')">
        <x-ui.input
            type="text"
            name="city"
            id="city"
            :value="old('city', $location->city ?? '')"
            placeholder="City"
            :hasError="$errors->has('city')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="state"
        label="State"
        :error="$errors->first('state')">
        <x-ui.input
            type="text"
            name="state"
            id="state"
            :value="old('state', $location->state ?? '')"
            placeholder="State"
            :hasError="$errors->has('state')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="postcode"
        label="Post Code"
        :error="$errors->first('postcode')">
        <x-ui.input
            type="text"
            name="postcode"
            id="postcode"
            :value="old('postcode', $location->postcode ?? '')"
            placeholder="Postal code"
            :hasError="$errors->has('postcode')" />
    </x-ui.form-group>
</div>

{{-- Location Details --}}
<h3 class="text-lg font-semibold text-foreground mb-4 mt-6">Location Details</h3>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-ui.form-group
        name="country"
        label="Country"
        :error="$errors->first('country')">
        <x-ui.input
            type="text"
            name="country"
            id="country"
            :value="old('country', $location->country ?? '')"
            placeholder="Country"
            :hasError="$errors->has('country')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="latitude"
        label="Latitude"
        :error="$errors->first('latitude')">
        <x-ui.input
            type="text"
            name="latitude"
            id="latitude"
            :value="old('latitude', $location->latitude ?? '')"
            placeholder="Latitude"
            :hasError="$errors->has('latitude')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="longitude"
        label="Longitude"
        :error="$errors->first('longitude')">
        <x-ui.input
            type="text"
            name="longitude"
            id="longitude"
            :value="old('longitude', $location->longitude ?? '')"
            placeholder="Longitude"
            :hasError="$errors->has('longitude')" />
    </x-ui.form-group>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-ui.form-group
        name="location_type_id"
        label="Type"
        :error="$errors->first('location_type_id')">
        <x-ui.select
            name="location_type_id"
            id="location_type_id"
            :hasError="$errors->has('location_type_id')">
            <option value="">Select location type</option>
            @foreach($locationTypeOptions as $id => $name)
                <option value="{{ $id }}" {{ old('location_type_id', $location->location_type_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.form-group>

    <x-ui.form-group
        name="capacity"
        label="Capacity"
        :error="$errors->first('capacity')">
        <x-ui.input
            type="number"
            name="capacity"
            id="capacity"
            :value="old('capacity', $location->capacity ?? '')"
            placeholder="Capacity"
            :hasError="$errors->has('capacity')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="visibility_id"
        label="Visibility"
        :error="$errors->first('visibility_id')">
        <x-ui.select
            name="visibility_id"
            id="visibility_id"
            :hasError="$errors->has('visibility_id')">
            @foreach($visibilityOptions as $id => $name)
                <option value="{{ $id }}" {{ old('visibility_id', $location->visibility_id ?? 3) == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </x-ui.select>
    </x-ui.form-group>
</div>

{{-- Map URL --}}
<x-ui.form-group
    name="map_url"
    label="Map URL"
    :error="$errors->first('map_url')"
    helpText="Link to map location">
    <x-ui.input
        type="url"
        name="map_url"
        id="map_url"
        :value="old('map_url', $location->map_url ?? '')"
        placeholder="https://maps.google.com/..."
        :hasError="$errors->has('map_url')" />
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Location' : 'Add Location' }}
    </x-ui.button>
    <a href="{{ route('entities.show', $entity->slug) }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
