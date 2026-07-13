<?php

return [
    'title' => 'Transparency',
    'subtitle' => 'How GC Stats is developed, hosted and funded',
    'intro' => "GC Stats is a non-profit, community-driven project. One of my goals was to build an ethical and transparent project, and that's exactly what this page is for: we reveal the source code, how we work, the finances, everything.",
    'dev' => [
        'title' => 'Development',
        'body' => 'The source code of GC Stats and all of our projects is open source and publicly available on GitHub. Anyone can review the code, report a bug or suggest an improvement.<br><br>Decisions about additions or new features on the site are made by the staff after discussion and a vote, taking community feedback into account. When in doubt about major decisions, polls are held on Discord or on Twitter.',
        'link' => 'View the source code',
    ],
    'hosting' => [
        'title' => 'Hosting & Infrastructure',
        'body' => 'We rely on four providers to keep GC Stats fast, reliable and transparent. Here is exactly who hosts what, without ever reselling the data we collect.',
        'providers' => [
            'cdn' => [
                'name' => 'BunnyCDN',
                'role' => 'Content delivery',
                'body' => 'Caches our CSS/JS, as well as player, team & tournament images, on servers around the world to lighten the load on our own servers. Our open data site is also served by Bunny (data.gc-stats.app).',
            ],
            'servers' => [
                'name' => 'Hetzner',
                'role' => 'Application servers',
                'body' => 'Runs the application and database servers that power the website, the API and all of our services (except the open data site).',
            ],
            'mail' => [
                'name' => 'MXRoute',
                'role' => 'Email hosting',
                'body' => 'Handles the delivery and storage of our emails.',
            ],
            'domain' => [
                'name' => 'Infomaniak',
                'role' => 'Domain registrar',
                'body' => 'Swiss registrar managing our domain name. However, the name servers are managed by Bunny.',
            ],
        ],
    ],
    'data' => [
        'title' => 'Data collected',
        'body' => "We publicly document every piece of data we store: players, teams, tournaments and matches. No data is collected without a reason tied to the site's operation.",
        'link' => 'See the data we store',
    ],
    'finance' => [
        'title' => 'Finance',
        'body' => 'GC Stats publishes every single income and expense in a public ledger, updated with every movement.',
        'income' => 'Income',
        'expense' => 'Expenses',
        'balance' => 'Balance',
        'link' => 'View the full ledger',
        'last_update' => 'Last entry on :date',
        'no_entry' => 'No entry recorded yet.',
    ],
];
