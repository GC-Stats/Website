<?php

/**
 * GC-Stats — SocialAccountAlreadyLinkedException
 *
 * Thrown when a Socialite provider identity is already linked to a
 * different user account than the one attempting to link it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;

class SocialAccountAlreadyLinkedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('account.errors.social_already_linked'));
    }
}
