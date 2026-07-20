<?php

/**
 * GC-Stats — Admin: news publishers
 *
 * Site admins see and manage every publisher; a publisher's own owner/editor
 * (granted via App\Services\PublisherRoleService, guard 'publisher' — see
 * App\Support\PublisherPermissions) reaches the same `show` page for their
 * own publisher and edits within their permission ceiling. Assigning an
 * owner and setting max_permissions is site-admin only, same relationship
 * as Admin\TeamController has with Team\RoleController.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPublisher;
use App\Models\User;
use App\Services\LogoUploadService;
use App\Services\PublisherRoleService;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use App\Support\PublisherScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class NewsPublisherController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->can('news.publishers.view')) {
            $publisherId = PublisherScope::publisherIdsForUser($request->user()->id)->first();

            abort_unless($publisherId, 403);

            return redirect()->route('admin.news.publishers.show', $publisherId);
        }

        $search = $request->get('q');

        $publishers = NewsPublisher::query()
            ->withCount('news')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.news.publishers.index', [
            'publishers' => $publishers,
            'search' => $search ?? '',
        ]);
    }

    public function show(Request $request, NewsPublisher $publisher, PublisherRoleService $publisherRoles): View
    {
        $this->ensureCanView($request, $publisher);

        $publisherRoles->ensureRolesExist($publisher);

        PermissionTeam::use($publisher->id);
        $ownerRole = Role::where('team_id', $publisher->id)
            ->where('guard_name', PublisherPermissions::GUARD)
            ->where('name', PublisherRoleService::ROLE_OWNER)->first();
        $owners = $ownerRole?->users()->orderBy('name')->get() ?? collect();
        PermissionTeam::global();

        $search = $request->get('q');
        $existingOwnerIds = $ownerRole
            ? $ownerRole->users()->pluck('users.id')
            : collect();

        return view('admin.news.publishers.show', [
            'publisher' => $publisher,
            'owners' => $owners,
            'permissionGroups' => PublisherPermissions::grouped(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)->whereNotIn('id', $existingOwnerIds)->limit(10)->get()
                : collect(),
            'canEditProfile' => $this->canEditProfile($request, $publisher),
            'canUploadLogo' => $this->canUploadLogo($request, $publisher),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:news_publishers,slug'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['socials'] = array_filter($validated['socials'] ?? [], fn ($value) => filled($value));

        $publisher = NewsPublisher::create($validated);

        return redirect()->route('admin.news.publishers.show', $publisher)->with('status', 'publisher-created');
    }

    public function update(Request $request, NewsPublisher $publisher): RedirectResponse
    {
        abort_unless($this->canEditProfile($request, $publisher), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('news_publishers', 'slug')->ignore($publisher->id)],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $publisher->update([
            'name' => $validated['name'],
            'slug' => ($validated['slug'] ?? null) ?: Str::slug($validated['name']),
            'socials' => array_filter($validated['socials'] ?? [], fn ($value) => filled($value)),
        ]);

        return back()->with('status', 'publisher-updated');
    }

    public function updateLogo(Request $request, NewsPublisher $publisher, LogoUploadService $logoUploadService): RedirectResponse
    {
        abort_unless($this->canUploadLogo($request, $publisher), 403);

        $validated = $request->validate(['logo' => ['required', 'file', 'image', 'max:10240']]);

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'publishers');
        $logoUploadService->acceptReplacing($publisher, 'publisher', $uuid, 'publishers');

        return back()->with('status', 'logo-updated');
    }

    public function updateMaxPermissions(Request $request, NewsPublisher $publisher): RedirectResponse
    {
        $validated = $request->validate([
            'max_permissions' => ['array'],
            'max_permissions.*' => ['string', Rule::in(PublisherPermissions::all())],
        ]);

        $ceiling = $validated['max_permissions'] ?? [];
        $publisher->update(['max_permissions' => $ceiling]);

        PermissionTeam::use($publisher->id);
        foreach (Role::where('team_id', $publisher->id)->where('guard_name', PublisherPermissions::GUARD)->get() as $role) {
            $permissions = $role->name === PublisherRoleService::ROLE_OWNER
                ? $ceiling
                : array_intersect($role->permissions->pluck('name')->all(), $ceiling);

            $role->syncPermissions($permissions);
        }
        PermissionTeam::global();

        return back()->with('status', 'max-permissions-updated');
    }

    public function assignOwner(Request $request, NewsPublisher $publisher, PublisherRoleService $publisherRoles): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        $user = User::findOrFail($validated['user_id']);
        $publisherRoles->assign($user, $publisher, PublisherRoleService::ROLE_OWNER);

        return redirect()->route('admin.news.publishers.show', $publisher)->with('status', 'owner-assigned');
    }

    public function removeOwner(Request $request, NewsPublisher $publisher, User $user, PublisherRoleService $publisherRoles): RedirectResponse
    {
        $publisherRoles->revoke($user, $publisher, PublisherRoleService::ROLE_OWNER);

        return back()->with('status', 'owner-removed');
    }

    public function destroy(NewsPublisher $publisher): RedirectResponse
    {
        $publisher->delete();

        return redirect()->route('admin.news.publishers.index')->with('status', 'publisher-deleted');
    }

    /**
     * A site editor with news.publishers.view can see any publisher;
     * otherwise the user must hold *some* role on this specific publisher.
     */
    private function ensureCanView(Request $request, NewsPublisher $publisher): void
    {
        $user = $request->user();

        if ($user->can('news.publishers.view')) {
            return;
        }

        abort_unless(PublisherScope::publisherIdsForUser($user->id)->contains($publisher->id), 403);
    }

    /**
     * A site editor with news.publishers.edit can edit any publisher;
     * otherwise the user needs 'publisher.profile.edit' within this
     * specific publisher's own permission scope.
     */
    private function canEditProfile(Request $request, NewsPublisher $publisher): bool
    {
        return $this->hasPublisherPermission($request, $publisher, 'news.publishers.edit', 'publisher.profile.edit');
    }

    /**
     * Same idea as canEditProfile() but for the separate
     * 'publisher.logo.upload' permission — a role can be granted one
     * without the other.
     */
    private function canUploadLogo(Request $request, NewsPublisher $publisher): bool
    {
        return $this->hasPublisherPermission($request, $publisher, 'news.publishers.edit', 'publisher.logo.upload');
    }

    private function hasPublisherPermission(Request $request, NewsPublisher $publisher, string $adminPermission, string $publisherPermission): bool
    {
        $user = $request->user();

        if ($user->can($adminPermission)) {
            return true;
        }

        PermissionTeam::use($publisher->id);
        $has = $user->can($publisherPermission);
        PermissionTeam::global();

        return $has;
    }
}
