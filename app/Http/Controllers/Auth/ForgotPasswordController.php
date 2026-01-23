<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email-tw');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        $email = $request->input('email');
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        $activity = new Activity();
        $activity->user_id = $user?->id;
        $activity->object_table = 'User';
        $activity->object_id = $user?->id;
        $activity->object_name = $email;
        $activity->action_id = Action::PASSWORD_RESET_REQUEST;
        $activity->message = 'Password reset link requested for ' . $email;
        $activity->ip_address = $request->ip();
        $activity->save();

        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
    }
}
