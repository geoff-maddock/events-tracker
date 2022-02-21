<?php

namespace App\Models\Interfaces;

use App\Models\User;

interface OwnableInterface
{
    public function getCreatedBy(): User;
}
