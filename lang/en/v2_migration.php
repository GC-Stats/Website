<?php

return [
    'title' => 'Migration to V2',
    'description' => 'GC Stats is getting ready to deploy V2: some features are temporarily limited.',
    'intro' => 'GC Stats is getting ready to deploy its V2. To migrate the data safely, a maintenance is scheduled and some features will be temporarily limited.',
    'schedule' => [
        'title' => 'Maintenance date',
        'starts' => 'Maintenance starts',
        'note' => 'Date and time shown in your timezone.',
    ],
    'limited' => [
        'title' => 'Limited features',
        'intro' => 'During the migration, you won\'t be able to:',
        'items' => [
            'reactions' => 'Add, remove or report reactions',
            'forum' => 'Create a thread or reply on the forum',
            'change_requests' => 'Submit an edit request (player or team) or reply to one',
            'reports' => 'Report a user',
        ],
    ],
    'available' => [
        'title' => 'What stays available',
        'body' => 'The site stays fully browsable: tournaments, matches, teams, players, stats and the forum in read-only mode. Your account and settings stay accessible.',
    ],
    'thanks' => 'Thanks for your patience, see you very soon on V2!',
];
