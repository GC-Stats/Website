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

namespace App\Http\Controllers;

use App\Models\AboutProject;
use App\Models\AboutSection;
use App\Models\AboutTeamMember;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $sections = AboutSection::orderBy('order')->get()->keyBy('key');

        $team = AboutTeamMember::where('is_active', true)
            ->orderBy('order')
            ->get();

        $projects = AboutProject::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('about', [
            'sections' => $sections,
            'team' => $team,
            'projects' => $projects,
        ]);
    }
}
