<?php

/**
 * GC-Stats — EnsureWritesAreNotFrozen middleware
 *
 * Refuses public write routes while the V2 migration freeze is on
 * (see App\Support\WriteFreeze).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Support\WriteFreeze;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWritesAreNotFrozen
{
    public function handle(Request $request, Closure $next): Response
    {
        WriteFreeze::abortIfActive();

        return $next($request);
    }
}
