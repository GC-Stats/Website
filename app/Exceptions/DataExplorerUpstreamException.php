<?php

/**
 * GC-Stats — DataExplorerUpstreamException
 *
 * Thrown by DataExplorerService::execute() when the call to GC-Stats-API's
 * /internal/nl-query endpoint times out, fails, or returns a response that
 * can't be parsed — so the controller can surface a clear retryable error
 * instead of a blank/frozen screen.
 *
 * Carries $llmAttempted so callers can tell whether the failure happened
 * before or after GC-Stats-API actually reached the LLM call: DataExplorerService
 * already refunds the quota slot it claimed when $llmAttempted is false
 * (see DataExplorerQuotaService::releaseRequestSlot()) — this flag is exposed
 * mainly so it can still be asserted on / logged by callers, not so they
 * need to refund anything themselves.
 *
 * $requestId is the same UUID sent to GC-Stats-API and already persisted to
 * data_explorer_error_logs by the time this is thrown — surfaced to the
 * user as a reference code (see QueryController/BuilderController).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DataExplorerUpstreamException extends RuntimeException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        public readonly bool $llmAttempted = false,
        public readonly bool $retryable = true,
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message !== '' ? $message : __('data_explorer.errors.upstream_failed'), 0, $previous);
    }
}
