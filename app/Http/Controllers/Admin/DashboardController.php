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
use App\Support\PublisherScope;
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
            $user->can('players.view') => redirect()->route('admin.players.index'),
            $user->can('news.view') => redirect()->route('admin.news.index'),
            $user->can('news.publishers.view') => redirect()->route('admin.news.publishers.index'),
            $user->can('news.authors.view') => redirect()->route('admin.news.authors.index'),
            $user->can('news.media.view') => redirect()->route('admin.news.media.index'),
            $user->can('manage-roles') => redirect()->route('admin.roles.index'),

            PublisherScope::publisherIdsForUser($user->id)->isNotEmpty() => redirect()->route('admin.news.publishers.index'),
            $user->newsAuthor()->exists() => redirect()->route('admin.news.authors.index'),

            default => redirect()->route('home')->with('status', 'no-admin-section'),
        };
    }
}
