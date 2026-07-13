<?php

/**
 * GC-Stats — Global stats API controller
 *
 * Exposes global counters (players, teams, matches, tournaments) for the
 * Dashboard's overview page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;

class ApiStatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'total_players' => Player::count(),
            'total_teams' => Team::count(),
            'total_matches' => Matchs::count(),
            'total_tournaments' => Tournament::count(),
        ]);
    }
}
