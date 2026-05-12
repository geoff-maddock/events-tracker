<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:6,1')->only('verify');
    }

    /**
     * Verify a user's email address via API.
     * This performs the same check and update as the web endpoint but returns JSON.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        // Defense in depth: the `signed` middleware on the route already
        // enforces this, but reject unsigned requests here too in case a
        // future route refactor drops the middleware.
        // The verification URL is generated with a relative signature (see
        // AuthServiceProvider::VerifyEmail::createUrlUsing) so it can be
        // prefixed by a frontend host; validate it accordingly.
        if (! $request->hasValidSignature(absolute: false)) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        $userId = (int) $request->route('id');
        $hash = (string) $request->route('hash');

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Validate the email hash from the URL
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link.',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully.',
            'verified' => true
        ], 200);
    }
}
