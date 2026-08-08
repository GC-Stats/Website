<?php

/**
 * GC-Stats — Admin & organization dashboard: API keys
 *
 * List/create/edit/toggle/regenerate client API keys. The clear key value
 * is never stored — only a SHA-256 hash (App\Models\ApiKey::hashKey()) — and
 * is shown to the operator exactly once via the single-use reveal link
 * (App\Models\ApiKeyReveal, routes/web.php `api-keys.reveal`).
 *
 * A key belongs either to a single user or to an organization — never both
 * (see the organization_id/user_id columns). Only site admins can create a
 * key or change its owner/rate-limit/status: the owner is picked via the
 * generic <livewire:entity-picker> (type 'user' or 'organization', see
 * resources/views/api-keys/_index.blade.php) instead of a free-text
 * username, submitted as owner_type + owner_id_{$type}, mirroring
 * Admin\ChangeRequestController's subject_type/subject_id_{$type} pattern.
 *
 * index()/regenerate() additionally back organization-dashboard.api-keys.*
 * (an organization's own dashboard, extends organization.layout, same
 * dual-context pattern as Admin\StreamChannelController) — an organization
 * can only view its own keys and regenerate them, never create, rename, or
 * (de)activate one; those routes simply don't exist under
 * organization-dashboard.*, see routes/organization.php.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyReveal;
use App\Models\Organization;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    private const SORTABLE = ['client_name', 'rate_limit', 'status'];

    private const OWNER_TYPES = ['user', 'organization'];

    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.api-keys.' : 'admin.api-keys.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.api-keys.{$page}" : "admin.api-keys.{$page}";
    }

    public function index(Request $request, ?Organization $organization = null): View
    {
        $search = $request->get('q');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'created_at', 'asc');

        $keys = ApiKey::query()
            ->with(['user:id,name,username', 'organization:id,name,slug'])
            ->when($organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->when($search, fn ($query) => $query->where('client_name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($sort === 'client_name', fn ($query) => $query->orderBy('client_name', $direction))
            ->when($sort === 'rate_limit', fn ($query) => $query->orderBy('rate_limit', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('is_active', $direction))
            ->when($sort === 'created_at', fn ($query) => $query->orderByDesc('created_at'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view($this->viewName('index'), [
            'keys' => $keys,
            'search' => $search ?? '',
            'sort' => $sort,
            'direction' => $direction,
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    /**
     * Admin-only — no organization-dashboard route exists for this action.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedOwner($request, [
            'client_name' => ['required', 'string', 'min:3', 'max:50'],
            'rate_limit' => ['required', 'integer', 'min:1'],
        ]);

        $revealUrl = DB::transaction(function () use ($request, $validated) {
            $clearKey = $this->generateClearKey();

            $key = ApiKey::create([
                'user_id' => $validated['user_id'],
                'organization_id' => $validated['organization_id'],
                'client_name' => $validated['client_name'],
                'rate_limit' => $validated['rate_limit'],
                'is_active' => true,
                'key_hash' => ApiKey::hashKey($clearKey),
            ]);

            activity('administration')->causedBy($request->user())
                ->performedOn($key)
                ->withProperties(ActivityChangeSet::fromCreated($key, ['client_name', 'rate_limit', 'user_id', 'organization_id', 'is_active'])->toArray())
                ->log('api_key.created');

            return route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token);
        });

        return back()->with('status', 'api-key-created')->with('reveal_url', $revealUrl);
    }

    /**
     * Admin-only — no organization-dashboard route exists for this action.
     */
    public function update(Request $request, ApiKey $key): RedirectResponse
    {
        $validated = $this->validatedOwner($request, [
            'client_name' => ['required', 'string', 'min:3', 'max:50'],
            'rate_limit' => ['required', 'integer', 'min:1'],
        ]);

        $key->update([
            'client_name' => $validated['client_name'],
            'rate_limit' => $validated['rate_limit'],
            'user_id' => $validated['user_id'],
            'organization_id' => $validated['organization_id'],
        ]);

        activity('administration')->causedBy($request->user())
            ->performedOn($key)
            ->withProperties(ActivityChangeSet::fromModel($key, ['client_name', 'rate_limit', 'user_id', 'organization_id'])->toArray())
            ->log('api_key.updated');

        return back()->with('status', 'api-key-updated');
    }

    /**
     * Admin-only — no organization-dashboard route exists for this action.
     */
    public function toggleStatus(Request $request, ApiKey $key): RedirectResponse
    {
        $key->update(['is_active' => ! $key->is_active]);

        activity('administration')->causedBy($request->user())
            ->performedOn($key)
            ->withProperties(ActivityChangeSet::fromModel($key, ['is_active'])->toArray())
            ->log('api_key.toggled');

        return back()->with('status', 'api-key-toggled');
    }

    /**
     * Reads owner_type + owner_id_{type} (submitted by the entity-picker
     * toggle, see resources/views/api-keys/_index.blade.php) and resolves
     * them into exactly one of user_id/organization_id, merged into the
     * given base validation rules.
     *
     * @param  array<string, list<string>>  $rules
     * @return array<string, mixed>
     */
    private function validatedOwner(Request $request, array $rules): array
    {
        $validated = $request->validate($rules + [
            'owner_type' => ['required', 'string', Rule::in(self::OWNER_TYPES)],
            'owner_id_user' => ['nullable', 'integer', 'exists:users,id'],
            'owner_id_organization' => ['nullable', 'integer', 'exists:organization,id'],
        ]);

        $ownerId = $validated['owner_id_'.$validated['owner_type']] ?? null;
        abort_unless($ownerId, 422, 'An owner must be selected.');

        $validated['user_id'] = $validated['owner_type'] === 'user' ? $ownerId : null;
        $validated['organization_id'] = $validated['owner_type'] === 'organization' ? $ownerId : null;

        return $validated;
    }

    public function regenerate(Request $request, ?Organization $organization = null, ?ApiKey $key = null): RedirectResponse
    {
        $key = $this->requiredKey($key);
        $this->ensureBelongsToOrganization($key, $organization);

        $revealUrl = DB::transaction(function () use ($key) {
            $clearKey = $this->generateClearKey();

            // Overwriting the hash invalidates the old key immediately.
            $key->update(['key_hash' => ApiKey::hashKey($clearKey)]);

            return route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token);
        });

        // The hash itself is never logged (see class docblock) — only that a
        // regeneration happened, for whom, and when.
        activity('administration')->causedBy($request->user())
            ->performedOn($key)
            ->withProperties(['client_name' => $key->client_name])
            ->log('api_key.regenerated');

        return back()->with('status', 'api-key-regenerated')->with('reveal_url', $revealUrl);
    }

    private function generateClearKey(): string
    {
        return 'GCS_'.Str::random(32);
    }

    /**
     * Trailing-optional-param workaround (needed to keep ?Organization
     * ahead of ?ApiKey in the signature so implicit route-model-binding,
     * which is positional by URL order not parameter name, resolves both
     * correctly — see Admin\NewsController::requiredArticle()'s docblock)
     * — the key itself is never actually absent when the route matched.
     */
    private function requiredKey(?ApiKey $key): ApiKey
    {
        abort_unless($key, 404);

        return $key;
    }

    /**
     * A key reaching here belonging to a *different* organization 404s
     * rather than being mutated under the wrong dashboard context. Site
     * admins (no $organization bound) can still touch any key, including
     * organization-owned ones.
     */
    private function ensureBelongsToOrganization(ApiKey $key, ?Organization $organization): void
    {
        if ($organization) {
            abort_unless((int) $key->organization_id === $organization->id, 404);
        }
    }
}
