<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Escape a raw user search term for safe use inside a LIKE pattern.
     */
    protected function escapeLike(string $value): string
    {
        return Str::of($value)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'])->toString();
    }
}
