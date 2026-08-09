<?php

/**
 * GC-Stats — Personal (org-less) author dashboard: overview
 *
 * The equivalent of Organization\DashboardController::index() for a lone
 * author with no organization — much lighter, since there's no staff roster
 * or role catalog to summarize: just the author's own profile snapshot and
 * their article counts by status. See routes/personal-dashboard.php's
 * docblock for why this dashboard exists at all.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\PersonalDashboard;

use App\Http\Controllers\Public\Controller;
use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $author = $request->user()->newsAuthor;

        $counts = $author
            ? News::where('author_id', $author->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();

        return view('personal-dashboard.index', [
            'author' => $author,
            'draftCount' => $counts->get('draft', 0),
            'publishedCount' => $counts->get('published', 0),
            'archivedCount' => $counts->get('archived', 0),
        ]);
    }
}
