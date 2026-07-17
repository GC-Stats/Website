<?php

/**
 * GC-Stats — Email reputation service
 *
 * There is no generic "account age" for an arbitrary email address, unlike
 * OAuth providers (see ProviderAccountAge). The practical proxy is
 * disposable/temporary-address detection via Kickbox, used at registration
 * and email changes to reject throwaway addresses. Fails open: if Kickbox
 * is unreachable or misconfigured, the check is skipped rather than
 * blocking registration on a third-party outage.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailReputationService
{
    public const STATUS_OK = 'ok';

    public const STATUS_DISPOSABLE = 'disposable';

    public const STATUS_UNDELIVERABLE = 'undeliverable';

    public const STATUS_RISKY = 'risky';

    public const STATUS_UNKNOWN = 'unknown';

    /**
     * @return array{status: string, disposable: bool}
     */
    public function check(string $email): array
    {
        $key = config('services.kickbox.key');

        if (! $key) {
            return ['status' => self::STATUS_UNKNOWN, 'disposable' => false];
        }

        try {
            $response = Http::timeout(5)->get('https://api.kickbox.com/v2/verify', [
                'email' => $email,
                'apikey' => $key,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Kickbox email reputation check failed', ['message' => $e->getMessage()]);

            return ['status' => self::STATUS_UNKNOWN, 'disposable' => false];
        }

        if ($response->failed()) {
            Log::warning('Kickbox email reputation check failed', ['status' => $response->status()]);

            return ['status' => self::STATUS_UNKNOWN, 'disposable' => false];
        }

        $disposable = (bool) $response->json('disposable', false);

        $status = match (true) {
            $disposable => self::STATUS_DISPOSABLE,
            $response->json('result') === 'undeliverable' => self::STATUS_UNDELIVERABLE,
            $response->json('result') === 'risky' => self::STATUS_RISKY,
            $response->json('result') === 'deliverable' => self::STATUS_OK,
            default => self::STATUS_UNKNOWN,
        };

        return ['status' => $status, 'disposable' => $disposable];
    }

    /**
     * Should registration/email-change be hard-blocked for this result?
     * Disposable addresses are deliberately *not* blocking — they're
     * allowed through but flagged (see $flagsForModeration) so moderation
     * can review the account's activity, rather than pushing the user to
     * just enter a fake permanent-looking address instead.
     */
    public function isBlocking(string $status): bool
    {
        return $status === self::STATUS_UNDELIVERABLE;
    }

    /**
     * Should this result be surfaced to moderation as suspicious?
     */
    public function flagsForModeration(string $status): bool
    {
        return in_array($status, [self::STATUS_DISPOSABLE, self::STATUS_RISKY], true);
    }
}
