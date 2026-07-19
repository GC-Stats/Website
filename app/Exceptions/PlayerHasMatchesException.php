<?php

/**
 * GC-Stats — PlayerHasMatchesException
 *
 * Thrown when a player deletion is attempted for a player with recorded
 * match stats. Mirrors TeamHasMatchesException — see its docblock. Guarding
 * inside PlayerMergeService::delete() itself (rather than only at the call
 * site) means the invariant holds regardless of how delete() is reached.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class PlayerHasMatchesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('admin.status.player-delete-blocked'));
    }
}
