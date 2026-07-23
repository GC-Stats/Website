<?php

/**
 * GC-Stats — Bulk-import Twemoji as Emote rows
 *
 * Fetches the full SVG file list from the Twemoji repository (via GitHub's
 * git-trees API, one call) and downloads each SVG individually, storing it
 * locally under storage/app/public/emotes/twemoji — never hotlinked at
 * runtime. Idempotent: re-running skips any codepoint already downloaded
 * (checked by file path, not by name — see below).
 *
 * Twemoji filenames are raw Unicode codepoint sequences (e.g.
 * "1f469-200d-1f469-200d-1f467-200d-1f467"), which make poor Emote names:
 * too long, not human-readable. Instead this resolves a short, readable
 * name for each codepoint from Emojibase's "github" shortcode preset
 * (https://github.com/milesj/emojibase) — the same gemoji-derived short
 * names GitHub/Slack use and that Discord's own emoji picker/autocomplete
 * is built on (e.g. "1F600" → "grinning", "1F44D" → "thumbsup"). Codepoints
 * with no known shortcode (skin-tone variants, rare ZWJ combos) fall back
 * to the raw codepoint string.
 *
 * Usage: php artisan import:twemoji [--ref=main] [--limit=50]
 *
 * Twemoji is licensed CC-BY 4.0 — see https://github.com/jdecked/twemoji
 * and the credit in README.md.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ImportCommands;

use App\Models\Emote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportTwemojiEmotes extends Command
{
    protected $signature = 'import:twemoji {--ref=main : Git ref (tag/branch) of the twemoji repo to import from} {--limit= : Only import the first N emoji (for testing)}';

    protected $description = 'Bulk-import the Twemoji SVG set as Emote rows, named from Discord-style shortcodes when known (credits: github.com/jdecked/twemoji, CC-BY 4.0)';

    private const REPO = 'jdecked/twemoji';

    private const SHORTCODES_URL = 'https://raw.githubusercontent.com/milesj/emojibase/master/packages/data/en/shortcodes/github.raw.json';

    public function handle(): int
    {
        $ref = $this->option('ref');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info('Fetching shortcode names…');
        $shortcodes = $this->fetchShortcodes();

        $this->info("Fetching Twemoji SVG file list ({$ref})…");

        $tree = Http::timeout(30)->get('https://api.github.com/repos/'.self::REPO."/git/trees/{$ref}", ['recursive' => 1]);

        if ($tree->failed()) {
            $this->error("GitHub API error: {$tree->status()} — {$tree->body()}");

            return self::FAILURE;
        }

        $files = collect($tree->json('tree'))
            ->filter(fn ($entry) => str_starts_with($entry['path'], 'assets/svg/') && str_ends_with($entry['path'], '.svg'))
            ->values();

        if ($limit !== null) {
            $files = $files->take($limit);
        }

        $this->info("Importing {$files->count()} emoji…");
        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $entry) {
            $codepoint = Str::of($entry['path'])->afterLast('/')->beforeLast('.svg')->toString();
            $path = "emotes/twemoji/{$codepoint}.svg";

            if (Storage::disk('public')->exists($path)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $raw = Http::timeout(30)->get('https://raw.githubusercontent.com/'.self::REPO."/{$ref}/{$entry['path']}");

            if ($raw->failed()) {
                $failed++;
                $this->newLine();
                $this->warn("Failed to download {$entry['path']}: HTTP {$raw->status()}");
                $bar->advance();

                continue;
            }

            Storage::disk('public')->put($path, $raw->body());

            Emote::create([
                'name' => $this->uniqueName($this->resolveName($codepoint, $shortcodes)),
                'image_path' => $path,
                'source' => 'twemoji',
                'is_active' => true,
            ]);

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$imported} imported, {$skipped} already present, {$failed} failed.");

        if ($imported > 0) {
            Emote::forgetActiveCache();
            Emote::forgetSourcesCache();
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> uppercase codepoint => shortcode
     */
    private function fetchShortcodes(): array
    {
        $response = Http::timeout(30)->get(self::SHORTCODES_URL);

        if ($response->failed()) {
            $this->warn("Could not fetch shortcode names ({$response->status()}) — falling back to raw codepoints for every emoji.");

            return [];
        }

        return collect($response->json())
            ->map(function ($value) {
                // Some entries list multiple aliases (e.g. ["+1", "thumbsup"]) —
                // prefer the first alpha_dash-safe one (skip symbols like "+1").
                if (! is_array($value)) {
                    return $value;
                }

                return collect($value)->first(fn ($alias) => preg_match('/^[a-zA-Z0-9_-]+$/', $alias)) ?? $value[0];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $shortcodes
     */
    private function resolveName(string $codepoint, array $shortcodes): string
    {
        $key = strtoupper($codepoint);

        // Twemoji includes the FE0F emoji-presentation selector in many
        // filenames (e.g. "2764-fe0f") that Emojibase's keys omit — retry
        // without it before falling back to the raw codepoint.
        $withoutSelector = implode('-', array_filter(explode('-', $key), fn ($part) => $part !== 'FE0F'));

        $name = $shortcodes[$key] ?? $shortcodes[$withoutSelector] ?? $codepoint;

        return Str::substr($name, 0, 80);
    }

    private function uniqueName(string $name): string
    {
        if (! Emote::where('name', $name)->exists()) {
            return $name;
        }

        for ($suffix = 2; ; $suffix++) {
            $candidate = Str::substr($name, 0, 77)."-{$suffix}";

            if (! Emote::where('name', $candidate)->exists()) {
                return $candidate;
            }
        }
    }
}
