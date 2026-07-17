<?php

/**
 * GC-Stats — Sanction service
 *
 * Issues/revokes sanctions and maintains SanctionIdentity fingerprints so a
 * sanction "sticks" to every login method (email, Discord/Twitch/Twitter
 * account) the sanctioned user has ever used — including methods linked
 * after the sanction was issued, and even once the account itself is
 * deleted. Also exposes the evasion check used at registration / provider
 * linking time.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Sanction;
use App\Models\SanctionIdentity;
use App\Models\User;

class SanctionService
{
    /**
     * @param  array{type: string, reason: string, ends_at?: \DateTimeInterface|string|null, team_id?: ?int}  $data
     */
    public function issue(User $user, User $issuedBy, array $data): Sanction
    {
        $sanction = Sanction::create([
            'user_id' => $user->id,
            'team_id' => $data['team_id'] ?? null,
            'issued_by' => $issuedBy->id,
            'type' => $data['type'],
            'reason' => $data['reason'],
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $this->snapshotIdentities($sanction, $user);

        activity('moderation')
            ->performedOn($sanction)
            ->causedBy($issuedBy)
            ->withProperties(['type' => $sanction->type, 'team_id' => $sanction->team_id, 'target_user_id' => $user->id])
            ->log('sanction.issued');

        return $sanction;
    }

    public function revoke(Sanction $sanction, User $revokedBy): void
    {
        $sanction->update([
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
        ]);

        activity('moderation')
            ->performedOn($sanction)
            ->causedBy($revokedBy)
            ->withProperties(['target_user_id' => $sanction->user_id])
            ->log('sanction.revoked');
    }

    /**
     * Record every current auth method of $user against $sanction.
     */
    public function snapshotIdentities(Sanction $sanction, User $user): void
    {
        if ($user->email !== null) {
            $sanction->identities()->firstOrCreate([
                'type' => SanctionIdentity::TYPE_EMAIL,
                'value' => $user->email,
            ]);
        }

        foreach ($user->socialAccounts as $socialAccount) {
            $sanction->identities()->firstOrCreate([
                'type' => $socialAccount->provider,
                'value' => $socialAccount->provider_id,
            ]);
        }
    }

    /**
     * A new auth method was just attached to $user (email set, or a
     * provider linked) — attach it to every sanction already on record for
     * this user so future evasion via that identity is still caught.
     */
    public function propagateIdentity(User $user, string $type, string $value): void
    {
        foreach ($user->sanctions as $sanction) {
            $sanction->identities()->firstOrCreate(['type' => $type, 'value' => $value]);
        }
    }

    /**
     * Does this identity (email or provider id) match an active sanction,
     * regardless of which account it was originally issued against?
     */
    public function hasActiveSanctionFor(string $type, string $value): bool
    {
        return SanctionIdentity::where('type', $type)
            ->where('value', $value)
            ->whereHas('sanction', fn ($query) => $query->active())
            ->exists();
    }
}
