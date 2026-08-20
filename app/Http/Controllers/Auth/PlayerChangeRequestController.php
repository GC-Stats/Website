<?php

/**
 * GC-Stats — Player change request controller
 *
 * Lets any authenticated user propose a correction on a player's public
 * page — profile fields, socials, photo, current team, past team-history
 * entries, and optionally claiming the profile as their own — via a
 * dedicated page (not a page-embedded modal, so every field gets room,
 * including the team picker). Only fields that actually differ from the
 * player's current value become a ChangeRequestItem. Every proposal goes
 * through the moderated ChangeRequest queue (see
 * App\Http\Controllers\Admin\ChangeRequestController) — a proposed field is
 * never written to the player directly. A "link to me" item only takes
 * effect once a staff member accepts it (see
 * App\Services\ChangeRequests\Appliers\PlayerLinkUserApplier, only invoked
 * from the admin accept action, which is what actually sets players.user_id),
 * and an uploaded photo is stored to disk immediately (see
 * LogoUploadService::storeLogoPair()) but only linked to the player once
 * accepted (see App\Services\ChangeRequests\Appliers\PlayerPhotoApplier).
 * Mirrors App\Http\Controllers\Auth\UserReportController.
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
use App\Support\Pronouns;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerChangeRequestController extends Controller
{
    /**
     * Fields a public change request may propose — the subset of
     * ChangeRequestApplierRegistry's Player fields that are simple,
     * self-service-appropriate profile attributes. `roster` is handled
     * separately below since it needs a team/role/date, not a text diff.
     */
    private const EDITABLE_FIELDS = ['first_name', 'last_name', 'pronouns', 'country_code', 'bio'];

    /** Social platforms the header/admin profile form both understand — see resources/views/public/player/header.blade.php's $socialConfig. */
    private const SOCIAL_PLATFORMS = ['twitter', 'twitch', 'tiktok', 'instagram', 'youtube', 'discord'];

    public function create(Player $player, RosterService $roster): View
    {
        $currentTeam = $player->teams()->wherePivotNull('left_at')->first();

        return view('public.player.change-request', [
            'player' => $player,
            'currentTeam' => $currentTeam,
            'roles' => __('team.roster.roles'),
            'countries' => app(Countries::class)->list(),
            'socialPlatforms' => self::SOCIAL_PLATFORMS,
            'teamHistory' => $roster->teamHistory($player->id),
        ]);
    }

    public function store(Request $request, Player $player, ChangeRequestService $changeRequests, RosterService $roster, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'pronouns' => ['nullable', 'integer', Rule::in(Pronouns::OPTIONS)],
            'country_code' => ['nullable', 'string', 'max:3'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'joined_at' => ['nullable', 'date'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'history.*.joined_at' => ['nullable', 'date'],
            'history.*.left_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'photo.max' => __('player.change_request.errors.photo_too_large'),
        ]);

        $items = [];

        $newSocials = collect(self::SOCIAL_PLATFORMS)
            ->mapWithKeys(fn ($platform) => [$platform => trim((string) ($validated['socials'][$platform] ?? ''))])
            ->filter(fn ($value) => $value !== '')
            ->all();

        $currentSocials = collect($player->socials ?? [])
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

        if (! empty($validated['history'])) {
            $existingRows = $roster->teamHistory($player->id)->keyBy('id');

            foreach ($validated['history'] as $rowId => $entry) {
                $row = $existingRows->get((int) $rowId);

                if (! $row) {
                    continue;
                }

                $newRole = $entry['role'] ?? $row->role;
                $newJoinedAt = $entry['joined_at'] ?? $row->joined_at;
                $newLeftAt = filled($entry['left_at'] ?? null) ? $entry['left_at'] : null;

                if ($newRole === $row->role && $newJoinedAt === $row->joined_at && $newLeftAt === $row->left_at) {
                    continue;
                }

                $items[] = [
                    'field' => 'roster_history',
                    'old_value' => [
                        'row_id' => $row->id,
                        'team_id' => $row->team_id,
                        'team_name' => $row->team_name,
                        'role' => $row->role,
                        'joined_at' => $row->joined_at,
                        'left_at' => $row->left_at,
                    ],
                    'new_value' => [
                        'row_id' => $row->id,
                        'team_id' => $row->team_id,
                        'team_name' => $row->team_name,
                        'role' => $newRole,
                        'joined_at' => $newJoinedAt,
                        'left_at' => $newLeftAt,
                    ],
                ];
            }
        }

        foreach (self::EDITABLE_FIELDS as $field) {
            $newValue = match ($field) {
                'country_code' => blank($validated[$field] ?? null) ? null : strtolower($validated[$field]),
                'pronouns' => isset($validated[$field]) ? (int) $validated[$field] : null,
                default => $validated[$field] ?? null,
            };

            if ($newValue !== $player->{$field}) {
                $items[] = [
                    'field' => $field,
                    'old_value' => $player->{$field},
                    'new_value' => $newValue,
                ];
            }
        }

        if (! empty($validated['team_id'])) {
            $currentTeam = $player->teams()->wherePivotNull('left_at')->first();

            if (! $currentTeam || $currentTeam->id !== (int) $validated['team_id']) {
                if (empty($validated['joined_at'])) {
                    return back()->withErrors(['joined_at' => __('player.change_request.errors.joined_at_required')])->withInput();
                }

                $team = Team::findOrFail($validated['team_id']);

                $items[] = [
                    'field' => 'roster',
                    'old_value' => $currentTeam ? ['team_id' => $currentTeam->id, 'team_name' => $currentTeam->name] : null,
                    'new_value' => [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'role' => $validated['role'] ?? 'player',
                        'joined_at' => $validated['joined_at'],
                    ],
                ];
            }
        }

        if ($request->boolean('link_to_me')) {
            $user = $request->user();

            if ($player->user_id === $user->id) {
                return back()->withErrors(['change_request' => __('player.change_request.errors.already_linked_to_you')])->withInput();
            }

            if ($player->user_id !== null) {
                return back()->withErrors(['change_request' => __('player.change_request.errors.player_already_linked')])->withInput();
            }

            if ($user->player()->exists()) {
                return back()->withErrors(['change_request' => __('player.change_request.errors.user_already_linked')])->withInput();
            }

            $items[] = [
                'field' => 'link_user',
                'old_value' => null,
                'new_value' => ['user_id' => $user->id],
            ];
        }

        // Deferred until every validation-driven early return above has
        // passed, so a rejected submission never leaves an orphaned upload
        // on disk (see LogoUploadService::storeLogoPair()).
        if ($request->hasFile('photo')) {
            $logoId = $logoUploadService->storeLogoPair($request->file('photo'), 'players');

            $items[] = [
                'field' => 'photo',
                'old_value' => ['current_photo' => $player->profile_photo],
                'new_value' => ['logo_id' => $logoId],
            ];
        }

        if (empty($items)) {
            return back()->withErrors(['change_request' => __('player.change_request.errors.no_changes')])->withInput();
        }

        $changeRequests->create($player, $request->user(), $validated['note'] ?? null, $items);

        return back()->with('status', 'change-request-submitted');
    }
}
