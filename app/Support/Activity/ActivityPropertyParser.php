<?php

/**
 * GC-Stats — Activity property parser
 *
 * Entry point for turning a stored activity's raw `properties` JSON into a
 * normalized, human-readable shape. Dispatches by `log_name` since each
 * activity type (team, player, account, ...) can carry a different set of
 * property keys — see App\Support\Activity\Formatters.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Activity;

use App\Support\Activity\Formatters\AccountActivityFormatter;
use App\Support\Activity\Formatters\AdministrationActivityFormatter;
use App\Support\Activity\Formatters\DefaultActivityFormatter;
use App\Support\Activity\Formatters\ModerationActivityFormatter;
use App\Support\Activity\Formatters\OrganizationActivityFormatter;
use App\Support\Activity\Formatters\PlayerActivityFormatter;
use App\Support\Activity\Formatters\PublisherActivityFormatter;
use App\Support\Activity\Formatters\StaffActivityFormatter;
use App\Support\Activity\Formatters\TeamActivityFormatter;
use App\Support\Activity\Formatters\TournamentActivityFormatter;
use Spatie\Activitylog\Models\Activity;

class ActivityPropertyParser
{
    private const FORMATTERS = [
        'team' => TeamActivityFormatter::class,
        'player' => PlayerActivityFormatter::class,
        'account' => AccountActivityFormatter::class,
        'moderation' => ModerationActivityFormatter::class,
        'administration' => AdministrationActivityFormatter::class,
        'tournament' => TournamentActivityFormatter::class,
        'publisher' => PublisherActivityFormatter::class,
        'organization' => OrganizationActivityFormatter::class,
        'staff' => StaffActivityFormatter::class,
    ];

    /**
     * @return array{
     *     log_name: ?string,
     *     event: ?string,
     *     changes: list<array{field: string, label: string, old: mixed, new: mixed}>,
     *     context: list<array{key: string, label: string, value: mixed}>,
     * }
     */
    public static function parse(Activity $activity): array
    {
        $formatterClass = self::FORMATTERS[$activity->log_name] ?? DefaultActivityFormatter::class;

        return app($formatterClass)->format($activity);
    }
}
