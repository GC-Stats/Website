<?php

namespace App\Support\Activity\Formatters;

class OrganizationActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'name' => 'Name',
        'slug' => 'Slug',
        'types' => 'Types',
        'country_code' => 'Country',
        'liquipedia_link' => 'Liquipedia',
        'socials' => 'Social links',
        'max_permissions' => 'Permission ceiling',
        'permissions' => 'Permissions',
        'organization_id' => 'Organization',
        'role' => 'Role',
        'user_id' => 'User',
        'logo_id' => 'Logo',
    ];
}
