<?php

/**
 * GC-Stats — Dashboard context resolution
 *
 * Shared by the news controllers that back three route trees off the same
 * actions: flat admin.* (cross-organization), organization-dashboard.*
 * (scoped to the {organization} bound in the URL) and personal-dashboard.*
 * (scoped to the current user's own NewsAuthor). isDashboard()/
 * isPersonalDashboard() sniff which tree the current request came from;
 * dashboardContext() picks between three caller-supplied values the same
 * way, for routePrefix()/viewName() implementations that otherwise differ
 * per controller (different route/view names).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

trait ResolvesDashboardContext
{
    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function isPersonalDashboard(): bool
    {
        return request()->routeIs('personal-dashboard.*');
    }

    private function dashboardContext(string $dashboard, string $personalDashboard, string $admin): string
    {
        return match (true) {
            $this->isDashboard() => $dashboard,
            $this->isPersonalDashboard() => $personalDashboard,
            default => $admin,
        };
    }
}
