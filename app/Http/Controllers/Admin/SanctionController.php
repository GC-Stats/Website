<?php

/**
 * GC-Stats — Admin: sanctions
 *
 * List/issue/revoke/delete, one permission per action (see routes/admin.php).
 * Revoke deactivates but keeps the record; delete erases it entirely.
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

    private const SORTABLE = ['user', 'type', 'reason', 'ends_at', 'issued_by'];

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'created_at', 'desc');

        $sanctions = Sanction::with(['user:id,name', 'issuedBy:id,name', 'team:id,name'])
            ->when(! $request->boolean('all'), fn ($query) => $query->active())
            ->when($sort === 'user', fn ($query) => $query
                ->select('sanctions.*')
                ->leftJoin('users as sanctioned_users', 'sanctioned_users.id', '=', 'sanctions.user_id')
                ->orderBy('sanctioned_users.name', $direction))
            ->when($sort === 'issued_by', fn ($query) => $query
                ->select('sanctions.*')
                ->leftJoin('users as issuers', 'issuers.id', '=', 'sanctions.issued_by')
                ->orderBy('issuers.name', $direction))
            ->when($sort === 'type', fn ($query) => $query->orderBy('type', $direction))
            ->when($sort === 'reason', fn ($query) => $query->orderBy('reason', $direction))
            ->when($sort === 'ends_at', fn ($query) => $query->orderBy('ends_at', $direction))
            ->when($sort === 'created_at', fn ($query) => $query->orderByDesc('created_at'))
            ->orderByDesc('sanctions.id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.sanctions.index', [
            'sanctions' => $sanctions,
            'showAll' => $request->boolean('all'),
            'types' => self::TYPES,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function store(Request $request, SanctionService $sanctions): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'reason' => ['required', 'string', 'max:2000'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::where('username', $validated['username'])->firstOrFail();

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
