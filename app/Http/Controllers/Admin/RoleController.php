<?php

/**
 * GC-Stats — Admin: global role management
 *
 * Assigns/removes global roles (super-admin/moderator/editor/custom) and
 * their permissions. Scoped to team_id = PermissionTeam::GLOBAL_ID only —
 * per-team roles (team_owner etc.) live on each team's own page. Gated by
 * the super-admin-only 'manage-roles' gate; logs under 'administration'.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscordRoleMapping;
use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')
                ->where('team_id', PermissionTeam::GLOBAL_ID)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->where('team_id', PermissionTeam::GLOBAL_ID)],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        activity('administration')->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.created');

        return redirect()->route('admin.roles.show', $role)->with('status', 'role-created');
    }

    public function show(Request $request, Role $role): View
    {
        $this->ensureGlobal($role);

        $search = $request->get('q');

        return view('admin.roles.show', [
            'role' => $role,
            'permissionGroups' => AdminPermissions::grouped(),
            'members' => $role->users()->orderBy('name')->get(),
            'discordMapping' => DiscordRoleMapping::whereNull('team_id')->where('app_role', $role->name)->first(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)
                    ->whereDoesntHave('roles', fn ($q) => $q->where('id', $role->id))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(AdminPermissions::all())],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        activity('administration')->causedBy($request->user())
            ->withProperties(['role' => $role->name, 'permissions' => $validated['permissions'] ?? []])
            ->log('role.permissions_updated');

        return back()->with('status', 'permissions-updated');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        $name = $role->name;
        $role->delete();

        activity('administration')->causedBy($request->user())
            ->withProperties(['role' => $name])->log('role.deleted');

        return redirect()->route('admin.roles.index')->with('status', 'role-deleted');
    }

    public function addMember(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->assignRole($role);

        activity('administration')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.assigned');

        return redirect()->route('admin.roles.show', $role)->with('status', 'role-assigned');
    }

    public function removeMember(Request $request, Role $role, User $user): RedirectResponse
    {
        $this->ensureGlobal($role);

        if ($user->id === $request->user()->id && $role->name === 'super-admin') {
            throw ValidationException::withMessages([
                'role' => __('admin.roles.errors.self_demote'),
            ]);
        }

        $user->removeRole($role);

        activity('administration')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.removed');

        return back()->with('status', 'role-removed');
    }

    public function updateDiscordMapping(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        $existingMapping = DiscordRoleMapping::where('team_id', null)->where('app_role', $role->name)->first();

        $validated = $request->validate([
            'discord_role_id' => ['required', 'string', 'max:32', Rule::unique('discord_role_mappings', 'discord_role_id')->ignore($existingMapping?->id)],
            'discord_role_name' => ['nullable', 'string', 'max:100'],
        ]);

        DiscordRoleMapping::updateOrCreate(
            ['team_id' => null, 'app_role' => $role->name],
            ['discord_role_id' => $validated['discord_role_id'], 'discord_role_name' => $validated['discord_role_name'] ?? null],
        );

        activity('administration')->causedBy($request->user())
            ->withProperties(['role' => $role->name, 'discord_role_id' => $validated['discord_role_id']])
            ->log('role.discord_mapping_updated');

        return back()->with('status', 'discord-mapping-updated');
    }

    public function destroyDiscordMapping(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        DiscordRoleMapping::whereNull('team_id')->where('app_role', $role->name)->delete();

        activity('administration')->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.discord_mapping_removed');

        return back()->with('status', 'discord-mapping-removed');
    }

    /**
     * Every action in this controller operates on global roles only — a
     * per-team role reaching here (e.g. a stale bookmarked URL) 404s rather
     * than being silently mutated under the wrong team context.
     */
    private function ensureGlobal(Role $role): void
    {
        abort_unless((int) $role->team_id === PermissionTeam::GLOBAL_ID, 404);
    }

    private function ensureEditable(Role $role): void
    {
        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages([
                'role' => __('admin.roles.errors.protected_role'),
            ]);
        }
    }
}
