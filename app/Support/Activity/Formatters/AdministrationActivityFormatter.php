<?php

namespace App\Support\Activity\Formatters;

class AdministrationActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'role' => 'Role',
        'via' => 'Via',
        'permissions' => 'Permissions',
        'name' => 'Name',
        'key' => 'Key',
        'user_id' => 'User',
        'client_name' => 'Client name',
        'rate_limit' => 'Rate limit',
        'is_active' => 'Active',
        'entry_date' => 'Entry date',
        'type' => 'Type',
        'category' => 'Category',
        'label' => 'Label',
        'description' => 'Description',
        'source_url' => 'Source URL',
        'amount_usd' => 'Amount (USD)',
        'amount_eur' => 'Amount (EUR)',
        'discord_role_id' => 'Discord role ID',
        'discord_role_name' => 'Discord role name',
        'source' => 'Source',
        'image_path' => 'Image',
        'photo_url' => 'Photo',
        'logo_url' => 'Logo',
    ];
}
