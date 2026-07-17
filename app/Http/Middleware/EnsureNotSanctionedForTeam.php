<?php

/**
 * GC-Stats — EnsureNotSanctionedForTeam middleware
 *
 * Blocks team-management actions (roster edits, team page edits, invites…)
 * for a user under an active sanction scoped to that specific team. Expects
 * a route-model-bound {team} parameter.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotSanctionedForTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $request->route('team');

        if ($user && $team instanceof Team) {
            $sanction = $user->sanctions()
                ->active()
                ->where('team_id', $team->id)
                ->latest('starts_at')
                ->first();

            if ($sanction) {
                abort(403, __('account.errors.sanctioned_team', ['reason' => $sanction->reason]));
            }
        }

        return $next($request);
    }
}
