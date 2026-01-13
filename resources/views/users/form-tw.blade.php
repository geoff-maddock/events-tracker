{{-- First Name --}}
<x-ui.form-group
    name="first_name"
    label="First Name"
    :error="$errors->first('first_name')">
    <x-ui.input
        type="text"
        name="first_name"
        id="first_name"
        :value="old('first_name', $user->first_name ?? '')"
        placeholder="Enter first name"
        :hasError="$errors->has('first_name')" />
</x-ui.form-group>

{{-- Last Name --}}
<x-ui.form-group
    name="last_name"
    label="Last Name"
    :error="$errors->first('last_name')">
    <x-ui.input
        type="text"
        name="last_name"
        id="last_name"
        :value="old('last_name', $user->last_name ?? '')"
        placeholder="Enter last name"
        :hasError="$errors->has('last_name')" />
</x-ui.form-group>

{{-- Alias --}}
<x-ui.form-group
    name="alias"
    label="Alias"
    :error="$errors->first('alias')"
    helpText="Your public username">
    <x-ui.input
        type="text"
        name="alias"
        id="alias"
        :value="old('alias', $user->alias ?? '')"
        placeholder="Enter alias"
        :hasError="$errors->has('alias')" />
</x-ui.form-group>

{{-- Bio --}}
<x-ui.form-group
    name="bio"
    label="Bio"
    :error="$errors->first('bio')">
    <x-ui.textarea
        name="bio"
        id="bio"
        :hasError="$errors->has('bio')"
        rows="4"
        placeholder="Tell us about yourself">{{ old('bio', $user->bio ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Social Media --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-ui.form-group
        name="facebook_username"
        label="Facebook Username"
        :error="$errors->first('facebook_username')">
        <x-ui.input
            type="text"
            name="facebook_username"
            id="facebook_username"
            :value="old('facebook_username', $user->facebook_username ?? '')"
            placeholder="username"
            :hasError="$errors->has('facebook_username')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="instagram_username"
        label="Instagram Username"
        :error="$errors->first('instagram_username')">
        <x-ui.input
            type="text"
            name="instagram_username"
            id="instagram_username"
            :value="old('instagram_username', $user->instagram_username ?? '')"
            placeholder="username"
            :hasError="$errors->has('instagram_username')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="twitter_username"
        label="Twitter Username"
        :error="$errors->first('twitter_username')">
        <x-ui.input
            type="text"
            name="twitter_username"
            id="twitter_username"
            :value="old('twitter_username', $user->twitter_username ?? '')"
            placeholder="username"
            :hasError="$errors->has('twitter_username')" />
    </x-ui.form-group>
</div>

@can('grant_access')
{{-- Admin Fields --}}
<div class="border-t border-border pt-6 mt-6">
    <h3 class="text-lg font-semibold text-foreground mb-4">Administrative Settings</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.form-group
            name="user_status_id"
            label="Status"
            :error="$errors->first('user_status_id')">
            <x-ui.select
                name="user_status_id"
                id="user_status_id"
                :hasError="$errors->has('user_status_id')">
                <option value="">Select status</option>
                @foreach($userStatusOptions as $id => $name)
                    <option value="{{ $id }}" {{ old('user_status_id', $user->user_status_id ?? '') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>

        <x-ui.form-group
            name="group_list"
            label="Group Memberships"
            :error="$errors->first('groups')">
            <x-ui.select
                name="group_list[]"
                id="group_list"
                class="select2"
                data-theme="tailwind"
                data-placeholder="Select group memberships"
                data-tags="false"
                multiple
                :hasError="$errors->has('groups')">
                @foreach($groupOptions as $id => $name)
                    <option value="{{ $id }}" {{ in_array($id, old('group_list', $user->groups->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </x-ui.select>
        </x-ui.form-group>
    </div>
</div>
@endcan

{{-- Theme Preference --}}
<x-ui.form-group
    name="default_theme"
    label="Default Theme"
    :error="$errors->first('default_theme')">
    <x-ui.select
        name="default_theme"
        id="default_theme"
        :hasError="$errors->has('default_theme')">
        @foreach(Config::get('constants.themes') as $value => $label)
            <option value="{{ $value }}" {{ old('default_theme', isset($user->profile) ? $user->profile->default_theme : '') == $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Notification Settings --}}
<div class="border-t border-border pt-6 mt-6">
    <h3 class="text-lg font-semibold text-foreground mb-4">Notification Settings</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="flex items-start gap-3">
            <x-ui.checkbox
                name="setting_weekly_update"
                id="setting_weekly_update"
                value="1"
                :checked="old('setting_weekly_update', isset($user->profile) ? $user->profile->setting_weekly_update : false)"
                :hasError="$errors->has('setting_weekly_update')" />
            <div class="flex-1">
                <x-ui.label for="setting_weekly_update">Receive Weekly Updates</x-ui.label>
                @if($errors->has('setting_weekly_update'))
                    <span class="text-xs text-destructive">{{ $errors->first('setting_weekly_update') }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-start gap-3">
            <x-ui.checkbox
                name="setting_daily_update"
                id="setting_daily_update"
                value="1"
                :checked="old('setting_daily_update', isset($user->profile) ? $user->profile->setting_daily_update : false)"
                :hasError="$errors->has('setting_daily_update')" />
            <div class="flex-1">
                <x-ui.label for="setting_daily_update">Receive Daily Updates</x-ui.label>
                @if($errors->has('setting_daily_update'))
                    <span class="text-xs text-destructive">{{ $errors->first('setting_daily_update') }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-start gap-3">
            <x-ui.checkbox
                name="setting_instant_update"
                id="setting_instant_update"
                value="1"
                :checked="old('setting_instant_update', isset($user->profile) ? $user->profile->setting_instant_update : false)"
                :hasError="$errors->has('setting_instant_update')" />
            <div class="flex-1">
                <x-ui.label for="setting_instant_update">Receive Instant Updates</x-ui.label>
                @if($errors->has('setting_instant_update'))
                    <span class="text-xs text-destructive">{{ $errors->first('setting_instant_update') }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-start gap-3">
            <x-ui.checkbox
                name="setting_forum_update"
                id="setting_forum_update"
                value="1"
                :checked="old('setting_forum_update', isset($user->profile) ? $user->profile->setting_forum_update : false)"
                :hasError="$errors->has('setting_forum_update')" />
            <div class="flex-1">
                <x-ui.label for="setting_forum_update">Receive Forum Updates</x-ui.label>
                @if($errors->has('setting_forum_update'))
                    <span class="text-xs text-destructive">{{ $errors->first('setting_forum_update') }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-start gap-3">
            <x-ui.checkbox
                name="setting_public_profile"
                id="setting_public_profile"
                value="1"
                :checked="old('setting_public_profile', isset($user->profile) ? $user->profile->setting_public_profile : false)"
                :hasError="$errors->has('setting_public_profile')" />
            <div class="flex-1">
                <x-ui.label for="setting_public_profile">Public Profile</x-ui.label>
                @if($errors->has('setting_public_profile'))
                    <span class="text-xs text-destructive">{{ $errors->first('setting_public_profile') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Profile' : 'Add Profile' }}
    </x-ui.button>
    <a href="{{ route('users.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
