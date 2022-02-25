<?php

namespace App\Http\Forms;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Form
{
    use ValidatesRequests;

    protected Request $request;

    protected mixed $errors;

    protected array $rules = [];

    public function __construct(Request $request = null)
    {
        $this->request = $request ?: request();
    }

    public function isValid(): bool
    {
        try {
            $this->validate($this->request, $this->rules);
        } catch (ValidationException $e) {
            $this->errors = $e->validator->errors();
        }

        return true;
    }
}
