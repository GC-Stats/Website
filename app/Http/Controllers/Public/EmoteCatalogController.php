<?php

/**
 * GC-Stats — Emote catalog endpoint
 *
 * Serves the full active-emote catalog (name+url and id+name+url shapes) as
 * a single static JSON payload, for the forum composer's `:name:` shortcode
 * lookup (see resources/views/livewire/forum-thread.blade.php) and its
 * emote-picker click handler.
 *
 * Deliberately not a Livewire property: with ~4,000 emotes imported from
 * Twemoji (see App\Console\Commands\ImportCommands\ImportTwemojiEmotes),
 * that catalog serializes to several hundred KB — embedding it in the
 * forum-thread component's state meant Livewire re-sent the whole thing in
 * every single response for that component (mount, plus every action —
 * opening any picker, posting, paginating), regardless of the thread having
 * any messages at all. Serving it here instead means the browser fetches it
 * once and caches it, via this response's own Cache-Control header —
 * App\Http\Middleware\StaticPageCache isn't reusable here, it's typed to
 * return Illuminate\Http\Response, which JsonResponse doesn't extend.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\Emote;
use Illuminate\Http\JsonResponse;

class EmoteCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Emote::catalog())
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600');
    }
}
