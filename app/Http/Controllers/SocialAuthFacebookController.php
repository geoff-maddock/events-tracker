<?php

namespace App\Http\Controllers;

use App\Services\SocialFacebookAccountService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthFacebookController extends Controller
{
    /**
     * Create a redirect method to facebook api.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Return a callback method from facebook api.
     */
    public function callback(SocialFacebookAccountService $service): RedirectResponse
    {
        $user = $service->createOrGetUser(Socialite::driver('facebook')->user());
        auth()->login($user);

        return redirect()->to('/home');
    }
}
