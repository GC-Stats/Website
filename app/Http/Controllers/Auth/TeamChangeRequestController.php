<?php

/**
 * GC-Stats — Team change request controller
 *
 * Lets any authenticated user propose a correction on a team's public page —
 * profile fields, socials, logo, and roster (editing/closing out an active
 * entry, adding one or more new players, or correcting a past roster-history
 * entry) — via a dedicated page, exactly mirroring
 * App\Http\Controllers\Auth\PlayerChangeRequestController. There is no
 * self-service team-editing anymore: every proposal, from anyone, goes
 * through the moderated ChangeRequest queue (see
 * App\Http\Controllers\Admin\ChangeRequestController) — a proposed field is
 * never written to the team directly. vlr_id is deliberately not exposed
 * here: it's an internal-use identifier (see its tooltip in
 * team/_profile-form.blade.php, the admin-only form), not something a
 * suggester has grounds to correct — admins edit it directly instead. An
 * uploaded logo is stored to disk immediately (see
 * LogoUploadService::storeLogoPair()) but only linked to the team once
 * accepted (see App\Services\ChangeRequests\Appliers\TeamLogoApplier).
 *
 * The roster/new-players rows are added and removed entirely client-side
 * (see public.team.change-request's Alpine state) — nothing here fires
 * until the whole form, including every queued add/remove, is submitted at
 * once. "Removing" an active roster row isn't a delete (the change-request
 * system only ever edits player_team rows in place, never deletes them —
 * see RosterService::updateEntryForTeam()) — it proposes closing the row
 * out as of today, same as manually clearing it to a past left_at.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Public\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Services\ChangeRequestService;
use App\Services\LogoUploadService;
use App\Services\RosterService;
use App\Support\Countries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamChangeRequestController extends Controller
{
    /** Fields a change request may propose — TeamProfileService::updateProfile()'s information fields, minus vlr_id (see class docblock). */
    private const EDITABLE_FIELDS = ['name', 'short_name', 'country_code', 'bio', 'liquipedia_link'];

    /** Social platforms the header/profile form both understand — see resources/views/team/_profile-form.blade.php. */
    private const SOCIAL_PLATFORMS = ['twitter', 'twitch', 'instagram', 'youtube', 'tiktok', 'discord', 'website'];

    /** Pre-rendered "add a new player" slots on the change-request page — see its own docblock for why this can't just be an unbounded x-for. */
    public const MAX_NEW_PLAYERS = 6;

    public function create(Team $team, RosterService $roster): View
    {
        $history = $roster->history($team->id);

        return view('public.team.change-request', [
            'team' => $team,
            'countries' => app(Countries::class)->list(),
            'roles' => __('team.roster.roles'),
            'socialPlatforms' => self::SOCIAL_PLATFORMS,
            'roster' => $history->whereNull('left_at')->values(),
            'rosterHistory' => $history->whereNotNull('left_at')->values(),
        ]);
    }

    public function store(Request $request, Team $team, ChangeRequestService $changeRequests, RosterService $roster, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:10240'],
            'roster' => ['nullable', 'array'],
            'roster.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'roster.*.joined_at' => ['nullable', 'date'],
            'roster.*.removed' => ['nullable', 'boolean'],
            'new_players' => ['nullable', 'array', 'max:'.self::MAX_NEW_PLAYERS],
            'new_players.*.player_id' => ['nullable', 'integer', 'exists:players,id'],
            'new_players.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'new_players.*.joined_at' => ['nullable', 'date'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'history.*.joined_at' => ['nullable', 'date'],
            'history.*.left_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'logo.max' => __('team.change_request.errors.logo_too_large'),
        ]);

        $items = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            $newValue = $field === 'country_code'
                ? (blank($validated[$field] ?? null) ? null : strtolower($validated[$field]))
                : ($validated[$field] ?? null);

            if ($newValue !== $team->{$field}) {
                $items[] = [
                    'field' => $field,
                    'old_value' => $team->{$field},
                    'new_value' => $newValue,
                ];
            }
        }

        $newSocials = collect(self::SOCIAL_PLATFORMS)
            ->mapWithKeys(fn ($platform) => [$platform => trim((string) ($validated['socials'][$platform] ?? ''))])
            ->filter(fn ($value) => $value !== '')
            ->all();

        $currentSocials = collect($team->socials ?? [])
            ->only(self::SOCIAL_PLATFORMS)
            ->filter(fn ($value) => filled($value))
            ->all();

        ksort($newSocials);
        ksort($currentSocials);

        if ($newSocials !== $currentSocials) {
            $items[] = [
                'field' => 'socials',
                'old_value' => $currentSocials,
                'new_value' => $newSocials,
            ];
        }

        $allRows = $roster->history($team->id)->keyBy('id');

        if (! empty($validated['roster'])) {
            foreach ($validated['roster'] as $rowId => $entry) {
                $row = $allRows->get((int) $rowId);

                if (! $row || $row->left_at !== null) {
                    continue;
                }

                $removed = (bool) ($entry['removed'] ?? false);
                $newRole = $entry['role'] ?? $row->role;
                $newJoinedAt = $entry['joined_at'] ?? $row->joined_at;
                $newLeftAt = $removed ? now()->toDateString() : null;

                if ($newRole === $row->role && $newJoinedAt === $row->joined_at && $newLeftAt === $row->left_at) {
                    continue;
                }

                $items[] = $this->rosterHistoryItem($row, $newRole, $newJoinedAt, $newLeftAt);
            }
        }

        if (! empty($validated['history'])) {
            foreach ($validated['history'] as $rowId => $entry) {
                $row = $allRows->get((int) $rowId);

                if (! $row || $row->left_at === null) {
                    continue;
                }

                $newRole = $entry['role'] ?? $row->role;
                $newJoinedAt = $entry['joined_at'] ?? $row->joined_at;
                $newLeftAt = filled($entry['left_at'] ?? null) ? $entry['left_at'] : null;

                if ($newRole === $row->role && $newJoinedAt === $row->joined_at && $newLeftAt === $row->left_at) {
                    continue;
                }

                $items[] = $this->rosterHistoryItem($row, $newRole, $newJoinedAt, $newLeftAt);
            }
        }

        foreach ($validated['new_players'] ?? [] as $entry) {
            if (empty($entry['player_id'])) {
                continue;
            }

            if (empty($entry['joined_at'])) {
                return back()->withErrors(['new_players' => __('team.change_request.errors.joined_at_required')])->withInput();
            }

            $player = Player::findOrFail($entry['player_id']);

            $items[] = [
                'field' => 'roster_add',
                'old_value' => null,
                'new_value' => [
                    'player_id' => $player->id,
                    'player_handle' => $player->handle,
                    'role' => $entry['role'] ?? 'player',
                    'joined_at' => $entry['joined_at'],
                ],
            ];
        }

        // Deferred until every validation-driven early return above has
        // passed, so a rejected submission never leaves an orphaned upload
        // on disk (see LogoUploadService::storeLogoPair()).
        if ($request->hasFile('logo')) {
            $logoId = $logoUploadService->storeLogoPair($request->file('logo'), 'teams');

            $items[] = [
                'field' => 'logo',
                'old_value' => ['current_logo' => $team->logo],
                'new_value' => ['logo_id' => $logoId],
            ];
        }

        if (empty($items)) {
            return back()->withErrors(['change_request' => __('team.change_request.errors.no_changes')])->withInput();
        }

        $changeRequests->create($team, $request->user(), $validated['note'] ?? null, $items);

        return back()->with('status', 'change-request-submitted');
    }

    /**
     * @return array{field: string, old_value: array, new_value: array}
     */
    private function rosterHistoryItem(object $row, ?string $newRole, ?string $newJoinedAt, ?string $newLeftAt): array
    {
        return [
            'field' => 'roster_history',
            'old_value' => [
                'row_id' => $row->id,
                'player_id' => $row->player_id,
                'player_handle' => $row->player_handle,
                'role' => $row->role,
                'joined_at' => $row->joined_at,
                'left_at' => $row->left_at,
            ],
            'new_value' => [
                'row_id' => $row->id,
                'player_id' => $row->player_id,
                'player_handle' => $row->player_handle,
                'role' => $newRole,
                'joined_at' => $newJoinedAt,
                'left_at' => $newLeftAt,
            ],
        ];
    }
}
