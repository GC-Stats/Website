<?php

namespace App\Support\Activity\Contracts;

use Spatie\Activitylog\Models\Activity;

interface ActivityFormatter
{
    /**
     * Turn a raw activity's `properties` JSON into a normalized shape:
     * a list of field-level `changes` (old/new) plus any remaining
     * `context` values, both with human-readable labels attached.
     *
     * @return array{
     *     log_name: ?string,
     *     event: ?string,
     *     changes: list<array{field: string, label: string, old: mixed, new: mixed}>,
     *     context: list<array{key: string, label: string, value: mixed}>,
     * }
     */
    public function format(Activity $activity): array;
}
