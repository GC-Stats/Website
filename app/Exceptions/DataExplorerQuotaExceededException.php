<?php

/**
 * GC-Stats — DataExplorerQuotaExceededException
 *
 * Thrown by DataExplorerQuotaService::claimRequestSlot() when a user has used up
 * their share of the platform's monthly NL-query quota and has no valid
 * personal (BYOK) API key to fall back to.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class DataExplorerQuotaExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('data_explorer.errors.quota_exceeded'));
    }
}
