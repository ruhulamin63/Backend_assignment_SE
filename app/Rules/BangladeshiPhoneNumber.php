<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BangladeshiPhoneNumber implements Rule
{
    public function passes($attribute, $value)
    {
        // Define the regular expression pattern for Bangladeshi phone numbers.
        $pattern = '/^01[1-9]\d{8}$/';

        return preg_match($pattern, $value);
    }

    public function message()
    {
        return 'The :attribute must be a valid Bangladeshi phone number.';
    }
}
