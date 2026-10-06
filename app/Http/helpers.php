<?php

use App\Http\Flash;

function flash(?string $title = null, ?string $message = null): ?Flash
{
    $flash = app('App\Http\Flash');

    if (0 == func_num_args()) {
        return $flash;
    }

    $flash->message($title, $message);

    return null;
}
