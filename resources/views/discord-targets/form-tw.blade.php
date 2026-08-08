@php
	$isUpdate = isset($action) && $action === 'update';

	// Current criteria, grouped for the multi-selects below.
	$selected = [];
	foreach ($target->criteria ?? [] as $criterion) {
		$selected[$criterion->mode][$criterion->criteria_type][] = $criterion->criteria_id;
	}

	$chosen = function (string $mode, string $type) use ($selected) {
		return old($mode . '.' . $type, $selected[$mode][$type] ?? []);
	};
@endphp

{{-- Name --}}
<x-ui.form-group name="name" label="Name" :error="$errors->first('name')" required>
	<x-ui.input
		type="text"
		name="name"
		id="name"
		:value="old('name', $target->name ?? '')"
		placeholder="e.g. #events in Pittsburgh Techno"
		:hasError="$errors->has('name')"
		autofocus />
	<p class="text-xs text-muted-foreground mt-1">A label for admins. Not shown in Discord.</p>
</x-ui.form-group>

{{-- Webhook URL --}}
<x-ui.form-group
	name="webhook_url"
	label="Webhook URL"
	:error="$errors->first('webhook_url')"
	:required="! $isUpdate">
	<x-ui.input
		type="url"
		name="webhook_url"
		id="webhook_url"
		value=""
		placeholder="https://discord.com/api/webhooks/…"
		:hasError="$errors->has('webhook_url')" />
	<p class="text-xs text-muted-foreground mt-1">
		In Discord: <strong>Channel Settings → Integrations → Webhooks → New Webhook → Copy Webhook URL</strong>.
		@if($isUpdate)
			{{-- Never render the stored secret back into the page. --}}
			Currently <span class="font-mono">{{ $target->maskedUrl() }}</span>. Leave blank to keep it.
		@else
			Anyone with this URL can post to the channel, so it is stored encrypted and never shown again.
		@endif
	</p>
</x-ui.form-group>

{{-- What this channel receives --}}
<div class="border-t border-border pt-6 mt-6">
	<h2 class="text-lg font-semibold text-foreground mb-1">What this channel receives</h2>
	<p class="text-sm text-muted-foreground mb-4">
		Within one filter type the options are OR'd; across types they are AND'd. Excludes always win.
		A target with no include filters receives nothing unless "all events" is ticked below.
	</p>

	@error('include')
		<p class="text-sm text-destructive mb-3">{{ $message }}</p>
	@enderror

	<label class="flex items-start gap-3 mb-4">
		<x-ui.checkbox name="match_all" id="match_all" value="1" :checked="old('match_all', $target->match_all ?? false)" class="mt-1" />
		<span>
			<span class="text-foreground font-medium">Receive all events</span>
			<span class="block text-xs text-muted-foreground">Every public event on the site. Use with care on a busy channel.</span>
		</span>
	</label>

	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		@foreach([
			['tag', 'Tags', $tagOptions],
			['event_type', 'Event types', $eventTypeOptions],
			['venue', 'Venues', $venueOptions],
			['series', 'Series', $seriesOptions],
		] as [$type, $label, $options])
			<x-ui.form-group :name="'include.' . $type" :label="'Include ' . strtolower($label)">
				<select name="include[{{ $type }}][]" id="include_{{ $type }}" multiple size="6"
					class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
					@foreach($options as $id => $optionLabel)
						<option value="{{ $id }}" {{ in_array($id, $chosen('include', $type)) ? 'selected' : '' }}>
							{{ $optionLabel }}
						</option>
					@endforeach
				</select>
			</x-ui.form-group>
		@endforeach
	</div>

	<x-ui.form-group name="exclude.tag" label="Exclude tags">
		<select name="exclude[tag][]" id="exclude_tag" multiple size="4"
			class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
			@foreach($tagOptions as $id => $optionLabel)
				<option value="{{ $id }}" {{ in_array($id, $chosen('exclude', 'tag')) ? 'selected' : '' }}>
					{{ $optionLabel }}
				</option>
			@endforeach
		</select>
		<p class="text-xs text-muted-foreground mt-1">An event carrying any of these never reaches this channel.</p>
	</x-ui.form-group>

	<label class="flex items-start gap-3 mt-4">
		<x-ui.checkbox name="requires_photo" id="requires_photo" value="1" :checked="old('requires_photo', $target->requires_photo ?? true)" class="mt-1" />
		<span>
			<span class="text-foreground font-medium">Only post events that have a flyer</span>
			<span class="block text-xs text-muted-foreground">Recommended — an embed without an image is easy to scroll past.</span>
		</span>
	</label>
</div>

