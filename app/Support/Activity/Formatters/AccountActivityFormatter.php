<?php

namespace App\Support\Activity\Formatters;

class AccountActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'method' => 'Method',
        'provider' => 'Provider',
        'provider_created_at' => 'Provider account created',
        'email' => 'Email',
        'name' => 'Name',
        'username' => 'Username',
        'reason' => 'Reason',
    ];
}
