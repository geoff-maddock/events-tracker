<?php

namespace App\Http\Forms;

use App\Http\Requests\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;

class Form
{
    use ValidatesRequests;

    protected $request;

    protected $errors;

    protected $rules = [];

    public function __construct (Request $request = null)
    {
        $this->request = $request ?: request();
    }

    public function isValid()
    {
        try {
            $this->validate($this->request, $this->rules);
        } catch(\Exception $e) {
            $this->errors = $e->validator->errors();
        }

        return true;
    }

    public function fields()
    {

    }

    public function __get($property)
    {
        return $this->request->input($property);
    }


    abstract public function persist();

    public function save()
    {
        if ($this->isValid()) {
            $this->persist();

            return true;
        }

        return false;
    }
}
