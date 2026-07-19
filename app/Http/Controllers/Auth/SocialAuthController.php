<?php

/**
 * GC-Stats — Social authentication controller
 *
 * Socialite redirect/callback for Twitter/X, Twitch and Discord — the same
 * callback logs an existing user in or registers a new one.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAccountAlreadyLinkedException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AccountSecurityService;
use App\Services\DiscordRoleSyncService;
use App\Services\SanctionService;
use App\Support\Socialite\ProviderAccountAge;
use App\Support\Socialite\VerifiedEmail;
use App\Support\UsernameGenerator;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['twitter', 'twitch', 'discord'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(
        string $provider,
        AccountSecurityService $accountSecurity,
        SanctionService $sanctions,
        DiscordRoleSyncService $discordRoleSync,
    ): RedirectResponse {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $socialiteUser = Socialite::driver($provider)->user();
        $providerId = (string) $socialiteUser->getId();

        $existing = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        $isSanctioned = $sanctions->hasActiveSanctionFor($provider, $providerId);

        // Linking a new provider to the account that's currently logged in.
        if (Auth::check()) {
            if ($existing && $existing->user_id !== Auth::id()) {
                return back()->withErrors(['social' => (new SocialAccountAlreadyLinkedException)->getMessage()]);
            }

            if ($isSanctioned) {
                return back()->withErrors(['social' => __('account.errors.social_blocked')]);
            }

            /** @var User $user */
            $user = Auth::user();

            try {
                $accountSecurity->linkProvider($user, $provider, $this->providerPayload($provider, $socialiteUser));
            } catch (SocialAccountAlreadyLinkedException $e) {
                return back()->withErrors(['social' => $e->getMessage()]);
            }

            $sanctions->propagateIdentity($user, $provider, $providerId);

            if ($provider === 'discord') {
                $discordRoleSync->sync($user);
            }

            return redirect()->intended(route('home'));
        }

        if ($existing) {
            if ($isSanctioned) {
                abort(403, __('account.errors.social_blocked'));
            }

            Auth::login($existing->user, remember: true);

            if ($provider === 'discord') {
                $discordRoleSync->sync($existing->user);
            }

            return redirect()->intended(route('home'));
        }

        if ($isSanctioned) {
            abort(403, __('account.errors.social_blocked'));
        }

        $email = VerifiedEmail::resolve($provider, $socialiteUser->getRaw(), $socialiteUser->getEmail());

        if ($email !== null && User::where('email', $email)->exists()) {
            return redirect()->route('login')->withErrors([
                'email' => __('account.errors.email_already_registered'),
            ]);
        }

        $name = $socialiteUser->getNickname() ?? $socialiteUser->getName() ?? 'Player';

        $user = User::create([
            'name' => $name,
            'username' => UsernameGenerator::generate($name),
            'email' => $email,
            'password' => null,
        ]);

        try {
            $accountSecurity->linkProvider($user, $provider, $this->providerPayload($provider, $socialiteUser));
        } catch (SocialAccountAlreadyLinkedException $e) {
            return redirect()->route('login')->withErrors(['social' => $e->getMessage()]);
        }

        if ($provider === 'discord') {
            $discordRoleSync->sync($user);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    /**
     * @return array{id: string, nickname: ?string, avatar: ?string, token: string, refreshToken: ?string, expiresIn: ?int, createdAt: ?CarbonInterface}
     */
    private function providerPayload(string $provider, SocialiteUser $socialiteUser): array
    {
        $id = (string) $socialiteUser->getId();

        return [
            'id' => $id,
            'nickname' => $socialiteUser->getNickname(),
            'avatar' => $socialiteUser->getAvatar(),
            'token' => $socialiteUser->token,
            'refreshToken' => $socialiteUser->refreshToken ?? null,
            'expiresIn' => $socialiteUser->expiresIn ?? null,
            'createdAt' => ProviderAccountAge::resolve($provider, $id, $socialiteUser->getRaw()),
        ];
    }
}
