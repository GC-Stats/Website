<?php

/**
 * GC-Stats — Profile settings controller
 *
 * The signed-in user's public-facing profile: display name/username/
 * pronouns/email (via Fortify's own update-profile-information route),
 * avatar, team fan pick and bio/social links. Split out from
 * AccountSettingsController, which keeps password/2FA/passkeys/danger zone.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Public\Controller;
use App\Models\Team;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.profile-edit', [
            'user' => $request->user(),
        ]);
    }

    public function updateFanTeam(Request $request): RedirectResponse
    {
        // The picker's hidden inputs are always present, empty string when
        // unset — normalize to null so `nullable` actually short-circuits
        // `exists` instead of validating an empty string against it.
        $request->merge([
            'team_id' => $request->filled('team_id') ? $request->input('team_id') : null,
            'team_tag' => $request->filled('team_tag') ? $request->input('team_tag') : null,
        ]);

        $validated = $request->validate([
            'team_id' => ['nullable', 'exists:teams,id'],
            'team_tag' => ['nullable', 'string', 'max:50'],
        ]);

        $team = ! empty($validated['team_id']) ? Team::find($validated['team_id']) : null;

        if ($team && ! empty($validated['team_tag']) && ! in_array($validated['team_tag'], $team->fanTags(), true)) {
            throw ValidationException::withMessages([
                'team_tag' => __('account.errors.invalid_team_tag'),
            ]);
        }

        $request->user()->update([
            'team_id' => $team?->id,
            'team_tag' => $team ? ($validated['team_tag'] ?? null) : null,
        ]);

        return back()->with('status', 'team-tag-updated');
    }

    public function updateBio(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isEligibleForBio(), 403, __('account.errors.not_eligible_for_bio'));

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                // These are handles/usernames, not links — the display side
                // (SocialLinkConfig/x-social-links) prepends the platform's
                // fixed URL prefix, so a pasted full link would double up.
                if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $value) || str_contains($value, '://')) {
                    $fail(__('account.errors.social_handle_only'));
                }
            }],
        ]);

        $user->update($validated);

        if ($user->wasChanged(['bio', 'socials'])) {
            activity('profile')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(ActivityChangeSet::fromModel($user, ['bio', 'socials'])->toArray())
                ->log('user.bio_updated');
        }

        return back()->with('status', 'bio-updated');
    }
}
