<?php

/**
 * GC-Stats — LastAuthMethodException
 *
 * Thrown when an account operation would leave a user with zero ways to
 * authenticate (no password and no linked social provider).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class LastAuthMethodException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('account.errors.last_auth_method'));
    }
}
