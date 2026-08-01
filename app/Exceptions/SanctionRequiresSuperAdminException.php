<?php

/**
 * GC-Stats — SanctionRequiresSuperAdminException
 *
 * Thrown when a non-super-admin tries to sanction a user who holds a staff
 * (admin-panel) role — see SanctionService::issue().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class SanctionRequiresSuperAdminException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('account.errors.sanction_requires_super_admin'));
    }
}
