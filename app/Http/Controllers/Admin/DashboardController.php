<?php

/**
 * GC-Stats — Admin: dashboard entry point
 *
 * `/admin` itself isn't a page — it sends the user to the first section
 * their permissions actually grant, since which admin permissions a role
 * holds can vary (see App\Support\AdminPermissions).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Support\MatchDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $canViewTournaments = $user->can('tournaments.view');

        $recentTournaments = [
            'live' => Tournament::where('status', 'live')->orderByDesc('start_date')
                ->paginate(5, ['*'], 'tournaments_live_page')->appends(['tournaments_tab' => 'live']),
            'upcoming' => Tournament::where('status', 'upcoming')->orderByDesc('start_date')
                ->paginate(5, ['*'], 'tournaments_upcoming_page')->appends(['tournaments_tab' => 'upcoming']),
        ];

        if ($canViewTournaments) {
            $recentTournaments['inactive'] = Tournament::where('active', false)
                ->orderByDesc('start_date')
                ->paginate(5, ['*'], 'tournaments_inactive_page')
                ->appends(['tournaments_tab' => 'inactive']);
        }

        return view('admin.dashboard', [
            'stats' => [
                'tournaments' => Tournament::count(),
                'teams' => Team::count(),
                'players' => Player::count(),
                'matches' => Matchs::count(),
            ],
            'recentMatches' => Matchs::with(['teamA', 'teamB', 'tournament'])
                ->whereIn('status', ['live', 'upcoming'])
                ->whereNotNull('scheduled_at')
                ->whereDate('scheduled_at', '!=', MatchDisplay::UNKNOWN_DATE)
                ->orderByRaw("FIELD(status, 'live', 'upcoming')")
                ->orderByDesc('scheduled_at')
                ->paginate(5, ['*'], 'matches_page'),
            'recentTournaments' => $recentTournaments,
            'recentTeamModifications' => Activity::with(['causer', 'subject'])
                ->where('log_name', 'team')
                ->where('subject_type', 'team')
                ->latest()
                ->paginate(5, ['*'], 'team_modifications_page'),
            'recentPlayerModifications' => Activity::with(['causer', 'subject'])
                ->where('log_name', 'player')
                ->where('subject_type', 'player')
                ->latest()
                ->paginate(5, ['*'], 'player_modifications_page'),
        ]);
    }
}
