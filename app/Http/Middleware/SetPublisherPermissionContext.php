<?php

/**
 * GC-Stats — SetPublisherPermissionContext middleware
 *
 * Switches spatie/laravel-permission's team context to the {publisher} route
 * param for the duration of the request, so hasRole()/can() checks inside
 * publisher-scoped routes (News\RoleController) resolve against that
 * publisher's own roles rather than the global ones. Mirrors
 * App\Http\Middleware\SetTeamPermissionContext — see its docblock for why
 * this switch is needed and why it's safe alongside Team's despite sharing
 * the same numeric scoping column (App\Support\PublisherPermissions uses a
 * distinct 'publisher' guard).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Models\NewsPublisher;
use App\Support\PermissionTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublisherPermissionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $publisher = $request->route('publisher');

        if ($publisher instanceof NewsPublisher) {
            PermissionTeam::use($publisher->id);
        }

        return $next($request);
    }
}
