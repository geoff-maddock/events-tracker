{{--
    The summary of a form's validation errors (#2411), shown by
    partials.session-messages above the content. It is announced when the page
    loads; resources/assets/js/form-errors.js moves focus to it and turns each
    message into a link to its field.
--}}
@if ($errors->any())
    <div id="form-error-summary" role="alert" tabindex="-1" class="mb-6 p-4 bg-destructive/10 border border-destructive/20 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-destructive">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-triangle text-destructive text-lg mt-0.5" aria-hidden="true"></i>
            <div>
                <p class="font-semibold text-destructive">{{ $errors->count() === 1 ? 'There is a problem with your input.' : 'There are ' . $errors->count() . ' problems with your input.' }}</p>
                <ul class="mt-2 text-sm text-destructive/90 list-disc list-inside">
                    @foreach ($errors->getMessages() as $field => $messages)
                        @foreach ($messages as $message)
                            <li data-error-field="{{ $field }}">{{ $message }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
