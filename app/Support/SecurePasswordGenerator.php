<?php

namespace App\Support;

use Illuminate\Support\Str;

class SecurePasswordGenerator
{
    public static function generate(int $length = 12): string
    {
        $length = max(8, $length);

        do {
            $password = Str::password($length, letters: true, numbers: true, symbols: false);
        } while (! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password));

        return $password;
    }
}
