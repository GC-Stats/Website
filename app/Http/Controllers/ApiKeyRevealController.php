<?php

/**
 * GC-Stats — API key single-use reveal controller
 *
 * Displays a freshly generated API key exactly once, via an unguessable
 * single-use link handed out by the internal API to the Dashboard.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\ApiKeyReveal;
use Illuminate\View\View;

class ApiKeyRevealController extends Controller
{
    /**
     * GET: must stay side-effect free. Chat apps (Discord, Slack, Teams...)
     * fetch links server-side to build a preview embed as soon as the link
     * is posted, before a human ever opens it — so this step only confirms
     * the token is still valid, it never decrypts or consumes it.
     */
    public function show(string $token): View
    {
        $reveal = ApiKeyReveal::where('token', $token)->first();

        if (! $reveal || $reveal->isConsumed()) {
            return view('api-keys.reveal-unavailable');
        }

        return view('api-keys.reveal-confirm', [
            'token' => $token,
        ]);
    }

    /**
     * POST: only reachable via an explicit click on the confirm page, which
     * is where the key is actually decrypted and the reveal gets consumed.
     */
    public function reveal(string $token): View
    {
        $reveal = ApiKeyReveal::where('token', $token)->first();

        // consume() claims the reveal with an atomic conditional update, so
        // two concurrent requests for the same token (double-click, retry)
        // can't both succeed — the loser gets null here, same as an
        // already-consumed or missing token.
        $clearKey = $reveal?->consume();

        if (! $clearKey) {
            return view('api-keys.reveal-unavailable');
        }

        return view('api-keys.reveal', [
            'clientName' => $reveal->apiKey->client_name,
            'apiKey' => $clearKey,
        ]);
    }
}
