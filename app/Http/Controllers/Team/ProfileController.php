<?php

/**
 * GC-Stats — Team: profile editing
 *
 * Team-owner-facing entry point for App\Services\TeamProfileService — the
 * update logic itself is shared and will be reused from the admin panel
 * once site editors can edit any team from there too. Profile fields need
 * `team.profile.edit`, the logo needs `team.logo.upload` (checked
 * per-action; the edit page itself just needs either one to view).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Services\RosterService;
use App\Services\TeamProfileService;
use App\Support\Countries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request, Team $team, RosterService $rosterService): View
    {
        abort_unless(
            $request->user()->can('team.profile.edit')
                || $request->user()->can('team.logo.upload')
                || $request->user()->can('team.roles.manage')
                || $request->user()->can('team.roster.manage'),
            403
        );

        $history = $rosterService->history($team->id);
        $playerSearch = $request->get('player_q');

        return view('team.edit', [
            'team' => $team,
            'countries' => app(Countries::class)->list(),
            'roster' => $history->whereNull('left_at')->values(),
            'rosterHistory' => $history->whereNotNull('left_at')->values(),
            'playerSearch' => $playerSearch ?? '',
            'playerSearchResults' => $playerSearch
                ? Player::where('handle', 'like', '%'.$this->escapeLike($playerSearch).'%')
                    ->whereNotIn('id', $history->where('left_at', null)->pluck('player_id'))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vlr_id' => ['nullable', 'integer'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'socials' => ['nullable', 'array'],
            'socials.website' => ['nullable', 'url', 'max:255'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $service->updateProfile($team, $validated, $request->user());

        return back()->with('status', 'profile-updated');
    }

    public function updateLogo(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $service->updateLogo($team, $validated['logo'], $request->user());

        return back()->with('status', 'logo-updated');
    }

    public function storeLogoHistory(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
        ]);

        $service->addLogoHistoryEntry($team, $validated['logo'], $validated['from'], $validated['until'], $request->user());

        return back()->with('status', 'logo-history-added');
    }

    public function updateLogoEntry(Request $request, Team $team, string $slug, string $logo, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
        ]);

        $service->updateLogoEntry($team, $logo, $validated['from'], $validated['until'] ?? null, $request->user());

        return back()->with('status', 'logo-history-updated');
    }

    public function destroyLogoEntry(Request $request, Team $team, string $slug, string $logo, TeamProfileService $service): RedirectResponse
    {
        $service->deleteLogoEntry($team, $logo, $request->user());

        return back()->with('status', 'logo-history-removed');
    }
}
