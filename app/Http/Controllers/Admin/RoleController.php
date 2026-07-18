<?php

/**
 * GC-Stats — Admin: global role management
 *
 * Assigns/removes site-wide roles to/from users — any global role that
 * currently exists (seeded ones like super-admin/moderator/editor, or new
 * ones created from this page). Every action is scoped to roles with
 * team_id = PermissionTeam::GLOBAL_ID: spatie/laravel-permission's `roles`
 * table also holds the per-team roles (team_owner, team_manager,
 * team_editor) lazily created by TeamRoleService::ensureRolesExist() — with
 * tens of thousands of teams, an unscoped query here would flood this page
 * with thousands of duplicate-named rows and let a mutation act on the
 * wrong team's role. Team-scoped roles are managed from each team's own
 * page instead. Gated behind the 'manage-roles' gate (super-admin only),
 * stricter than any single admin permission.
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
use Illuminate\Support\Str;
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
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        activity('moderation')->causedBy($request->user())
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
                ? User::where(fn ($q) => $q->where('name', 'like', '%'.$this->escapeLike($search).'%')->orWhere('email', 'like', '%'.$this->escapeLike($search).'%'))
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

        activity('moderation')->causedBy($request->user())
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

        activity('moderation')->causedBy($request->user())
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

        activity('moderation')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.assigned');

        return back()->with('status', 'role-assigned');
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

        activity('moderation')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $role->name])->log('role.removed');

        return back()->with('status', 'role-removed');
    }

    public function updateDiscordMapping(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        $validated = $request->validate([
            'discord_role_id' => ['required', 'string', 'max:32'],
            'discord_role_name' => ['nullable', 'string', 'max:100'],
        ]);

        DiscordRoleMapping::updateOrCreate(
            ['team_id' => null, 'app_role' => $role->name],
            ['discord_role_id' => $validated['discord_role_id'], 'discord_role_name' => $validated['discord_role_name'] ?? null],
        );

        activity('moderation')->causedBy($request->user())
            ->withProperties(['role' => $role->name, 'discord_role_id' => $validated['discord_role_id']])
            ->log('role.discord_mapping_updated');

        return back()->with('status', 'discord-mapping-updated');
    }

    public function destroyDiscordMapping(Request $request, Role $role): RedirectResponse
    {
        $this->ensureGlobal($role);
        $this->ensureEditable($role);

        DiscordRoleMapping::whereNull('team_id')->where('app_role', $role->name)->delete();

        activity('moderation')->causedBy($request->user())
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

    private function escapeLike(string $value): string
    {
        return Str::of($value)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'])->toString();
    }
}
