<?php

/**
 * GC-Stats — Admin: Data Explorer platform-key access & usage
 *
 * The Data Explorer query screen itself is open to everyone — this is
 * specifically the per-user toggle for who may spend the *platform's*
 * shared API key (nominative list, not a role — everyone else needs their
 * own BYOK key) and a global view of this month's platform quota
 * consumption.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\User;
use App\Services\DataExplorerQuotaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DataExplorerController extends Controller
{
    public function access(Request $request): View
    {
        $search = $request->get('q');
        $statusFilter = $request->get('status');

        $users = User::query()
            ->when($search, fn ($query) => $query->matching($search))
            ->when($statusFilter === 'enabled', fn ($query) => $query->where('data_explorer_enabled', true))
            ->when($statusFilter === 'disabled', fn ($query) => $query->where('data_explorer_enabled', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.data-explorer.access', [
            'users' => $users,
            'search' => $search ?? '',
            'statusFilter' => $statusFilter ?? '',
        ]);
    }

    public function toggleAccess(Request $request, User $user): RedirectResponse
    {
        $user->update(['data_explorer_enabled' => ! $user->data_explorer_enabled]);

        activity('administration')
            ->performedOn($user)
            ->causedBy($request->user())
            ->withProperties(['data_explorer_enabled' => $user->data_explorer_enabled])
            ->log('data-explorer.access.toggled');

        return back()->with('status', 'data-explorer-access-updated');
    }

    public function usage(DataExplorerQuotaService $quota): View
    {
        return view('admin.data-explorer.usage', $quota->globalUsageSummary());
    }
}
