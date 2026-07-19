<?php

/**
 * GC-Stats — Admin: dashboard entry point
 *
 * `/admin` itself isn't a page — it sends the user to the first section
 * their permissions actually grant, since which admin permissions a role
 * holds can vary (see App\Support\AdminPermissions).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->can('reports.view') => redirect()->route('admin.reports.index'),
            $user->can('sanctions.view') => redirect()->route('admin.sanctions.index'),
            $user->can('activity.view') => redirect()->route('admin.activity.index'),
            $user->can('teams.view') => redirect()->route('admin.teams.index'),
            $user->can('manage-roles') => redirect()->route('admin.roles.index'),

            default => redirect()->route('home')->with('status', 'no-admin-section'),
        };
    }
}
