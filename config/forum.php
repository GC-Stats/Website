<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Forum rules sections
    |--------------------------------------------------------------------------
    |
    | Ordered list of forum.rules.sections.* keys (see lang/{locale}/forum.php)
    | — single source of truth shared by the full rules page
    | (resources/views/public/forum/rules.blade.php) and the rules-popup
    | component (resources/views/components/forum/rules-popup.blade.php),
    | so the two never drift out of sync.
    |
    */
    'rules_sections' => [
        'toxicity',
        'discrimination',
        'gender_identity',
        'sexual_harassment',
        'privacy',
        'spam',
        'content',
        'impersonation',
        'enforcement',
    ],

];
