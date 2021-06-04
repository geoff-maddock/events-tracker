<?php

namespace App\Http\Controllers\Auth;

use App\Models\Activity;
use App\Models\User;
use App\Models\Profile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        // add some othe validation that prevents spam?
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        // do some profile setting here - set default status
        $profile = new Profile();
        $name = split_name($data['name']);
        $profile->first_name = $name['firstname'];
        $profile->last_name = $name['lastname'];
        $profile->user_id = $user->id;
        $profile->save();

        // log the registration
        Activity::log($user, $user, 1);

        // send a notification to admin to approve the new user
        $this->notifyAdmin($user);

        return $user;
    }

    /**
     * Notify the admin user that a new user was registered
     */
    protected function notifyAdmin(User $user): RedirectResponse
    {
        $admin_email = config('app.admin');
        $no_reply_email = config('app.noreplyemail');
        $site = config('app.app_name');
        $url = config('app.url');

        Mail::send('emails.register', ['user' => $user, 'admin_email' => $admin_email, 'no_reply_email' => $no_reply_email, 'site' => $site, 'url' => $url], function ($m) use ($user, $admin_email, $no_reply_email, $site) {
            $m->from($no_reply_email, $site);

            $m->to($admin_email, 'Administrator')->subject($site . ': New User Registered: ' . $user->name . ' :: ' . $user->created_at->format('D F jS'));
        });

        return back();
    }
}
