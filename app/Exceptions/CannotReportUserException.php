<?php

/**
 * GC-Stats — CannotReportUserException
 *
 * Thrown when a report submission is rejected for self-reporting. Volume
 * rate-limiting is handled separately by the `throttle:` route middleware.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class CannotReportUserException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('account.errors.cannot_report_user'));
    }
}
