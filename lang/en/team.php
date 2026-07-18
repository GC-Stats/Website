<?php

return [
    'title' => [
        'index' => ':team',
        'history' => ':team - Player History',
        'matches' => ':team - Matches',
        'maps' => ':team - Maps',
    ],

    'players' => 'Current Roster',
    'old_players' => 'Former Members',
    'news' => 'News',
    'seemore' => 'See more',

    // Other pages text
    'matches_history' => ':team – Matches History',
    'players_history' => ':team – Players History',

    'empty' => [
        'matches_history' => 'This team has no matches',
        'players_history' => 'This team has no past players',
        'maps' => 'This team has no maps played yet',
    ],

    'nav' => [
        'aria_label' => 'Team navigation',
        'overview' => 'Overview',
        'matches' => 'Matches',
        'maps' => 'Maps',
        'players_history' => 'Players history',
    ],

    'maps' => [
        'title' => ':team – Maps',
        'record' => 'Record',
        'atk_wr' => 'ATK Win%',
        'def_wr' => 'DEF Win%',
        'pick_rate' => 'Agent pick rate',
        'comps' => 'Comps played',
        'vs' => 'vs',
    ],

    'roles' => [
        'title' => 'Team roles',
        'back_to_team' => 'Back to :team',
        'member_count' => ':count member|:count members',
        'manage' => 'Manage',
        'delete' => 'Delete role',
        'delete_confirm' => 'Delete the :role role? This removes it from every member who has it.',
        'new_role' => [
            'title' => 'New role',
            'name_label' => 'Role name',
            'submit' => 'Create role',
        ],
        'permissions' => [
            'title' => 'Permissions',
            'save' => 'Save permissions',
            'empty_ceiling' => 'This team has not been granted any permissions yet — ask a site admin to set them.',
        ],
        'members' => [
            'title' => 'Members',
            'empty' => 'No members with this role yet.',
            'remove' => 'Remove',
            'remove_confirm' => 'Remove the :role role from :name?',
            'add' => 'Add a member',
            'search_placeholder' => 'Name or email',
            'search_submit' => 'Search',
            'search_empty' => 'No matching user without this role.',
            'assign' => 'Assign',
        ],
        'errors' => [
            'owner_role_protected' => 'The team_owner role cannot be deleted.',
            'owner_role_admin_only' => 'Assigning a new team_owner is a site admin action (team admin page).',
            'last_owner' => 'Cannot remove the last team_owner — assign another one first.',
        ],
        'status' => [
            'role-created' => 'Role created.',
            'role-deleted' => 'Role deleted.',
            'role-assigned' => 'Role assigned.',
            'role-removed' => 'Role removed.',
            'permissions-updated' => 'Permissions updated.',
        ],
    ],

    'edit' => [
        'title' => 'Edit team',
        'logo' => [
            'title' => 'Logo',
            'submit' => 'Upload',
        ],
        'profile' => [
            'title' => 'Profile',
            'submit' => 'Save changes',
        ],
        'fields' => [
            'name' => 'Team name',
            'short_name' => 'Short name',
            'country_code' => 'Country code',
            'bio' => 'Description',
            'website' => 'Website',
            'liquipedia_link' => 'Liquipedia link',
            'socials' => 'Social accounts',
        ],
        'status' => [
            'profile-updated' => 'Profile updated.',
            'logo-updated' => 'Logo updated.',
        ],
    ],
];
