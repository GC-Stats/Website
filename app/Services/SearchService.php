<?php

/**
 * GC-Stats — Global search service
 *
 * Shared typo-tolerant search/scoring logic for players, teams and
 * tournaments, used by both the header search dropdown and the dedicated
 * search results page.
 *
 * Ranking: each candidate gets a score, summed from:
 *   - +1000 if the name/handle starts with the typed term exactly (e.g. "Lac" → "Lacy").
 *   - +75   if it contains the typed term exactly anywhere, without needing a
 *           typo-variant substitution (c/k, i/y, ph/f, z/s, double letters).
 *   - +0–100 the closer the name's length is to the typed term's length.
 *   - +0–200 based on the page's view count over the last 30 days.
 * Results are sorted by this score descending, so exact prefix matches
 * outrank substring matches, which outrank typo-corrected matches.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class SearchService
{
    private function stripAccents(string $s): string
    {
        return strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ý' => 'y', 'ÿ' => 'y', 'ç' => 'c', 'ñ' => 'n', 'ß' => 'ss',
        ]);
    }

    public function typoVariants(string $term): array
    {
        $term = $this->stripAccents($term);

        $variants = [$term];

        // i ↔ y  (Krispy / Kryspy)
        if (str_contains($term, 'i')) {
            $variants[] = str_replace('i', 'y', $term);
        }
        if (str_contains($term, 'y')) {
            $variants[] = str_replace('y', 'i', $term);
        }

        // c ↔ k  (Cold / Kold)
        if (str_contains($term, 'c')) {
            $variants[] = str_replace('c', 'k', $term);
        }
        if (str_contains($term, 'k')) {
            $variants[] = str_replace('k', 'c', $term);
        }

        // ph ↔ f  (Phaze / Faze)
        if (str_contains($term, 'ph')) {
            $variants[] = str_replace('ph', 'f', $term);
        }
        if (str_contains($term, 'f')) {
            $variants[] = str_replace('f', 'ph', $term);
        }

        // z ↔ s  (Zeta / Seta)
        if (str_contains($term, 'z')) {
            $variants[] = str_replace('z', 's', $term);
        }
        if (str_contains($term, 's')) {
            $variants[] = str_replace('s', 'z', $term);
        }

        // Double → single  (Atttlas → Atlas)
        foreach (['tt', 'll', 'ss', 'rr', 'nn', 'pp'] as $double) {
            if (str_contains($term, $double)) {
                $variants[] = str_replace($double, $double[0], $term);
            }
        }

        return array_unique($variants);
    }

    /**
     * @return array{tournaments: array, teams: array, players: array}
     */
    public function search(string $term, int $perTypeLimit = 5, int $candidateLimit = 15): array
    {
        $term = strtolower(trim($term));
        $variants = $this->typoVariants($term);
        $term = $variants[0]; // accent-stripped version, used for scoring
        $termLen = mb_strlen($term);

        // Order candidates so exact prefix/substring matches on the typed term are
        // never pushed past the candidate limit by an arbitrary DB row order —
        // otherwise the most relevant rows could be excluded before scoring runs.
        $prefixFirst = fn ($v) => "CASE WHEN LOWER({$v}) LIKE ? THEN 0 WHEN LOWER({$v}) LIKE ? THEN 1 ELSE 2 END";

        $tournamentCandidates = Tournament::where('active', true)
            ->where(function ($q) use ($variants) {
                foreach ($variants as $v) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$v}%"]);
                }
            })
            ->orderByRaw($prefixFirst('name'), ["{$term}%", "%{$term}%"])
            ->limit($candidateLimit)
            ->get();

        $teamCandidates = Team::where(function ($q) use ($variants) {
            foreach ($variants as $v) {
                $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$v}%"])
                    ->orWhereRaw('LOWER(short_name) LIKE ?', ["%{$v}%"]);
            }
        })
            ->orderByRaw($prefixFirst('name'), ["{$term}%", "%{$term}%"])
            ->limit($candidateLimit)
            ->get();

        $playerCandidates = Player::where(function ($q) use ($variants) {
            foreach ($variants as $v) {
                $q->orWhereRaw('LOWER(handle) LIKE ?', ["%{$v}%"]);
            }
        })
            ->orderByRaw($prefixFirst('handle'), ["{$term}%", "%{$term}%"])
            ->limit($candidateLimit)
            ->get();

        $uris = collect()
            ->merge($tournamentCandidates->map(fn ($t) => "/tournaments/{$t->id}"))
            ->merge($teamCandidates->map(fn ($t) => "/teams/{$t->id}"))
            ->merge($playerCandidates->map(fn ($p) => "/players/{$p->id}"))
            ->all();

        $pageViews = DB::table('page_views')
            ->whereIn('uri', $uris)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->select('uri', DB::raw('SUM(count) as total'))
            ->groupBy('uri')
            ->pluck('total', 'uri');

        // Score: exact prefix match (1000) + exact (uncorrected) match (75)
        // + length proximity (0–100) + popularity (0–200)
        $score = function (string $name, string $uri) use ($term, $termLen, $pageViews) {
            $lower = $this->stripAccents(strtolower($name));
            $diff = abs(mb_strlen($name) - $termLen);
            $containsExact = str_contains($lower, $term);

            return (str_starts_with($lower, $term) ? 1000 : 0)
                + ($containsExact ? 75 : 0)
                + max(0, 100 - $diff * 10)
                + min((int) ($pageViews->get($uri, 0) / 10), 200);
        };

        $tournaments = $tournamentCandidates
            ->sortByDesc(fn ($t) => $score($t->name, "/tournaments/{$t->id}"))
            ->take($perTypeLimit)
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'logo' => $t->logo,
                'score' => $score($t->name, "/tournaments/{$t->id}"),
                'popularity' => $pageViews->get("/tournaments/{$t->id}", 0),
            ])
            ->values();

        $teams = $teamCandidates
            ->sortByDesc(fn ($t) => $score($t->name, "/teams/{$t->id}"))
            ->take($perTypeLimit)
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'country_code' => $t->country_code,
                'logo' => $t->logo,
                'score' => $score($t->name, "/teams/{$t->id}"),
                'popularity' => $pageViews->get("/teams/{$t->id}", 0),
            ])
            ->values();

        $players = $playerCandidates
            ->sortByDesc(fn ($p) => $score($p->handle, "/players/{$p->id}"))
            ->take($perTypeLimit)
            ->map(fn ($p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'country_code' => $p->country_code,
                'photo' => $p->profile_photo,
                'score' => $score($p->handle, "/players/{$p->id}"),
                'popularity' => $pageViews->get("/players/{$p->id}", 0),
            ])
            ->values();

        return [
            'tournaments' => $tournaments->toArray(),
            'teams' => $teams->toArray(),
            'players' => $players->toArray(),
        ];
    }

    /**
     * Teams-only search, same typo-tolerant matching + scoring as search()
     * (exact-prefix/substring/length-proximity/popularity) — used by the
     * team-fan-picker Livewire component, kept separate since it has no use
     * for tournament/player candidates.
     *
     * @return list<array{id: int, name: string, country_code: ?string, logo: string, tags: list<string>, score: int}>
     */
    public function searchTeams(string $term, int $limit = 8, int $candidateLimit = 15): array
    {
        $term = strtolower(trim($term));
        $variants = $this->typoVariants($term);
        $term = $variants[0];
        $termLen = mb_strlen($term);

        $prefixFirst = fn ($v) => "CASE WHEN LOWER({$v}) LIKE ? THEN 0 WHEN LOWER({$v}) LIKE ? THEN 1 ELSE 2 END";

        $teamCandidates = Team::where(function ($q) use ($variants) {
            foreach ($variants as $v) {
                $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$v}%"])
                    ->orWhereRaw('LOWER(short_name) LIKE ?', ["%{$v}%"]);
            }
        })
            ->orderByRaw($prefixFirst('name'), ["{$term}%", "%{$term}%"])
            ->limit($candidateLimit)
            ->get();

        $pageViews = DB::table('page_views')
            ->whereIn('uri', $teamCandidates->map(fn ($t) => "/teams/{$t->id}")->all())
            ->where('viewed_at', '>=', now()->subDays(30))
            ->select('uri', DB::raw('SUM(count) as total'))
            ->groupBy('uri')
            ->pluck('total', 'uri');

        $score = function (string $name, int $id) use ($term, $termLen, $pageViews) {
            $lower = $this->stripAccents(strtolower($name));
            $diff = abs(mb_strlen($name) - $termLen);
            $containsExact = str_contains($lower, $term);

            return (str_starts_with($lower, $term) ? 1000 : 0)
                + ($containsExact ? 75 : 0)
                + max(0, 100 - $diff * 10)
                + min((int) ($pageViews->get("/teams/{$id}", 0) / 10), 200);
        };

        return $teamCandidates
            ->sortByDesc(fn ($t) => $score($t->name, $t->id))
            ->take($limit)
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'country_code' => $t->country_code,
                'logo' => $t->logo,
                'tags' => $t->fanTags(),
                'score' => $score($t->name, $t->id),
            ])
            ->values()
            ->toArray();
    }
}
