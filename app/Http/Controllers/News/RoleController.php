<?php

/**
 * GC-Stats — Publisher: role management
 *
 * A per-publisher mirror of Team\RoleController (see its docblock for the
 * positional-binding quirk this file also relies on) — same page shape
 * (permission matrix, members, custom roles), scoped to one publisher's own
 * roles instead of the site's global ones. Reached via the
 * 'publisher.roles.manage' permission (guard 'publisher', see
 * App\Support\PublisherPermissions), which a publisher's own publisher_owner
 * holds by default (see PublisherRoleService) but isn't hardcoded — a site
 * admin can grant or revoke it per publisher, independently of every other
 * publisher.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Models\NewsPublisher;
use App\Models\User;
use App\Services\PublisherRoleService;
use App\Support\PublisherPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(NewsPublisher $publisher, PublisherRoleService $publisherRoles): View
    {
        $publisherRoles->ensureRolesExist($publisher);

        return view('news.roles.index', [
            'publisher' => $publisher,
            'roles' => Role::withCount('users')->where('team_id', $publisher->id)
                ->where('guard_name', PublisherPermissions::GUARD)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, NewsPublisher $publisher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('roles', 'name')->where('team_id', $publisher->id)->where('guard_name', PublisherPermissions::GUARD)],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => PublisherPermissions::GUARD]);

        activity('publisher')->causedBy($request->user())
            ->withProperties(['publisher_id' => $publisher->id, 'role' => $role->name])->log('publisher_role.created');

        return redirect()->route('admin.news.publishers.roles.show', [$publisher, $role])->with('status', 'role-created');
    }

    public function show(Request $request, NewsPublisher $publisher, Role $role): View
    {
        $this->ensureBelongsToPublisher($publisher, $role);

        $search = $request->get('q');

        return view('news.roles.show', [
            'publisher' => $publisher,
            'role' => $role,
            'permissionGroups' => PublisherPermissions::groupedWithin($publisher->maxPermissions()),
            'members' => $role->users()->orderBy('name')->get(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)
                    ->whereDoesntHave('roles', fn ($q) => $q->where('id', $role->id))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, NewsPublisher $publisher, Role $role): RedirectResponse
    {
        $this->ensureBelongsToPublisher($publisher, $role);

        if ($role->name === PublisherRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.news.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($publisher->maxPermissions())],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        activity('publisher')->causedBy($request->user())
            ->withProperties(['publisher_id' => $publisher->id, 'role' => $role->name, 'permissions' => $validated['permissions'] ?? []])
            ->log('publisher_role.permissions_updated');

        return back()->with('status', 'permissions-updated');
    }

    public function destroy(Request $request, NewsPublisher $publisher, Role $role): RedirectResponse
    {
        $this->ensureBelongsToPublisher($publisher, $role);

        if ($role->name === PublisherRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.news.roles.errors.owner_role_protected'),
            ]);
        }

        $name = $role->name;
        $role->delete();

        activity('publisher')->causedBy($request->user())
            ->withProperties(['publisher_id' => $publisher->id, 'role' => $name])->log('publisher_role.deleted');

        return redirect()->route('admin.news.publishers.roles.index', $publisher)->with('status', 'role-deleted');
    }

    public function addMember(Request $request, NewsPublisher $publisher, Role $role): RedirectResponse
    {
        $this->ensureBelongsToPublisher($publisher, $role);

        if ($role->name === PublisherRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.news.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->assignRole($role);

        activity('publisher')->performedOn($user)->causedBy($request->user())
            ->withProperties(['publisher_id' => $publisher->id, 'role' => $role->name])->log('publisher_role.assigned');

        return redirect()->route('admin.news.publishers.roles.show', [$publisher, $role])->with('status', 'role-assigned');
    }

    public function removeMember(Request $request, NewsPublisher $publisher, Role $role, User $user): RedirectResponse
    {
        $this->ensureBelongsToPublisher($publisher, $role);

        if ($role->name === PublisherRoleService::ROLE_OWNER && $role->users()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => __('admin.news.roles.errors.last_owner'),
            ]);
        }

        $user->removeRole($role);

        activity('publisher')->performedOn($user)->causedBy($request->user())
            ->withProperties(['publisher_id' => $publisher->id, 'role' => $role->name])->log('publisher_role.removed');

        return back()->with('status', 'role-removed');
    }

    /**
     * A role reaching here belonging to a *different* publisher (or to
     * Team's 'web' guard instead of 'publisher') 404s rather than being
     * mutated under the wrong context.
     */
    private function ensureBelongsToPublisher(NewsPublisher $publisher, Role $role): void
    {
        abort_unless((int) $role->team_id === $publisher->id && $role->guard_name === PublisherPermissions::GUARD, 404);
    }
}
