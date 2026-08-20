<?php

return [
    'title' => 'Developer Documentation',
    'intro' => 'All our data to help you build awesome project! And if you feel like contributing to GC-Stats, we got you too!',

    'api_key' => [
        'title' => 'Requesting an API Key',
        'body' => 'To prevent abuse, our API requires authentication. If you want to build a tool or integrate our data, head over to our <strong>Discord server</strong> and open a support ticket.',
        'get_a_key' => 'Get an API Key',
        'warning' => 'Please include in your ticket:',
        'step_1' => 'Your project name & description',
        'step_2' => 'Intended API use case and scale',
        'step_3' => 'Estimated request volume',
        'btn' => 'Open a ticket on Discord',
        'forbidden_title' => 'Strict Restrictions',
        'forbidden_text' => 'It is strictly forbidden to use our data on platforms promoting gambling, misinformation, hate speech, or any illegal activity.',
    ],

    'swagger' => [
        'title' => 'API Reference',
        'body' => 'We map our API endpoints using Swagger. You can find all routes, request structures, and live response examples in our documentation.',
        'btn' => 'Explore Swagger UI',
    ],

    'doc_dashboard' => [
        'title' => 'API Dashboard',
        'body' => 'Track your API key usage, rate limits, and request statistics from the API dashboard.',
        'btn' => 'Open API Dashboard',
    ],

    'opendata' => [
        'title' => 'Open Data Portal',
        'body' => 'Prefer browsing or downloading datasets directly instead of using the API? Check out our Open Data portal.',
        'btn' => 'Visit Open Data Portal',
    ],

    'git' => [
        'title' => 'Open Source & Contributions',
        'body' => 'We decide to opensource most of our project, to let the community help, contribute and because we don\' believe in closed source. All our repositories except for the administration Dashboard are opensource & open for contributions. Make sure to read the guidelines before submitting a Pull Request.',
        'badge' => 'Check CONTRIBUTE.md',
    ],

    'dashboard' => [
        'title' => 'Overview',
        'nav' => [
            'title' => 'Developer',
            'dashboard' => 'Overview',
            'api-keys' => 'API Keys',
            'requests' => 'History',
            'stats' => 'Statistics',
            'back_to_site' => 'Back to site',
        ],
        'status' => [
            'api-key-toggled' => 'Key status updated.',
            'api-key-regenerated' => 'Key regenerated.',
        ],
        'errors' => [
            'not_own_key' => "This isn't one of your API keys.",
        ],
        'overview' => [
            'title' => 'Overview',
            'api-keys' => 'API Keys',
            'requests' => 'Requests',
            'avg_response_time' => 'Avg Response Time',
            'error_rate' => 'Error Rate',
        ],
        'api_keys' => [
            'title' => 'API Keys',
            'search_placeholder' => 'Search by name',
            '403' => 'You cannot edit this key.',
            'client_name' => 'Name',
            'rate_limit' => 'Rate limit',
            'status' => 'Status',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'empty' => 'No API keys yet.',
            'regenerate' => 'Regenerate',
            'toggled' => 'Key status updated.',
            'regenerate_confirm' => 'Regenerate this key? The current key will stop working immediately.',
            'reveal_banner' => [
                'title' => 'Key created',
                'body' => 'This link shows the clear key once. It cannot be retrieved again after this.',
                'copy' => 'Copy link',
                'copied' => 'Copied!',
            ],
        ],
        'filter' => [
            'key' => 'API Key',
        ],
        'requests' => [
            'title' => 'Request History',
            'when' => 'Date',
            'endpoint' => 'Endpoint',
            'method' => 'Method',
            'status' => 'Status',
            'duration' => 'Duration',
            'empty' => 'No requests found.',
            'filter' => [
                'all_endpoints' => 'All endpoints',
                'all_statuses' => 'All statuses',
                'from' => 'From',
                'to' => 'To',
                'submit' => 'Filter',
                'reset' => 'Reset',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'requests_24h' => 'Requests (24h)',
            'requests_7d' => 'Requests (7d)',
            'requests_30d' => 'Requests (30d)',
            'error_rate' => 'Error Rate (30d)',
            'chart_title' => 'Requests — Last 30 Days',
            'chart_requests' => 'Requests',
            'chart_errors' => 'Errors',
            'response_time_title' => 'Response Time (30d)',
            'min' => 'Min',
            'max' => 'Max',
            'p50' => 'Median (p50)',
            'p95' => 'p95',
            'p99' => 'p99',
            'top_endpoints_title' => 'Most Used Endpoints',
            'endpoint' => 'Endpoint',
            'requests' => 'Requests',
            'avg_response_time' => 'Avg Response Time',
            'error_rate_col' => 'Error %',
            'empty' => 'No requests in the last 30 days.',
        ],
    ]
];
