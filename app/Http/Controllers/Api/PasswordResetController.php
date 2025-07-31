<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Send the password reset link to the given email address.
     */
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'secret' => ['required', 'string'],
        ]);

        if ($data['secret'] !== config('app.password_reset_secret')) {
            return response()->json(['message' => 'Invalid secret'], 401);
        }

        $status = Password::broker()->sendResetLink(['email' => $data['email']]);

        $code = $status === Password::RESET_LINK_SENT ? 200 : 400;

        return response()->json(['message' => __($status)], $code);
    }

    /**
     * Reset the user's password using the provided token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'token' => ['required', 'string'],
            'secret' => ['required', 'string'],
        ]);

        if ($data['secret'] !== config('app.password_reset_secret')) {
            return response()->json(['message' => 'Invalid secret'], 401);
        }

        $status = Password::broker()->reset([
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password'],
            'token' => $data['token'],
        ], function ($user, $password) {
            $user->password = $password;
            $user->save();
        });

        $code = $status === Password::PASSWORD_RESET ? 200 : 400;

        return response()->json(['message' => __($status)], $code);
    }
}
