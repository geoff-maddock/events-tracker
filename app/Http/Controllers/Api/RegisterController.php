<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Profile;
use App\Http\Resources\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Register a new user via API.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        // Validate the request
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the user
        $user = $this->create($request->all());

        // Fire the Registered event to trigger email verification
        event(new Registered($user));

        // add an activity log that a new user was added
        Activity::log($user, null, 1, sprintf('New user registered: %s', $user->email));

        return response()->json([
            'message' => 'User registered successfully. Please check your email to verify your account.',
            'user' => new UserResource($user)
        ], 201);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'g-recaptcha-response' => 'required|captcha'
        ], [
            'name.required' => 'A name is required',
            'name.min' => 'A name must be at least 3 characters',
            'name.max' => 'A name must be less than 255 characters',
            'email.required' => 'An email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'A password is required',
            'password.min' => 'A password must be at least 8 characters',
            'g-recaptcha-response.required' => 'Please complete the captcha verification',
            'g-recaptcha-response.captcha' => 'Captcha verification failed'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'user_status_id' => UserStatus::PENDING,
        ]);

        // email_verified_at will be null by default
        
        // Create an empty profile for the user
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        return $user;
    }
}