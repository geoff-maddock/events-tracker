{{-- Review Text --}}
<x-ui.form-group
    name="review"
    label="Review"
    :error="$errors->first('review')"
    required>
    <x-ui.textarea
        name="review"
        id="review"
        :hasError="$errors->has('review')"
        rows="6"
        placeholder="Write your review...">{{ old('review', $eventReview->review ?? '') }}</x-ui.textarea>
</x-ui.form-group>

{{-- Review Type --}}
<x-ui.form-group
    name="review_type_id"
    label="Type"
    :error="$errors->first('review_type_id')">
    <x-ui.select
        name="review_type_id"
        id="review_type_id"
        :hasError="$errors->has('review_type_id')">
        <option value="">Select review type</option>
        @foreach($reviewTypeOptions as $id => $name)
            <option value="{{ $id }}" {{ old('review_type_id', $eventReview->review_type_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </x-ui.select>
</x-ui.form-group>

{{-- Attendance and Confirmation --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex items-start gap-3">
        <x-ui.checkbox
            name="attended"
            id="attended"
            value="1"
            :checked="old('attended', $eventReview->attended ?? false)"
            :hasError="$errors->has('attended')" />
        <div class="flex-1">
            <x-ui.label for="attended">Attended</x-ui.label>
            <p class="text-xs text-muted-foreground">Check if you attended this event</p>
            @if($errors->has('attended'))
                <span class="text-xs text-destructive">{{ $errors->first('attended') }}</span>
            @endif
        </div>
    </div>

    <div class="flex items-start gap-3">
        <x-ui.checkbox
            name="confirmed"
            id="confirmed"
            value="1"
            :checked="old('confirmed', $eventReview->confirmed ?? false)"
            :hasError="$errors->has('confirmed')" />
        <div class="flex-1">
            <x-ui.label for="confirmed">Confirmed</x-ui.label>
            <p class="text-xs text-muted-foreground">Check if attendance is confirmed</p>
            @if($errors->has('confirmed'))
                <span class="text-xs text-destructive">{{ $errors->first('confirmed') }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Ratings --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-ui.form-group
        name="expectation"
        label="Expected Rating"
        :error="$errors->first('expectation')"
        helpText="Rating from 1-10">
        <x-ui.input
            type="number"
            name="expectation"
            id="expectation"
            :value="old('expectation', $eventReview->expectation ?? '')"
            placeholder="Expected rating (1-10)"
            min="1"
            max="10"
            :hasError="$errors->has('expectation')" />
    </x-ui.form-group>

    <x-ui.form-group
        name="rating"
        label="Actual Rating"
        :error="$errors->first('rating')"
        helpText="Rating from 1-10">
        <x-ui.input
            type="number"
            name="rating"
            id="rating"
            :value="old('rating', $eventReview->rating ?? '')"
            placeholder="Rating (1-10)"
            min="1"
            max="10"
            :hasError="$errors->has('rating')" />
    </x-ui.form-group>
</div>

{{-- Submit Button --}}
<div class="flex items-center gap-3">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Review' : 'Add Review' }}
    </x-ui.button>
    <a href="{{ route('events.index') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
