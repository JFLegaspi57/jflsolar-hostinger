<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Form values as plain strings: an array sent in place of text (name[]=…) becomes '' instead of an error. */
final class Input
{
    public static function str(Request $request, string $key, string $default = ''): string
    {
        $v = $request->input($key, $default);

        return is_string($v) || is_numeric($v) ? (string) $v : '';
    }
}
