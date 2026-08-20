<?php

/**
 * GC-Stats — RiotRelay client
 *
 * Centralizes every HTTP call to the RiotRelay service (see
 * I:\JetBrains\GC-Stats\RiotRelay) — the caching proxy in front of Riot's
 * Valorant match-v1 API. RiotRelay relays Riot's own non-2xx responses
 * verbatim and only originates a handful of statuses itself (400 invalid
 * region/id, 401 bad relay token, 502 Riot unreachable, 503 cache DB down);
 * RiotRelayResult classifies both into one translated, user-facing reason.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RiotRelayClient
{
    private const CONNECT_TIMEOUT = 5;

    private const TIMEOUT = 12;

    public function getMatch(string $region, string $matchId): RiotRelayResult
    {
        return $this->call('get', "/match/{$region}/{$matchId}");
    }

    public function renewMatch(string $region, string $matchId): RiotRelayResult
    {
        return $this->call('post', "/match/{$region}/{$matchId}/renew");
    }

    /**
     * Stitches several raw match IDs into one continuous match — used when a
     * map's game was interrupted (disconnect/restart) and Riot recorded it
     * as multiple separate matches. Each segment picks the round range
     * (startRound/endRound, inclusive) to pull from its own match. The relay
     * returns a synthetic match ID (format `GCS-xxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`)
     * that behaves like a normal one for every other RiotRelay/relay-backed
     * call (getMatch/renewMatch) from then on.
     *
     * @param  list<array{matchId: string, startRound: int, endRound: int}>  $segments
     */
    public function mergeMatch(string $region, array $segments): RiotRelayResult
    {
        return $this->call('post', "/match/{$region}/merge", ['segments' => $segments]);
    }

    private function call(string $method, string $path, ?array $body = null): RiotRelayResult
    {
        $relayUrl = rtrim((string) config('services.riot.relay_url'), '/');

        try {
            $request = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT);

            $response = $body !== null
                ? $request->{$method}("{$relayUrl}{$path}", $body)
                : $request->{$method}("{$relayUrl}{$path}");
        } catch (ConnectionException) {
            return RiotRelayResult::unreachable();
        }

        return RiotRelayResult::fromResponse($response);
    }
}
