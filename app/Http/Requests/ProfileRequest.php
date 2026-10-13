<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Models\User;
use App\Rules\BioLinks;

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
        $owner = $this->route('user');
        $userId = optional($owner)->id;

        return [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.($userId ?? 'NULL').',id',
            // the profile form's fields, so the controller can save validated() alone (#2180)
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'alias' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'bio' => self::bioRules($owner instanceof User ? $owner : null, $this->user()),
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

    /**
     * Bio rules, shared with api/users (#2312): a length cap, and no links
     * until the account is trusted, unless an admin is the one editing.
     *
     * @return list<mixed>
     */
    public static function bioRules(?User $owner, ?User $editor): array
    {
        $allowLinks = ($editor && $editor->can('grant_access')) || ($owner && $owner->isTrusted());

        return ['nullable', 'string', 'max:'.(int) config('signup.bio_max_length'), new BioLinks($allowLinks)];
    }
}
