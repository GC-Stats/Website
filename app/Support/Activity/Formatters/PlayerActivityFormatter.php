<?php

namespace App\Support\Activity\Formatters;

class PlayerActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'handle' => 'Handle',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'country_code' => 'Country',
        'bio' => 'Bio',
        'vlr_id' => 'VLR ID',
        'liquipedia_link' => 'Liquipedia link',
        'is_active' => 'Active',
        'socials' => 'Social links',
        'val_id' => 'Riot ID',
        'discord_id' => 'Discord ID',
        'user_id' => 'Linked user',
        'source_player_id' => 'Source player',
        'target_player_id' => 'Target player',
        'fields_merged' => 'Merged fields',
        'logo_id' => 'Logo',
        'from' => 'From',
        'until' => 'Until',
    ];
}
