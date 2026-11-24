{{--
    Messages a redirect left in the session with ->with('error', ...) or ->with('status', ...):
    the expired-session notice, account and email-verification results. Unlike the flash()
    toast they stay on the page until the next one. A form's validation errors are summarised
    here too. The layout shows them above the content;
    a page that places them itself includes this partial and sets
    @section('session-messages', 'inline').
--}}
@if (session('error'))
    <div class="mb-6 p-4 bg-destructive/10 border border-destructive/20 rounded-lg" role="alert">
        <div class="flex items-center gap-3">
            <i class="bi bi-exclamation-triangle text-destructive" aria-hidden="true"></i>
            <span class="text-destructive">{{ session('error') }}</span>
        </div>
    </div>
@endif

@if (session('status'))
    <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg" role="status">
        <div class="flex items-center gap-3">
            <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
            <span class="text-success">{{ session('status') }}</span>
        </div>
    </div>
@endif

@include('partials.form-errors')
