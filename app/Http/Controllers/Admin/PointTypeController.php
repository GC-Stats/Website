<?php

/**
 * GC-Stats — Admin: point types
 *
 * CRUD over point types — one row per named points system + validity period
 * (e.g. "Cash Cup Points" 2025, "Cash Cup Points" 2026 are two separate
 * rows). Gated by the existing tournaments.* permissions rather than a new
 * permission group, since this is tournament metadata.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PointTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.point-types.index', [
            'pointTypes' => PointType::orderBy('name')->orderByDesc('start_date')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.point-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $pointType = PointType::create($this->validatePointType($request));

        activity('tournament')->causedBy($request->user())
            ->performedOn($pointType)->log('point_type.created');

        return redirect()->route('admin.point-types.index')->with('status', 'point-type-created');
    }

    public function edit(PointType $pointType): View
    {
        return view('admin.point-types.edit', ['pointType' => $pointType]);
    }

    public function update(Request $request, PointType $pointType): RedirectResponse
    {
        $pointType->update($this->validatePointType($request));

        activity('tournament')->causedBy($request->user())
            ->performedOn($pointType)->log('point_type.updated');

        return redirect()->route('admin.point-types.index')->with('status', 'point-type-updated');
    }

    public function destroy(Request $request, PointType $pointType): RedirectResponse
    {
        $pointType->delete();

        activity('tournament')->causedBy($request->user())->log('point_type.deleted');

        return redirect()->route('admin.point-types.index')->with('status', 'point-type-deleted');
    }

    private function validatePointType(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
