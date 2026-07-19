<?php

/**
 * GC-Stats — SetTeamPermissionContext middleware
 *
 * Switches spatie/laravel-permission's team context to the {team} route
 * param for the duration of the request, so hasRole()/can() checks inside
 * team-scoped routes (Team\RoleController) resolve against that team's own
 * roles rather than the global ones. Site-wide checks (isSuperAdmin(),
 * Gate::before) are deliberately context-independent, see User::isSuperAdmin().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Models\Team;
use App\Support\PermissionTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTeamPermissionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('team');

        if ($team instanceof Team) {
            PermissionTeam::use($team->id);
        }

        return $next($request);
    }
}
