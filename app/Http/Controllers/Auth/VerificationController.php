<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Show the email verification notice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request)
    {
        // Guard against null user (should be prevented by middleware, but defensive)
        if (!$request->user()) {
            return redirect()->route('login');
        }

        return $request->user()->hasVerifiedEmail()
            ? redirect($this->redirectPath())
            : view('auth.verify-tw');
    }

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->only(['show', 'resend']);
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    // Override the trait method to not rely on $request->user()
    public function verify(Request $request)
    {
        $userId = (int) $request->route('id');
        $hash = (string) $request->route('hash');

        $user = User::findOrFail($userId);

        // Validate the email hash from the URL
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('status', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectPath())->with('status', 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Optional: auto-login after verification
        Auth::login($user);

        return redirect($this->redirectPath())->with('verified', true);
    }
}
