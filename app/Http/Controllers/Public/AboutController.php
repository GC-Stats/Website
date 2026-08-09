<?php

/**
 * GC-Stats — About Us controller
 *
 * Renders the public "About Us" page (project presentation, team,
 * projects and future plans), with content stored in the database.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\AboutProject;
use App\Models\AboutSection;
use App\Models\User;
use App\Support\PermissionTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $sections = AboutSection::orderBy('order')->get()->keyBy('key');

        $team = $this->team();

        $projects = AboutProject::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('public.about', [
            'sections' => $sections,
            'team' => $team,
            'projects' => $projects,
        ]);
    }

    /**
     * The "team" shown on the About page is now simply every user holding a
     * global (not team-scoped) role — same population App\Models\User's
     * hasGlobalRole() treats as site staff. Queried against the raw pivot
     * table for the same reason hasGlobalRole()/isSuperAdmin() are: this
     * must not depend on whatever PermissionTeam context happens to be
     * active for the current request.
     */
    private function team(): Collection
    {
        $staffIds = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('team_id', PermissionTeam::GLOBAL_ID)
            ->distinct()
            ->pluck('model_id');

        return User::whereIn('id', $staffIds)
            ->with(['roles' => fn ($query) => $query->where('model_has_roles.team_id', PermissionTeam::GLOBAL_ID)])
            ->orderBy('name')
            ->get();
    }
}
