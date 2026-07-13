<?php

return [
    'title' => 'Terms of Service',
    'last_updated' => 'Last updated: :date',

    'intro' => 'By accessing or using GC Stats, you agree to be bound by these Terms of Service. Please read them carefully before using the platform.',

    'service' => [
        'title' => 'About the Service',
        'text' => 'GC Stats is a community platform dedicated to archiving and sharing competitive data from Valorant Game Changers tournaments and related events. The service provides access to tournament results, player statistics, team information, and match data. GC Stats is not affiliated with, endorsed by, or officially connected to Riot Games.',
    ],

    'access' => [
        'title' => 'Access & Eligibility',
        'text' => 'The platform is publicly accessible. Player profiles, team rosters, and match statistics are compiled by our team from publicly tracked tournament matches. Linking or editing a player profile, when applicable, is handled directly by our staff upon request and does not require authenticating a Riot account with GC Stats.',
    ],

    'riot_data' => [
        'title' => 'Riot Match Data',
        'text' => 'GC Stats retrieves match statistics directly from the official Riot Games API for matches identified as part of tracked tournaments. This data includes:',
        'items' => [
            'Riot IDs (game name and tagline) of participating players',
            'Match history for matches identified as part of tracked tournaments',
            'In-match statistics: scoreboard, performance summary, economy summary, and kill feed',
        ],
        'opt_in' => 'Basic match participation (name, statistics) is recorded for any player appearing in a tracked tournament match, as this is public competitive data. Additional profile information (biography, socials, photo) is only added with the player\'s or team\'s consent.',
        'correction' => 'If you are a player appearing in this data and wish to request a correction or removal, you may contact us at any time — see our Privacy Policy for details.',
    ],

    'prohibited' => [
        'title' => 'Prohibited Uses',
        'text' => 'You agree not to use the platform or its API for any of the following purposes:',
        'items' => [
            'Gambling, betting, or any activity involving wagering on game outcomes',
            'Spreading misinformation, fake statistics, or manipulated data',
            'Harassing, targeting, or doxing players or community members',
            'Any illegal activity under applicable law',
            'Automated bulk scraping beyond normal API usage',
            'Reselling or redistributing raw data as a standalone product',
        ],
    ],

    'api' => [
        'title' => 'API Usage',
        'text' => 'GC Stats provides a public API for community and GC-related projects. Use of the API is subject to these Terms. The API is intended for informational, analytical, and community purposes. Rate limiting may be enforced. GC Stats reserves the right to revoke API access for any usage that violates these Terms or Riot Games\'s developer policies.',
    ],

    'ip' => [
        'title' => 'Intellectual Property',
        'text' => 'Game statistics and structured data compiled by GC Stats are made available under a modified MIT license, as detailed in the public GitHub repository. Team logos, player images, tournament assets, and any branding elements remain the property of their respective rights holders. GC Stats claims no ownership over game assets, which are the property of Riot Games.',
    ],

    'liability' => [
        'title' => 'Limitation of Liability',
        'text' => 'GC Stats is provided "as is" without warranties of any kind. We do not guarantee the accuracy, completeness, or availability of any data. GC Stats shall not be liable for any indirect, incidental, or consequential damages arising from your use of the platform. Statistics displayed may contain errors; always verify with official sources for competitive decisions.',
    ],

    'changes' => [
        'title' => 'Changes to These Terms',
        'text' => 'We may update these Terms from time to time. The date at the top of this page reflects the most recent revision. Continued use of the platform after changes are posted constitutes acceptance of the updated Terms.',
    ],

    'contact' => [
        'title' => 'Contact',
        'text' => 'For any questions regarding these Terms:',
        'email' => 'contact@gc-stats.app',
    ],

    'riot_notice' => 'GC Stats isn\'t endorsed by Riot Games and doesn\'t reflect the views or opinions of Riot Games or anyone officially involved in producing or managing Riot Games properties. Riot Games and all associated properties are trademarks or registered trademarks of Riot Games, Inc.',
];
