{{-- Comment Message --}}
<x-ui.form-group
    name="message"
    label="Message"
    :error="$errors->first('message')"
    required>
    <x-ui.textarea
        name="message"
        id="message"
        :value="old('message', $comment->message ?? '')"
        placeholder="Enter your comment"
        :hasError="$errors->has('message')"
        rows="6"
        autofocus />
</x-ui.form-group>

{{-- Submit Button --}}
<div class="flex items-center gap-3 mt-6">
    <x-ui.button type="submit">
        {{ isset($action) && $action === 'update' ? 'Update Comment' : 'Add Comment' }}
    </x-ui.button>
    <a href="{{ url()->previous() }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
        Cancel
    </a>
</div>
