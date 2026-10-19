{{--
    Post-registration "check your email" prompt.

    Shown once, on the page a user lands on straight after registering, while
    their account is still pending activation. RegisterController::registered()
    flashes `show_verification_notice` with the address the link was sent to.
    UsersController::update() flashes it too when a user changes their own
    email, which must be verified again (#2321).

    Deliberately self-contained Alpine rather than <x-ui.modal>: that component
    emits an inline script with a hardcoded element id and stacks a fresh
    document-level keydown listener on every render.

    Required variables:
      $email (string)  address the activation link was sent to
    Optional:
      $emailChanged (bool)  the link verifies a changed email, not a new account
--}}
<style>[x-cloak]{display:none!important}</style>
<div x-data="verifyEmailNotice()" x-show="open" x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="verify-email-title">
    <div class="bg-secondary rounded-lg shadow-2xl max-w-md w-full border border-border ring-1 ring-black/5"
         @click.stop>
        <!-- Header -->
        <div class="p-6 pb-4 text-center">
            <div class="w-16 h-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-4">
                <i class="bi bi-envelope-check text-primary text-3xl"></i>
            </div>
            <h2 id="verify-email-title" class="text-xl font-bold text-foreground">Check your email</h2>
        </div>

        <!-- Body -->
        <div class="px-6 pb-2 space-y-3 text-sm text-muted-foreground text-center">
            @if($emailChanged ?? false)
                <p>
                    Your email changed, so we sent a verification link to
                    <span class="font-medium text-foreground break-all">{{ $email }}</span>.
                    Click that link to confirm the new address.
                </p>
                <p>Until then you can't add or edit content.</p>
            @else
                <p>
                    We sent an activation link to
                    <span class="font-medium text-foreground break-all">{{ $email }}</span>.
                    Click that link to finish activating your account.
                </p>
                <p>Until then you're signed in, but some features stay locked.</p>
            @endif
            <p class="text-xs">Can't find it? Check your spam or promotions folder.</p>

            <p x-show="status" x-cloak class="text-xs"
               :class="statusIsError ? 'text-destructive' : 'text-green-500'"
               x-text="status"></p>
        </div>

        <!-- Footer -->
        <div class="p-4 flex flex-wrap justify-between items-center gap-2">
            <button type="button" @click="resend()" :disabled="sending"
                    class="px-3 py-2 text-sm rounded-md text-muted-foreground hover:text-foreground hover:bg-background transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!sending">Resend email</span>
                <span x-show="sending" x-cloak>Sending&hellip;</span>
            </button>
            <button type="button" @click="open = false"
                    class="px-4 py-2 text-sm rounded-md bg-primary text-primary-foreground hover:opacity-90 transition-opacity">
                Got it
            </button>
        </div>
    </div>
</div>

<script>
    function verifyEmailNotice() {
        return {
            open: true,
            sending: false,
            status: '',
            statusIsError: false,

            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            },

            // The resend endpoint answers JSON when asked: 202 on send, 204 if
            // the address was already verified in another tab.
            resend() {
                if (this.sending) return;
                this.sending = true;
                this.status = '';
                this.statusIsError = false;

                fetch('{{ route('verification.resend') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                })
                    .then(r => {
                        if (r.status === 204) {
                            this.status = 'That address is already verified — you\'re all set.';
                        } else if (r.ok) {
                            this.status = 'Sent. A fresh {{ ($emailChanged ?? false) ? 'verification' : 'activation' }} link is on its way.';
                        } else if (r.status === 429) {
                            this.statusIsError = true;
                            this.status = 'Too many requests. Please wait a minute and try again.';
                        } else {
                            this.statusIsError = true;
                            this.status = 'Something went wrong. Please try again.';
                        }
                    })
                    .catch(() => {
                        this.statusIsError = true;
                        this.status = 'Something went wrong. Please try again.';
                    })
                    .finally(() => { this.sending = false; });
            },
        };
    }
</script>
