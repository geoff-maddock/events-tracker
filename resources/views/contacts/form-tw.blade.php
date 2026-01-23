{{-- Contact Name --}}
<x-ui.form-group
    name="name"
    label="Name"
    :error="$errors->first('name')"
    required>
    <x-ui.input
        type="text"
        name="name"
        id="name"
        :value="old('name', $contact->name ?? '')"
        placeholder="Enter contact name"
        :hasError="$errors->has('name')"
        autofocus />
</x-ui.form-group>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Email --}}
    <x-ui.form-group
        name="email"
        label="Email"
        :error="$errors->first('email')">
        <x-ui.input
            type="email"
            name="email"
            id="email"
            :value="old('email', $contact->email ?? '')"
            placeholder="email@example.com"
            :hasError="$errors->has('email')" />
    </x-ui.form-group>

    {{-- Phone --}}
    <x-ui.form-group
        name="phone"
        label="Phone"
        :error="$errors->first('phone')">
        <x-ui.input
            type="tel"
            name="phone"
            id="phone"
            :value="old('phone', $contact->phone ?? '')"
            placeholder="Phone number"
            :hasError="$errors->has('phone')" />
    </x-ui.form-group>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Other --}}
    <x-ui.form-group
        name="other"
        label="Other"
        :error="$errors->first('other')"
        helpText="Additional contact information">
        <x-ui.input
            type="text"
            name="other"
            id="other"
            :value="old('other', $contact->other ?? '')"
            placeholder="Other contact info"
            :hasError="$errors->has('other')" />
    </x-ui.form-group>

    {{-- Type --}}
    <x-ui.form-group
        name="type"
        label="Type"
        :error="$errors->first('type')"
        helpText="Type of contact (e.g., agent, personal, management)">
        <x-ui.input
            type="text"
            name="type"
            id="type"
            :value="old('type', $contact->type ?? '')"
            placeholder="Type of contact"
            :hasError="$errors->has('type')" />
    </x-ui.form-group>
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
        @foreach($visibilities as $id => $name)
            <option value="{{ $id }}" {{ old('visibility_id', $contact->visibility_id ?? 3) == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Contact' : 'Add Contact' }}
    </x-ui.button>
    <a href="{{ url()->previous() }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
