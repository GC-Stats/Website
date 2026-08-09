<?php

namespace App\Support\Activity\Formatters;

class StaffActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'handle' => 'Handle',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'country_code' => 'Country',
        'pronouns' => 'Pronouns',
        'bio' => 'Bio',
        'vlr_id' => 'VLR.gg ID',
        'socials' => 'Social links',
        'is_active' => 'Active',
        'liquipedia_link' => 'Liquipedia',
        'staff_id' => 'Staff',
        'user_id' => 'User',
        'logo_id' => 'Logo',
    ];
}
