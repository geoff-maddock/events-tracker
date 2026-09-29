<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ProfileRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;  // authorization is enforced in UsersController
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = optional($this->route('user'))->id;

        return [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.($userId ?? 'NULL').',id',
            // the profile form's fields, so the controller can save validated() alone (#2180)
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'alias' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'default_theme' => 'nullable|in:dark,light',
            'facebook_username' => 'nullable|string|max:255',
            'twitter_username' => 'nullable|string|max:255',
            'instagram_username' => 'nullable|string|max:255',
            // checkboxes: present means on (the controller stores 1/0)
            'setting_weekly_update' => 'nullable',
            'setting_daily_update' => 'nullable',
            'setting_instant_update' => 'nullable',
            'setting_forum_update' => 'nullable',
            'setting_notify_threads_by_follow' => 'nullable',
            'setting_public_profile' => 'nullable',
            'setting_feedback_requests' => 'nullable',
            // admin-only; UsersController::update drops them for everyone else
            'user_status_id' => 'nullable|integer|exists:user_statuses,id',
            'group_list' => 'nullable|array',
            'group_list.*' => 'integer|exists:groups,id',
        ];
    }
}
