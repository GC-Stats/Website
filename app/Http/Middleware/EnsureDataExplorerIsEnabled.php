<?php

/**
 * GC-Stats — EnsureDataExplorerIsEnabled middleware
 *
 * Data Explorer is currently in early access: only users with the
 * per-user data_explorer_enabled column set to true (toggled from
 * admin/data-explorer/access, see Admin\DataExplorerController) can reach
 * it — everyone else gets the early-access placeholder instead. Runs
 * after 'auth', so $request->user() is always present here.
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

class EnsureDataExplorerIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->data_explorer_enabled) {
            return response()->view('data-explorer.early-access', status: 200);
        }

        return $next($request);
    }
}
