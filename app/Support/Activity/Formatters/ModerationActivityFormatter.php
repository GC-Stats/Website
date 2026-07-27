<?php

namespace App\Support\Activity\Formatters;

class ModerationActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'reason' => 'Reason',
        'provider' => 'Provider',
        'provider_created_at' => 'Provider account created',
        'sanction_type' => 'Sanction type',
        'target_type' => 'Target type',
        'target_id' => 'Target',
        'report_id' => 'Report',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'revoked_at' => 'Revoked',
        'status' => 'Status',
        'reaction_id' => 'Reaction',
        'reactor_id' => 'Reactor',
        'emote_id' => 'Emote',
        'reactions_removed' => 'Reactions removed',
        'resolution_note' => 'Resolution note',
    ];
}
