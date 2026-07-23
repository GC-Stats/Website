<?php

/**
 * GC-Stats — EnsureAccountIsNotSanctioned middleware
 *
 * Blocks sensitive actions for a user under an active global (suspension or
 * ban) sanction. Team-scoped sanctions (issued with a team_id) are enforced
 * separately by EnsureNotSanctionedForTeam, since they should only restrict
 * actions on that specific team, not the whole account.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsNotSanctioned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $sanction = $user->activeGlobalBlockingSanction();

            if ($sanction) {
                abort(403, __('account.errors.sanctioned_global', ['reason' => $sanction->reason]));
            }
        }

        return $next($request);
    }
}
