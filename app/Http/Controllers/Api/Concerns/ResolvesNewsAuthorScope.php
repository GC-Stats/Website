<?php

/**
 * GC-Stats — News author scope resolver
 *
 * Resolves the author constraint from the request. The dashboard (already
 * authenticated via HMAC) passes the logged-in author's ID through:
 *
 *   - Header  X-Author-Id   → used for GET requests (no body to sign)
 *   - Body    author_scope   → used for write requests (included in HMAC payload)
 *
 * Null means the dashboard is acting as an admin (no restriction).
 * The api_key table is reserved for the external Rust API and is NOT used here.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait ResolvesNewsAuthorScope
{
    /**
     * Return the author_id the caller is restricted to, or null for admin access.
     *
     * Priority: body `author_scope` (signed) > header `X-Author-Id` (trusted via HMAC).
     */
    protected function authorScope(Request $request): ?int
    {
        if ($request->has('author_scope')) {
            $value = $request->input('author_scope');

            return $value !== null ? (int) $value : null;
        }

        $header = $request->header('X-Author-Id');

        return $header !== null ? (int) $header : null;
    }

    /**
     * Abort 403 if the caller is scoped to a different author than the resource.
     */
    protected function assertAuthorAccess(?int $scope, int $resourceAuthorId): void
    {
        if ($scope !== null && $scope !== $resourceAuthorId) {
            abort(403, 'Access denied.');
        }
    }
}
