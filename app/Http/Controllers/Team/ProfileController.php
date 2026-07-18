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
use App\Models\Team;
use App\Services\TeamProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request, Team $team): View
    {
        // Matches User::canManageTeam(), which decides whether the "Edit
        // team" link is shown at all — a team.roles.manage-only user must
        // land on a real page here (they'll just see neither form section,
        // per the @can checks in team.edit, but the roles-page link at the
        // top of it) rather than 403 on the only link they're given.
        abort_unless(
            $request->user()->can('team.profile.edit')
                || $request->user()->can('team.logo.upload')
                || $request->user()->can('team.roles.manage'),
            403
        );

        return view('team.edit', ['team' => $team]);
    }

    public function update(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'bio' => ['nullable', 'string', 'max:2000'],
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
}
