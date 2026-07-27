<?php

namespace App\Support\Activity\Formatters;

class TournamentActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'status' => 'Status',
        'name' => 'Name',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'match_id' => 'Match',
        'tournament_id' => 'Tournament',
        'region' => 'Region',
        'category' => 'Category',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'location' => 'Location',
        'prize_pool' => 'Prize pool',
        'description' => 'Description',
        'liquipedia_link' => 'Liquipedia link',
        'active' => 'Active',
        'point_type_id' => 'Point type',
        'team_id' => 'Team',
        'team_a_id' => 'Team A',
        'team_b_id' => 'Team B',
        'map_name' => 'Map',
        'order' => 'Order',
        'is_completed' => 'Completed',
        'team_a_score' => 'Team A score',
        'team_b_score' => 'Team B score',
        'api_match_id' => 'Riot match ID',
        'success' => 'Success',
        'player_stats_count' => 'Player stats count',
        'rounds_count' => 'Rounds count',
        'advanced_stats_count' => 'Advanced stats count',
        'veto_count' => 'Veto count',
        'maps_count' => 'Maps count',
        'round_name' => 'Round name',
        'round_number' => 'Round number',
        'match_order' => 'Match order',
        'best_of' => 'Best of',
        'patch' => 'Patch',
        'scheduled_at' => 'Scheduled at',
        'phase_id' => 'Phase',
        'source_phase_id' => 'Source phase',
        'source_match_id' => 'Source match',
        'destination_type' => 'Destination type',
        'destination_phase_id' => 'Destination phase',
        'rank_from' => 'Rank from',
        'rank_to' => 'Rank to',
        'placement' => 'Placement',
        'placement_label' => 'Placement label',
        'points' => 'Points',
        'cash_prize_amount' => 'Cash prize amount',
        'cash_prize_currency' => 'Cash prize currency',
        'outcome' => 'Outcome',
        'phases' => 'Phases',
        'vetos' => 'Veto',
        'stream_channel_id' => 'Stream channel',
        'vod_id' => 'VOD',
        'game_map_id' => 'Map',
        'publisher_id' => 'Publisher',
        'url' => 'URL',
        'language_code' => 'Language',
    ];

    protected function formatListItem(string $key, array $item): string
    {
        if ($key === 'phases') {
            $name = $item['name'] ?? '?';
            $suffix = ! empty($item['parent_id']) ? ' ('.__('admin.activity.modal.sub_phase').')' : '';

            return $name.$suffix;
        }

        if ($key === 'vetos') {
            $map = $item['map_name'] ?? '?';
            $type = $item['type'] ?? null;

            return $type ? "{$map} ({$type})" : $map;
        }

        return parent::formatListItem($key, $item);
    }
}
