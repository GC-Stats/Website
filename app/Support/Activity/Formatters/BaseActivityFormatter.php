<?php

/**
 * GC-Stats — Activity formatter base
 *
 * Shared parsing logic for every log_name-specific formatter: splits the
 * activity's `properties` JSON into `changes` (the `changes` sub-array
 * written by App\Support\Activity\ActivityChangeSet) and `context` (every
 * other property, e.g. ids or reasons that aren't a before/after diff).
 * Subclasses supply a $labels map for nicer field names, and can override
 * formatValue() for fields whose value needs bespoke rendering (e.g. a
 * list of phase/veto objects) rather than the generic scalar/list handling
 * here — the modal only ever renders the resulting strings, never raw
 * JSON, so this is the one place that decides how a value reads.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Activity\Formatters;

use App\Support\Activity\Contracts\ActivityFormatter;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

abstract class BaseActivityFormatter implements ActivityFormatter
{
    /** @var array<string, string> field/context key => human label */
    protected array $labels = [];

    public function format(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $changes = $properties['changes'] ?? [];

        return [
            'log_name' => $activity->log_name,
            // Every call site in this codebase passes the event code to
            // ->log() (which Spatie stores as `description`), not ->event()
            // — the `event` column itself is never populated, so it's not
            // usable here.
            'event' => $activity->description,
            'changes' => collect($changes)
                ->map(fn (array $change, string $field) => [
                    'field' => $field,
                    'label' => $this->label($field),
                    'old' => $this->formatValue($field, $change['old'] ?? null),
                    'new' => $this->formatValue($field, $change['new'] ?? null),
                ])
                ->values()
                ->all(),
            'context' => collect($properties)
                ->except('changes')
                ->map(fn (mixed $value, string $key) => [
                    'key' => $key,
                    'label' => $this->label($key),
                    'value' => $this->formatValue($key, $value),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function label(string $key): string
    {
        return $this->labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Render a raw property value as a short, human-readable string —
     * never JSON. Handles scalars, booleans, flat lists (permissions,
     * tags, ...) and lists of objects reasonably well on its own; override
     * per field in a subclass for anything that deserves a nicer summary.
     */
    protected function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return __('admin.activity.modal.empty_value');
        }

        if (is_bool($value)) {
            return $value ? __('admin.activity.modal.yes') : __('admin.activity.modal.no');
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        if (array_is_list($value)) {
            if (! is_array($value[0] ?? null)) {
                return implode(', ', $value);
            }

            return collect($value)->map(fn (array $item) => $this->formatListItem($key, $item))->implode(', ');
        }

        return collect($value)->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v)))->implode(', ');
    }

    /**
     * One line for a single item of a list-of-objects value (e.g. one
     * phase, one veto row) — drops noise (id, null/empty fields) and joins
     * whatever's left. Subclasses with a clearer notion of what identifies
     * an item (a phase's name, a veto's map) should override this per
     * $key instead of reproducing the whole formatValue() logic.
     */
    protected function formatListItem(string $key, array $item): string
    {
        return Collection::make($item)
            ->except(['id'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => is_scalar($v) ? $v : json_encode($v))
            ->implode(', ');
    }
}
