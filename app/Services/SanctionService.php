<?php

/**
 * GC-Stats — Sanction service
 *
 * Issues/revokes/deletes sanctions and maintains SanctionIdentity
 * fingerprints so a sanction sticks to every auth method the user has
 * ever used, even across account deletion. Also exposes the evasion check
 * used at registration / provider-linking time.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\SanctionRequiresSuperAdminException;
use App\Models\Sanction;
use App\Models\SanctionIdentity;
use App\Models\User;

class SanctionService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @param  array{type: string, reason: string, ends_at?: \DateTimeInterface|string|null, team_id?: ?int}  $data
     *
     * @throws SanctionRequiresSuperAdminException
     */
    public function issue(User $user, User $issuedBy, array $data): Sanction
    {
        if ($user->hasGlobalRole() && ! $issuedBy->isSuperAdmin()) {
            throw new SanctionRequiresSuperAdminException;
        }

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

        $this->notifications->notify(
            recipient: $user,
            type: NotificationService::TYPE_SANCTION_ISSUED,
            title: __('notifications.sanction_issued.title'),
            description: __('notifications.sanction_issued.description', ['type' => __('admin.sanctions.type.'.$sanction->type)]),
            link: route('account.edit'),
            author: $issuedBy,
            data: ['sanction_id' => $sanction->id, 'sanction_type' => $sanction->type],
        );

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
     * Permanently remove a sanction and its identity fingerprints
     * (cascade-deleted with it) — unlike revoke(), this erases the record
     * rather than just deactivating it, including the evasion-tracking
     * evidence in SanctionIdentity. Meant for corrections (a sanction
     * issued by mistake), not routine lifting — use revoke() for that.
     */
    public function delete(Sanction $sanction, User $deletedBy): void
    {
        activity('moderation')
            ->performedOn($sanction)
            ->causedBy($deletedBy)
            ->withProperties(['type' => $sanction->type, 'target_user_id' => $sanction->user_id])
            ->log('sanction.deleted');

        $sanction->delete();
    }

    /**
     * Record every current auth method of $user against $sanction.
     */
    public function snapshotIdentities(Sanction $sanction, User $user): void
    {
        if ($user->email !== null) {
            $sanction->identities()->firstOrCreate([
                'type' => SanctionIdentity::TYPE_EMAIL,
                'value' => self::canonicalizeIdentity(SanctionIdentity::TYPE_EMAIL, $user->email),
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
        $value = self::canonicalizeIdentity($type, $value);

        foreach ($user->sanctions as $sanction) {
            $sanction->identities()->firstOrCreate(['type' => $type, 'value' => $value]);
        }
    }

    /**
     * Does this identity (email or provider id) match an active suspension
     * or ban, regardless of which account it was originally issued against?
     * Lighter sanction types (warning/mute/note) don't block — they follow
     * the user to the new account instead, see transferNonBanSanctions().
     */
    public function hasActiveSanctionFor(string $type, string $value): bool
    {
        return SanctionIdentity::where('type', $type)
            ->where('value', self::canonicalizeIdentity($type, $value))
            ->whereHas('sanction', fn ($query) => $query->active()->whereIn('type', Sanction::BLOCKING_TYPES))
            ->exists();
    }

    /**
     * Re-registration/relinking with an identity carrying a non-blocking
     * sanction (warning/mute/note) isn't refused — the sanction simply
     * follows the user onto the new account instead, so it can't be shed by
     * making a fresh one. Blocking sanctions (suspension/ban) never reach
     * this: hasActiveSanctionFor() already refuses the request before the
     * new account/identity exists.
     */
    public function transferNonBanSanctions(User $user, string $type, string $value): void
    {
        $value = self::canonicalizeIdentity($type, $value);

        $sanctions = Sanction::whereIn('id', SanctionIdentity::where('type', $type)->where('value', $value)->pluck('sanction_id'))
            ->active()
            ->whereNotIn('type', Sanction::BLOCKING_TYPES)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
            ->get();

        foreach ($sanctions as $sanction) {
            $alreadyTransferred = Sanction::where('user_id', $user->id)
                ->where('transferred_from', $sanction->id)
                ->exists();

            if ($alreadyTransferred) {
                continue;
            }

            $transferred = Sanction::create([
                'user_id' => $user->id,
                'team_id' => $sanction->team_id,
                'issued_by' => $sanction->issued_by,
                'type' => $sanction->type,
                'reason' => $sanction->reason,
                'starts_at' => $sanction->starts_at,
                'ends_at' => $sanction->ends_at,
                'transferred_from' => $sanction->id,
            ]);

            $this->snapshotIdentities($transferred, $user);

            activity('moderation')
                ->performedOn($transferred)
                ->withProperties(['type' => $transferred->type, 'transferred_from' => $sanction->id, 'target_user_id' => $user->id])
                ->log('sanction.transferred');
        }
    }

    /**
     * Emails are lowercased and stripped of a `+tag` local-part suffix
     * before being stored/matched, so a banned user can't evade an
     * email-based sanction with `Victim@x.com` or `victim+1@x.com` — both
     * resolve to the same mailbox as `victim@x.com`. Provider ids are
     * opaque and left as-is.
     */
    private static function canonicalizeIdentity(string $type, string $value): string
    {
        if ($type !== SanctionIdentity::TYPE_EMAIL) {
            return $value;
        }

        $value = mb_strtolower(trim($value));

        [$local, $domain] = array_pad(explode('@', $value, 2), 2, '');
        $local = preg_replace('/\+.*$/', '', $local);

        return $domain !== '' ? "{$local}@{$domain}" : $value;
    }
}
