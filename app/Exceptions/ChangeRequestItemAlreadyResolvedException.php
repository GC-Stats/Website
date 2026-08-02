<?php

/**
 * GC-Stats — ChangeRequestItemAlreadyResolvedException
 *
 * Thrown when accept/reject loses the race to resolve a ChangeRequestItem
 * that another concurrent request already resolved first — see
 * ChangeRequestService::resolveItem().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class ChangeRequestItemAlreadyResolvedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This item has already been resolved.');
    }
}
