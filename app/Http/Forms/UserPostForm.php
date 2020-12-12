<?php

namespace App\Http\Forms;

use App\Models\User;

class UserPostForm extends Form
{
    protected $rules = [
        'username' => 'required'
    ];

    public function persist()
    {
        $user = new User();

        var_dump('save to db');
    }
}
