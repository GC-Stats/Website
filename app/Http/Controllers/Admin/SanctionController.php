<?php

/**
 * GC-Stats — Admin: sanctions
 *
 * Listing requires `sanctions.view`, issuing requires `sanctions.create`,
 * revoking requires `sanctions.revoke`, and permanently deleting requires
 * `sanctions.delete` (all gated at the route level, see routes/admin.php).
 * Revoke deactivates a sanction but keeps the record (and its
 * SanctionIdentity evasion fingerprints) for the audit trail; delete
 * erases it entirely — meant for corrections, not routine lifting.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sanction;
use App\Models\User;
use App\Services\SanctionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SanctionController extends Controller
{
    private const TYPES = [
        Sanction::TYPE_WARNING,
        Sanction::TYPE_MUTE,
        Sanction::TYPE_SUSPENSION,
        Sanction::TYPE_BAN,
    ];

    public function index(Request $request): View
    {
        $sanctions = Sanction::with(['user:id,name', 'issuedBy:id,name', 'team:id,name'])
            ->when(! $request->boolean('all'), fn ($query) => $query->active())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.sanctions.index', [
            'sanctions' => $sanctions,
            'showAll' => $request->boolean('all'),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request, SanctionService $sanctions): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'reason' => ['required', 'string', 'max:2000'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $sanctions->issue($user, $request->user(), [
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return back()->with('status', 'sanction-issued');
    }

    public function destroy(Request $request, Sanction $sanction, SanctionService $sanctions): RedirectResponse
    {
        $sanctions->revoke($sanction, $request->user());

        return back()->with('status', 'sanction-revoked');
    }

    public function forceDestroy(Request $request, Sanction $sanction, SanctionService $sanctions): RedirectResponse
    {
        $sanctions->delete($sanction, $request->user());

        return back()->with('status', 'sanction-deleted');
    }
}
