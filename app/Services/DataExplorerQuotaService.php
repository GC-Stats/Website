<?php

/**
 * GC-Stats — Data Explorer quota service
 *
 * The AI query feature itself is open to every user (rate-limited at the
 * route level, see routes/data_explorer.php) — what's restricted is use of
 * the *platform's own* API key. The platform has a flat monthly budget for
 * that key (an LLM call costs money), split evenly and dynamically across
 * every currently authorized user (TOTAL_MONTHLY_QUOTA / count(
 * data_explorer_enabled users), recomputed live — never stored, so it
 * tracks the access list as it changes, and so every authorized user always
 * gets an equal share of the shared budget). Anyone else — unauthorized, or
 * authorized but over their share this month — needs their own linked
 * (BYOK) key to keep querying.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\DataExplorerQuotaExceededException;
use App\Models\DataExplorerUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataExplorerQuotaService
{
    public const SOURCE_PLATFORM = 'platform';

    public const SOURCE_PERSONAL = 'personal';

    public function individualMonthlyQuota(): int
    {
        $authorizedCount = User::where('data_explorer_enabled', true)->count();

        return intdiv((int) config('services.data_explorer.quota'), max(1, $authorizedCount));
    }

    /**
     * Atomically decides which key this request should use and records the
     * usage in the same locked transaction, so two concurrent requests near
     * the user's quota boundary can't both read "under quota" before either
     * write lands (the same check-then-act race ApiKeyReveal::consume()
     * closes for reveals).
     *
     * @return string self::SOURCE_PLATFORM or self::SOURCE_PERSONAL
     *
     * @throws DataExplorerQuotaExceededException
     */
    public function claimRequestSlot(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $usage = $this->lockCurrentMonthUsage($user);

            if ($user->data_explorer_enabled && $usage->platform_requests_count < $this->individualMonthlyQuota()) {
                $usage->increment('platform_requests_count');

                return self::SOURCE_PLATFORM;
            }

            $personalKey = $user->activeDataExplorerApiKey;

            if ($personalKey !== null && $personalKey->isValid()) {
                $usage->increment('personal_requests_count');

                return self::SOURCE_PERSONAL;
            }

            throw new DataExplorerQuotaExceededException;
        });
    }

    /**
     * Undoes the increment claimRequestSlot() made, for requests that never
     * actually reached the LLM (a connection failure to GC-Stats-API, or an
     * error shape that isn't one of the pipeline's own — see
     * DataExplorerService's before/after-the-call classification). A user
     * shouldn't be billed against their quota for something that was never
     * attempted. Floored at 0 in case of an already-reconciled/edge state.
     */
    public function releaseRequestSlot(User $user, string $source): void
    {
        DB::transaction(function () use ($user, $source) {
            $usage = $this->lockCurrentMonthUsage($user);
            $column = $source === self::SOURCE_PLATFORM ? 'platform_requests_count' : 'personal_requests_count';

            if ($usage->{$column} > 0) {
                $usage->decrement($column);
            }
        });
    }

    private function lockCurrentMonthUsage(User $user): DataExplorerUsage
    {
        $now = now();

        $usage = DataExplorerUsage::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->lockForUpdate()
            ->first();

        if ($usage !== null) {
            return $usage;
        }

        DataExplorerUsage::create([
            'user_id' => $user->id,
            'year' => $now->year,
            'month' => $now->month,
        ]);

        return DataExplorerUsage::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @return array{authorized: bool, quota: int, used: int, source: string|null, has_personal_key: bool}
     */
    public function usageSummary(User $user): array
    {
        $now = now();
        $authorized = (bool) $user->data_explorer_enabled;
        $quota = $authorized ? $this->individualMonthlyQuota() : 0;

        $usage = DataExplorerUsage::where('user_id', $user->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        $used = $usage?->platform_requests_count ?? 0;
        $hasPersonalKey = $user->activeDataExplorerApiKey?->isValid() ?? false;

        $source = match (true) {
            $authorized && $used < $quota => self::SOURCE_PLATFORM,
            $hasPersonalKey => self::SOURCE_PERSONAL,
            default => null,
        };

        return [
            'authorized' => $authorized,
            'quota' => $quota,
            'used' => $used,
            'source' => $source,
            'has_personal_key' => $hasPersonalKey,
        ];
    }

    /**
     * Global admin dashboard view: total platform requests used this month
     * out of the shared monthly budget, plus a per-user breakdown.
     *
     * @return array{total_used: int, total_quota: int, per_user: Collection}
     */
    public function globalUsageSummary(): array
    {
        $now = now();

        $usages = DataExplorerUsage::with('user:id,name,username')
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->get();

        return [
            'total_used' => $usages->sum('platform_requests_count'),
            'total_quota' => (int) config('services.data_explorer.quota'),
            'per_user' => $usages->sortByDesc(fn (DataExplorerUsage $usage) => $usage->totalRequestsCount())->values(),
        ];
    }
}
