<?php

/**
 * GC-Stats — Best-of format calculator
 *
 * Shared heuristic used by the HenrikDev match importers to infer a
 * match's best-of format (1/3/5) from the number of maps played and the
 * highest map-win count reached by either team.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class BestOfCalculator
{
    public static function fromMapsPlayed(int $totalMapsPlayed, int $maxWins): int
    {
        if ($totalMapsPlayed > 3 || $maxWins == 3) {
            return 5;
        }

        if ($totalMapsPlayed > 1 || $maxWins == 2) {
            return 3;
        }

        return 1;
    }
}
