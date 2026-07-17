<?php

/**
 * GC-Stats — SetDefaultPermissionTeam middleware
 *
 * spatie/laravel-permission's "teams" feature scopes every role/permission
 * check to whatever team id is currently active (see PermissionTeam). Most
 * requests aren't about a specific team, so this defaults every request to
 * the global scope; controllers dealing with a specific team explicitly
 * switch context via PermissionTeam::use($team->id) before checking.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Support\PermissionTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultPermissionTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        PermissionTeam::global();

        return $next($request);
    }
}
