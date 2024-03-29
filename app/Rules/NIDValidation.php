<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NIDValidation implements Rule
{
    protected $length;
    public function passes($attribute, $value)
    {
        // Define validation for nid
        $this->length = strlen($value);
        return in_array($this->length, [10, 13, 17]);
    }

    public function message()
    {
        return "The :attribute length must be 10/13/17 character. (current length: ".$this->length.")";
    }
}
