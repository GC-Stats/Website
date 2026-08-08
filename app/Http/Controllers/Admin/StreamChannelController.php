<?php

/**
 * GC-Stats — Admin: stream channels
 *
 * CRUD for streaming channels (YouTube/Twitch/TikTok) that can be linked to
 * matches (see Admin\MatchStreamController). Reachable by site editors
 * (streams.channels.*) or by an organization's own member with the matching
 * 'organization.streams.*' permission on the channel's organization (guard
 * 'organization', see App\Support\OrganizationPermissions). A channel
 * with no organization_id is an admin-only channel, same pattern as
 * News::organization_id.
 *
 * Dual-context like Admin\NewsController: this same controller backs both
 * admin.streams.* (flat, cross-organization) and
 * organization-dashboard.streams.* (scoped to the {organization} bound in
 * the dashboard URL) — see that controller's docblock for the general
 * pattern and resources/views/streams/*.blade.php for the shared content.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\Organization;
use App\Models\StreamChannel;
use App\Services\OrganizationAccessService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\OrganizationScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StreamChannelController extends Controller
{
    private const SORTABLE = ['name', 'platform', 'language_code', 'organization'];

    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.streams.' : 'admin.streams.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.streams.{$page}" : "admin.streams.{$page}";
    }

    public function index(Request $request, ?Organization $organization = null): View
    {
        $user = $request->user();

        if ($organization) {
            $access = app(OrganizationAccessService::class);
            abort_unless($access->hasOrganizationPermission($user, $organization, 'streams.channels.view', 'organization.streams.view'), 403);

            $allowedOrganizationIds = collect([$organization->id]);
            $editableOrganizationIds = $access->hasOrganizationPermission($user, $organization, 'streams.channels.edit', 'organization.streams.edit') ? collect([$organization->id]) : collect();
            $deletableOrganizationIds = $access->hasOrganizationPermission($user, $organization, 'streams.channels.delete', 'organization.streams.delete') ? collect([$organization->id]) : collect();
            $canCreate = $access->hasOrganizationPermission($user, $organization, 'streams.channels.create', 'organization.streams.edit');
        } else {
            $allowedOrganizationIds = $user->can('streams.channels.view') ? null : $this->allowedOrganizationIds($request, 'organization.streams.view');
            abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

            $editableOrganizationIds = $user->can('streams.channels.edit') ? null : $this->allowedOrganizationIds($request, 'organization.streams.edit');
            $deletableOrganizationIds = $user->can('streams.channels.delete') ? null : $this->allowedOrganizationIds($request, 'organization.streams.delete');
            $canCreate = $user->can('streams.action.create');
        }

        $search = $request->get('q');
        $platform = $request->get('platform');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'name', 'asc');

        $channels = StreamChannel::query()
            ->with('organization')
            ->when($organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($platform, fn ($query) => $query->where('platform', $platform))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->when($sort === 'platform', fn ($query) => $query->orderBy('platform', $direction))
            ->when($sort === 'language_code', fn ($query) => $query->orderBy('language_code', $direction))
            ->when($sort === 'organization', fn ($query) => $query
                ->select('stream_channels.*')
                ->leftJoin('organization', 'organization.id', '=', 'stream_channels.organization_id')
                ->orderBy('organization.name', $direction))
            ->orderByDesc('stream_channels.id')
            ->paginate(25)
            ->withQueryString();

        return view($this->viewName('index'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            'channels' => $channels,
            'search' => $search ?? '',
            'platform' => $platform ?? '',
            'sort' => $sort,
            'direction' => $direction,
            'platforms' => StreamChannel::PLATFORMS,
            'canCreate' => $canCreate,
            'editableOrganizationIds' => $editableOrganizationIds,
            'deletableOrganizationIds' => $deletableOrganizationIds,
        ]);
    }

    public function create(Request $request, ?Organization $organization = null): View
    {
        $allowedOrganizationIds = $this->creatableOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        return view($this->viewName('create'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            ...$this->formData(null, $allowedOrganizationIds, $organization),
        ]);
    }

    public function store(Request $request, ?Organization $organization = null): RedirectResponse
    {
        $allowedOrganizationIds = $this->creatableOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        $validated = $this->validated($request, null, $allowedOrganizationIds, $organization);

        $channel = StreamChannel::create($validated);

        activity('publisher')->performedOn($channel)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($channel, array_keys($validated))->toArray())
            ->log('stream_channel.created');

        return redirect()->route($this->routePrefix().'edit', $organization ? [$organization, $channel] : $channel)
            ->with('status', 'channel-created');
    }

    public function edit(Request $request, ?Organization $organization = null, ?StreamChannel $channel = null): View
    {
        $channel = $this->requiredChannel($channel);
        $this->ensureBelongsToOrganization($channel, $organization);
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.edit', 'organization.streams.edit');

        $restricted = ! $request->user()->can('streams.channels.edit');
        $allowedOrganizationIds = $organization
            ? collect([$organization->id])
            : ($restricted ? $this->allowedOrganizationIds($request, 'organization.streams.edit') : null);

        return view($this->viewName('edit'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            ...$this->formData($channel, $allowedOrganizationIds, $organization),
        ]);
    }

    public function update(Request $request, ?Organization $organization = null, ?StreamChannel $channel = null): RedirectResponse
    {
        $channel = $this->requiredChannel($channel);
        $this->ensureBelongsToOrganization($channel, $organization);
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.edit', 'organization.streams.edit');

        $restricted = ! $request->user()->can('streams.channels.edit');
        $allowedOrganizationIds = $organization
            ? collect([$organization->id])
            : ($restricted ? $this->allowedOrganizationIds($request, 'organization.streams.edit') : null);

        $validated = $this->validated($request, $channel, $allowedOrganizationIds, $organization);

        $channel->update($validated);

        activity('publisher')->performedOn($channel)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($channel, array_keys($validated))->toArray())
            ->log('stream_channel.updated');

        return back()->with('status', 'channel-updated');
    }

    public function destroy(Request $request, ?Organization $organization = null, ?StreamChannel $channel = null): RedirectResponse
    {
        $channel = $this->requiredChannel($channel);
        $this->ensureBelongsToOrganization($channel, $organization);
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.delete', 'organization.streams.delete');

        $name = $channel->name;
        $channel->delete();

        activity('publisher')->causedBy($request->user())
            ->withProperties(['name' => $name])
            ->log('stream_channel.deleted');

        return redirect()->route($this->routePrefix().'index', $organization ?? [])->with('status', 'channel-deleted');
    }

    /**
     * @return Collection<int, int>
     */
    private function allowedOrganizationIds(Request $request, string $organizationPermission): Collection
    {
        return OrganizationScope::organizationIdsWithPermission($request->user()->id, $organizationPermission);
    }

    /**
     * @return Collection<int, int>|null null means unrestricted (site editor)
     */
    private function creatableOrganizationIds(Request $request, ?Organization $organization): ?Collection
    {
        if ($organization) {
            $allowed = app(OrganizationAccessService::class)
                ->hasOrganizationPermission($request->user(), $organization, 'streams.channels.create', 'organization.streams.edit');

            return $allowed ? collect([$organization->id]) : collect();
        }

        return $request->user()->can('streams.channels.create') ? null : $this->allowedOrganizationIds($request, 'organization.streams.edit');
    }

    private function ensureCanManageChannel(Request $request, StreamChannel $channel, string $adminPermission, string $organizationPermission): void
    {
        $user = $request->user();

        if ($user->can($adminPermission)) {
            return;
        }

        $allowed = $channel->organization_id
            && $this->allowedOrganizationIds($request, $organizationPermission)->contains($channel->organization_id);

        abort_unless($allowed, 403);
    }

    /**
     * @param  Collection<int, int>|null  $allowedOrganizationIds  when not null, 'organization_id' must be one of these
     */
    private function validated(Request $request, ?StreamChannel $channel, ?Collection $allowedOrganizationIds, ?Organization $organization = null): array
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organization,id'],
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', Rule::in(StreamChannel::PLATFORMS)],
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($organization) {
            // Dashboard mode: attribution isn't a user choice — force it
            // regardless of what was submitted rather than trusting a
            // hidden field, same as Admin\NewsController::validated().
            $validated['organization_id'] = $organization->id;

            return $validated;
        }

        $organizationId = $validated['organization_id'] ?? null;
        $organizationUnchanged = $channel && (int) $organizationId === (int) $channel->organization_id;

        if ($allowedOrganizationIds !== null) {
            if (! $organizationId) {
                // An organization-scoped editor can't create/leave a channel
                // admin-only (organization_id null) — it must belong to one
                // of their own organizations.
                abort_unless($allowedOrganizationIds->count() === 1, 422);
                $validated['organization_id'] = $allowedOrganizationIds->first();
            } elseif (! $organizationUnchanged) {
                abort_unless($allowedOrganizationIds->contains($organizationId), 403);
            }
        }

        return $validated;
    }

    /**
     * @param  Collection<int, int>|null  $restrictToOrganizationIds  when not null, the organization picker only lists these
     */
    private function formData(?StreamChannel $channel, ?Collection $restrictToOrganizationIds, ?Organization $organization = null): array
    {
        return [
            'channel' => $channel,
            // In dashboard mode this is always "restricted" to exactly the
            // one organization bound in the URL, same hidden-input path a
            // single-organization admin editor already goes through — see
            // resources/views/streams/_form.blade.php.
            'restricted' => $restrictToOrganizationIds !== null,
            'platforms' => StreamChannel::PLATFORMS,
            'countries' => app(Countries::class)->list(),
            'organizations' => Organization::query()
                ->when($restrictToOrganizationIds !== null, fn ($query) => $query->whereIn('id', $restrictToOrganizationIds))
                ->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * A channel reaching here belonging to a *different* organization than
     * the one bound in the dashboard URL 404s — mirrors
     * Admin\NewsController::ensureBelongsToOrganization(). No-op in admin
     * mode ($organization null).
     */
    private function ensureBelongsToOrganization(StreamChannel $channel, ?Organization $organization): void
    {
        if ($organization) {
            abort_unless((int) $channel->organization_id === $organization->id, 404);
        }
    }

    /**
     * $channel is only nullable so its position can match $organization's —
     * see Admin\NewsController::requiredArticle()'s docblock for why.
     */
    private function requiredChannel(?StreamChannel $channel): StreamChannel
    {
        abort_unless($channel, 404);

        return $channel;
    }
}
