<?php

namespace App\Support\Activity\Formatters;

class TeamActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'name' => 'Name',
        'short_name' => 'Short name',
        'country_code' => 'Country',
        'bio' => 'Bio',
        'vlr_id' => 'VLR ID',
        'liquipedia_link' => 'Liquipedia link',
        'is_active' => 'Active',
        'socials' => 'Social links',
        'tags' => 'Tags',
        'team_id' => 'Team',
        'player_id' => 'Player',
        'role' => 'Role',
        'user_id' => 'User',
        'permissions' => 'Permissions',
        'max_permissions' => 'Permission ceiling',
        'source_team_id' => 'Source team',
        'target_team_id' => 'Target team',
        'fields_merged' => 'Merged fields',
        'logo_id' => 'Logo',
        'from' => 'From',
        'until' => 'Until',
    ];
}
