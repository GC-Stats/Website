<?php

/**
 * GC-Stats — Account settings controller
 *
 * Password set/update/removal, instant account deletion and instant GDPR
 * data export — no email round-trip, everything resolves synchronously in
 * the request/response cycle.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Exceptions\LastAuthMethodException;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\AccountSecurityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['socialAccounts', 'passkeys']);

        return view('auth.account-edit', [
            'user' => $user,
            'linkedProviders' => $user->socialAccounts->pluck('provider')->all(),
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

    public function setPassword(Request $request, AccountSecurityService $accountSecurity): RedirectResponse
    {
        $user = $request->user();

        if ($user->email === null) {
            throw ValidationException::withMessages([
                'password' => __('account.errors.password_requires_email'),
            ]);
        }

        if ($user->password !== null) {
            $request->validate(['current_password' => ['required', 'current_password']]);
        }

        $validated = $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);

        $accountSecurity->setPassword($user, $validated['password']);

        return back()->with('status', 'password-updated');
    }

    public function destroyPassword(Request $request, AccountSecurityService $accountSecurity): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        try {
            $accountSecurity->removePassword($request->user());
        } catch (LastAuthMethodException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        return back()->with('status', 'password-removed');
    }

    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user()->load(['socialAccounts', 'player', 'sanctions' => fn ($q) => $q->with('team:id,name')]);

        $roles = Role::whereHas('users', fn ($q) => $q->where('model_has_roles.model_id', $user->id))
            ->get(['name', 'team_id']);

        $payload = [
            'profile' => $user->only(['id', 'name', 'email', 'preferences', 'created_at']),
            'social_accounts' => $user->socialAccounts->map->only(['provider', 'nickname', 'created_at']),
            'player_profile' => $user->player?->only(['id', 'handle']),
            'teams' => $roles->map->only(['name', 'team_id']),
            'sanctions_received' => $user->sanctions->map->only(['type', 'reason', 'team', 'starts_at', 'ends_at', 'revoked_at']),
        ];

        $filename = 'gc-stats-data-'.$user->id.'-'.now()->format('Y-m-d').'.json';

        activity('account')->performedOn($user)->causedBy($user)->log('account.data_exported');

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->password !== null) {
            $request->validate(['current_password' => ['required', 'current_password']]);
        }

        activity('account')->performedOn($user)->causedBy($user)->log('account.deleted');

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
