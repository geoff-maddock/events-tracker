<?php

namespace App\Http;

use Illuminate\Support\Facades\Session;

class Flash
{
    public function message(string $title, string $message): void
    {
        Session::flash('flash_message', [
            'title' => $title,
            'message' => $message,
            'level' => 'info',
        ]);
    }

    public function create(string $title, string $message, int $level): void
    {
        Session::flash('flash_message', [
            'title' => $title,
            'message' => $message,
            'level' => $level,
        ]);
    }

    public function error(string $title, string $message): void
    {
        Session::flash('flash_message', [
            'title' => $title,
            'message' => $message,
            'level' => 'error',
        ]);
    }

    public function success(string $title, string $message): void
    {
        Session::flash('flash_message', [
            'title' => $title,
            'message' => $message,
            'level' => 'success',
        ]);
    }
}
