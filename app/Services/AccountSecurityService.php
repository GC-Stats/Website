<?php

/**
 * GC-Stats — Account security service
 *
 * Enforces the "at least one auth method" rule: a user account must always
 * be reachable via a password or at least one linked social provider. All
 * mutations that could remove an auth method (unlinking a provider, wiping
 * the password) go through here rather than touching the model directly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\LastAuthMethodException;
use App\Exceptions\SocialAccountAlreadyLinkedException;
use App\Models\SocialAccount;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;

class AccountSecurityService
{
    /**
     * Provider accounts younger than this are flagged for moderation when
     * first linked — a brand new Discord/Twitch/X account is a common
     * ban-evasion / throwaway-account signal.
     */
    private const YOUNG_ACCOUNT_THRESHOLD_DAYS = 30;

    /**
     * Attach a Socialite provider identity to $user, or refresh its tokens
     * if already linked to this same user.
     *
     * @param  array{id: string, nickname: ?string, avatar: ?string, token: string, refreshToken: ?string, expiresIn: ?int, createdAt: ?CarbonInterface}  $providerUser
     *
     * @throws SocialAccountAlreadyLinkedException
     */
    public function linkProvider(User $user, string $provider, array $providerUser): SocialAccount
    {
        $existing = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerUser['id'])
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            throw new SocialAccountAlreadyLinkedException;
        }

        $isNewLink = $existing === null;

        $socialAccount = SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $providerUser['id']],
            [
                'user_id' => $user->id,
                'nickname' => $providerUser['nickname'] ?? null,
                'avatar' => $providerUser['avatar'] ?? null,
                'token' => $providerUser['token'],
                'refresh_token' => $providerUser['refreshToken'] ?? null,
                'token_expires_at' => isset($providerUser['expiresIn'])
                    ? now()->addSeconds($providerUser['expiresIn'])
                    : null,
                'provider_created_at' => $providerUser['createdAt'] ?? null,
            ],
        );

        if ($isNewLink) {
            activity('account')
                ->performedOn($socialAccount)
                ->causedBy($user)
                ->withProperties(['provider' => $provider, 'provider_created_at' => $providerUser['createdAt']?->toIso8601String()])
                ->log('account.provider_linked');

            $createdAt = $providerUser['createdAt'] ?? null;

            if ($createdAt !== null && $createdAt->diffInDays(now()) < self::YOUNG_ACCOUNT_THRESHOLD_DAYS) {
                activity('moderation')
                    ->performedOn($socialAccount)
                    ->causedBy($user)
                    ->withProperties(['reason' => 'young_provider_account', 'provider' => $provider, 'provider_created_at' => $createdAt->toIso8601String()])
                    ->log('account.flagged');
            }
        }

        return $socialAccount;
    }

    /**
     * @throws LastAuthMethodException
     */
    public function unlinkProvider(User $user, SocialAccount $socialAccount): void
    {
        if ($user->authMethodsCount() <= 1) {
            throw new LastAuthMethodException;
        }

        activity('account')
            ->causedBy($user)
            ->withProperties(['provider' => $socialAccount->provider])
            ->log('account.provider_unlinked');

        $socialAccount->delete();
    }

    public function setPassword(User $user, string $password): void
    {
        $isNew = $user->password === null;

        $user->forceFill(['password' => Hash::make($password)])->save();

        activity('account')->performedOn($user)->causedBy($user)
            ->log($isNew ? 'account.password_set' : 'account.password_changed');
    }

    /**
     * @throws LastAuthMethodException
     */
    public function removePassword(User $user): void
    {
        if ($user->authMethodsCount() <= 1) {
            throw new LastAuthMethodException;
        }

        // Two-factor authentication and passkeys are only offered on
        // password-protected accounts (they exist to harden a password
        // login) — dropping the password strips their reason to exist, so
        // they're wiped along with it rather than left in a state the
        // account-settings UI no longer surfaces.
        $user->passkeys()->delete();

        $user->forceFill([
            'password' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        activity('account')->performedOn($user)->causedBy($user)->log('account.password_removed');
    }
}
