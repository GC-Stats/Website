<?php

/**
 * GC-Stats — SetOrganizationPermissionContext middleware
 *
 * Switches spatie/laravel-permission's team context to the {organization}
 * route param for the duration of the request, so hasRole()/can() checks
 * inside organization-scoped routes resolve against that organization's own
 * roles rather than the global ones — safe despite Team/Organization rows
 * sharing the same numeric scoping column (App\Support\OrganizationPermissions
 * uses a distinct 'organization' guard).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\PermissionTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationPermissionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if ($organization instanceof Organization) {
            PermissionTeam::use($organization->id);
        }

        return $next($request);
    }
}