{{-- When it posts --}}
<div class="border-t border-border pt-6 mt-6">
	<h2 class="text-lg font-semibold text-foreground mb-4">When it posts</h2>

	<div class="space-y-3">
		<label class="flex items-start gap-3">
			<x-ui.checkbox name="post_on_create" id="post_on_create" value="1" :checked="old('post_on_create', $target->post_on_create ?? true)" class="mt-1" />
			<span>
				<span class="text-foreground font-medium">When an event is added</span>
				<span class="block text-xs text-muted-foreground">
					Posted about {{ config('discord.create_delay_minutes') }} minutes after a flyer is attached,
					so last-minute edits are included.
				</span>
			</span>
		</label>

		<label class="flex items-start gap-3">
			<x-ui.checkbox name="post_reminders" id="post_reminders" value="1" :checked="old('post_reminders', $target->post_reminders ?? false)" class="mt-1" />
			<span>
				<span class="text-foreground font-medium">Reminders before the show</span>
			</span>
		</label>

		<div class="ml-7">
			<x-ui.form-group name="reminder_offsets" label="Days before the event">
				<input type="text" name="reminder_offsets_csv" id="reminder_offsets_csv"
					value="{{ old('reminder_offsets_csv', implode(', ', $target->reminder_offsets ?? [])) }}"
					placeholder="{{ implode(', ', config('discord.default_reminder_offsets')) }}"
					class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
				<p class="text-xs text-muted-foreground mt-1">
					Comma-separated. Blank uses the site default ({{ implode(', ', config('discord.default_reminder_offsets')) }}).
				</p>
			</x-ui.form-group>
		</div>

		<label class="flex items-start gap-3">
			<x-ui.checkbox name="post_digest" id="post_digest" value="1" :checked="old('post_digest', $target->post_digest ?? false)" class="mt-1" />
			<span>
				<span class="text-foreground font-medium">Weekly digest</span>
				<span class="block text-xs text-muted-foreground">One roundup message instead of, or alongside, individual posts.</span>
			</span>
		</label>

		<div class="ml-7 grid grid-cols-1 md:grid-cols-3 gap-4">
			<x-ui.form-group name="digest_day" label="Day">
				<x-ui.select name="digest_day" id="digest_day">
					@foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $dayName)
						<option value="{{ $index }}" {{ (int) old('digest_day', $target->digest_day ?? config('discord.digest.default_day')) === $index ? 'selected' : '' }}>
							{{ $dayName }}
						</option>
					@endforeach
				</x-ui.select>
			</x-ui.form-group>

			<x-ui.form-group name="digest_hour" label="Hour">
				<x-ui.select name="digest_hour" id="digest_hour">
					@for($hour = 0; $hour < 24; $hour++)
						<option value="{{ $hour }}" {{ (int) old('digest_hour', $target->digest_hour ?? config('discord.digest.default_hour')) === $hour ? 'selected' : '' }}>
							{{ sprintf('%02d:00', $hour) }}
						</option>
					@endfor
				</x-ui.select>
			</x-ui.form-group>

			<x-ui.form-group name="digest_window_days" label="Days ahead">
				<x-ui.input type="number" name="digest_window_days" id="digest_window_days" min="1" max="60"
					:value="old('digest_window_days', $target->digest_window_days ?? config('discord.digest.window_days'))" />
			</x-ui.form-group>
		</div>

		<label class="flex items-start gap-3">
			<x-ui.checkbox name="allow_manual" id="allow_manual" value="1" :checked="old('allow_manual', $target->allow_manual ?? true)" class="mt-1" />
			<span>
				<span class="text-foreground font-medium">Allow manual posts</span>
				<span class="block text-xs text-muted-foreground">Admins can push an event here from its page.</span>
			</span>
		</label>
	</div>
</div>

{{-- Presentation --}}
<div class="border-t border-border pt-6 mt-6">
	<h2 class="text-lg font-semibold text-foreground mb-4">Appearance</h2>

	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		<x-ui.form-group name="mention" label="Mention" :error="$errors->first('mention')">
			<x-ui.input type="text" name="mention" id="mention" :value="old('mention', $target->mention ?? '')" placeholder="@here" />
			<p class="text-xs text-muted-foreground mt-1">Optional, sent above the embed. Use a role as <code>&lt;@&amp;roleid&gt;</code>.</p>
		</x-ui.form-group>

		<x-ui.form-group name="username" label="Post as" :error="$errors->first('username')">
			<x-ui.input type="text" name="username" id="username" :value="old('username', $target->username ?? '')" :placeholder="config('app.app_name')" />
		</x-ui.form-group>

		<x-ui.form-group name="avatar_url" label="Avatar URL" :error="$errors->first('avatar_url')">
			<x-ui.input type="url" name="avatar_url" id="avatar_url" :value="old('avatar_url', $target->avatar_url ?? '')" />
		</x-ui.form-group>

		<x-ui.form-group name="is_enabled" label="Status">
			<label class="flex items-center gap-3 pt-2">
				<x-ui.checkbox name="is_enabled" id="is_enabled" value="1" :checked="old('is_enabled', $target->is_enabled ?? true)" />
				<span class="text-foreground">Enabled</span>
			</label>
		</x-ui.form-group>
	</div>
</div>

{{-- Submit --}}
<div class="flex items-center gap-3 border-t border-border pt-6 mt-6">
	<x-ui.button type="submit">
		{{ $isUpdate ? 'Update Target' : 'Add Target' }}
	</x-ui.button>
	<a href="{{ route('discord-targets.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
		Cancel
	</a>
</div>
