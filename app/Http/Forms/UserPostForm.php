<?php

namespace App\Http\Forms;

use App\User;

class UserPostForm extends Form{

    protected $rules = [
        'username' => 'required'
    ];

    public function persist ()
    {
        $user = new \App\User();

        var_dump('save to db');
    }
}