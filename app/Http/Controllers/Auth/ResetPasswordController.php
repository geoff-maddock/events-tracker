<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Display the password reset view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset-tw')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    protected function sendResetResponse(Request $request, $response)
    {
        $user = Auth::user() ?: User::where('email', $request->input('email'))->first();

        $activity = new Activity();
        $activity->user_id = $user?->id;
        $activity->object_table = 'User';
        $activity->object_id = $user?->id;
        $activity->object_name = $request->input('email');
        $activity->action_id = Action::PASSWORD_RESET;
        $activity->message = 'Password reset for ' . $request->input('email');
        $activity->ip_address = $request->ip();
        $activity->save();

        return redirect($this->redirectPath())
            ->with('status', trans($response));
    }
}
