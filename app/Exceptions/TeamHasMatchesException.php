<?php

/**
 * GC-Stats — TeamHasMatchesException
 *
 * Thrown when a team deletion is attempted for a team with recorded match
 * history. Guarding inside TeamMergeService::delete() itself (rather than
 * only at the call site) means the invariant holds regardless of how
 * delete() is reached.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class TeamHasMatchesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('admin.status.team-delete-blocked'));
    }
}
