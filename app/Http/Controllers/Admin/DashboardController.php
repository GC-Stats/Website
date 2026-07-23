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
use App\Support\PublisherScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $overviewCards = [
            'tournaments' => 'tournaments.view',
            'teams' => 'teams.view',
            'players' => 'players.view',
            'matches' => 'tournaments.view',
        ];

        if (collect($overviewCards)->some(fn (string $permission) => $user->can($permission))) {
            $canViewMatches = $user->can('tournaments.view');
            $canViewTournaments = $user->can('tournaments.view');

            $canViewTeams = $user->can('teams.view');
            $canViewPlayers = $user->can('players.view');

            return view('admin.dashboard', [
                'stats' => [
                    'tournaments' => $user->can('tournaments.view') ? Tournament::count() : null,
                    'teams' => $canViewTeams ? Team::count() : null,
                    'players' => $canViewPlayers ? Player::count() : null,
                    'matches' => $canViewMatches ? Matchs::count() : null,
                ],
                'recentMatches' => $canViewMatches
                    ? Matchs::with(['teamA', 'teamB', 'tournament'])
                        ->whereIn('status', ['live', 'upcoming'])
                        ->whereNotNull('scheduled_at')
                        ->whereDate('scheduled_at', '!=', MatchDisplay::UNKNOWN_DATE)
                        ->orderByRaw("FIELD(status, 'live', 'upcoming')")
                        ->orderByDesc('scheduled_at')
                        ->paginate(5, ['*'], 'matches_page')
                    : null,
                'recentTournaments' => $canViewTournaments ? [
                    'live' => Tournament::where('status', 'live')->orderByDesc('start_date')
                        ->paginate(5, ['*'], 'tournaments_live_page')->appends(['tournaments_tab' => 'live']),
                    'upcoming' => Tournament::where('status', 'upcoming')->orderByDesc('start_date')
                        ->paginate(5, ['*'], 'tournaments_upcoming_page')->appends(['tournaments_tab' => 'upcoming']),
                    'inactive' => Tournament::where('active', false)->orderByDesc('start_date')
                        ->paginate(5, ['*'], 'tournaments_inactive_page')->appends(['tournaments_tab' => 'inactive']),
                ] : null,
                'recentTeamModifications' => $canViewTeams
                    ? Activity::with(['causer', 'subject'])
                        ->where('log_name', 'team')
                        ->where('subject_type', 'team')
                        ->latest()
                        ->paginate(5, ['*'], 'team_modifications_page')
                    : null,
                'recentPlayerModifications' => $canViewPlayers
                    ? Activity::with(['causer', 'subject'])
                        ->where('log_name', 'player')
                        ->where('subject_type', 'player')
                        ->latest()
                        ->paginate(5, ['*'], 'player_modifications_page')
                    : null,
            ]);
        }

        return match (true) {
            $user->can('reports.view') => redirect()->route('admin.reports.index'),
            $user->can('sanctions.view') => redirect()->route('admin.sanctions.index'),
            $user->can('activity.view') => redirect()->route('admin.activity.index'),
            $user->can('news.view') => redirect()->route('admin.news.index'),
            $user->can('news.publishers.view') => redirect()->route('admin.news.publishers.index'),
            $user->can('news.authors.view') => redirect()->route('admin.news.authors.index'),
            $user->can('news.media.view') => redirect()->route('admin.news.media.index'),
            $user->can('manage-roles') => redirect()->route('admin.roles.index'),

            PublisherScope::publisherIdsForUser($user->id)->isNotEmpty() => redirect()->route('admin.news.publishers.index'),
            $user->newsAuthor()->exists() => redirect()->route('admin.news.authors.index'),

            default => redirect()->route('home')->with('status', 'no-admin-section'),
        };
    }
}
