<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Escape a raw user search term for safe use inside a LIKE pattern.
     */
    protected function escapeLike(string $value): string
    {
        return Str::of($value)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'])->toString();
    }

    /**
     * Read and whitelist-validate the `sort`/`direction` query params shared
     * by every server-side-sortable admin index page. Returns
     * [validated sort key, 'asc'|'desc'], falling back to the given
     * defaults for anything missing or not in $sortable.
     *
     * @param  list<string>  $sortable
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function resolveSort(Request $request, array $sortable, string $defaultSort, string $defaultDirection = 'desc'): array
    {
        $sort = $request->query('sort', $defaultSort);
        $direction = $request->query('direction', $defaultDirection);

        if (! in_array($sort, $sortable, true)) {
            $sort = $defaultSort;
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        return [$sort, $direction];
    }
}
