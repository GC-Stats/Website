<?php

/**
 * GC-Stats — Public user profile page
 *
 * Distinct from Admin\UserController (site-management) and
 * Auth\AccountSettingsController (the signed-in user's own settings) — this
 * one is the public-facing profile any visitor can view, addressed by the
 * user's unique username (no id/slug needed).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsPublisher;
use App\Models\User;
use App\Support\PublisherPermissions;
use App\Support\UserRoleSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserProfileController extends Controller
{
    public function show(User $user): View
    {
        return view('users.show', $this->sharedData($user));
    }

    /**
     * The "News" tab — folded into the user profile rather than a
     * standalone page (unlike App\Http\Controllers\NewsController::author(),
     * which still serves authors with no linked user account). Only
     * reachable when the user has a linked News\Author profile.
     */
    public function news(Request $request, User $user): View
    {
        $user->load('newsAuthor');
        abort_if($user->newsAuthor === null, 404);

        $filters = array_filter($request->only(['lang', 'from', 'until']), fn ($v) => $v !== null && $v !== '');

        $articles = News::with(['publisher.currentLogo', 'author.currentLogo'])
            ->where('author_id', $user->newsAuthor->id)
            ->where('status', 'published')
            ->when(isset($filters['lang']), fn ($q) => $q->where('lang', $filters['lang']))
            ->when(isset($filters['from']), fn ($q) => $q->whereDate('published_at', '>=', $filters['from']))
            ->when(isset($filters['until']), fn ($q) => $q->whereDate('published_at', '<=', $filters['until']))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('users.news', [
            ...$this->sharedData($user),
            'articles' => $articles,
            'locales' => config('locales.supported'),
            'langFilter' => $filters['lang'] ?? '',
            'fromFilter' => $filters['from'] ?? '',
            'untilFilter' => $filters['until'] ?? '',
        ]);
    }

    /**
     * @return array{profileUser: User, publishers: Collection}
     */
    private function sharedData(User $user): array
    {
        $user->load(['player', 'team', 'roles:id,name', 'newsAuthor']);

        $publisherIds = UserRoleSummary::rolesGroupedByTeam($user->id, PublisherPermissions::GUARD)->keys()->all();

        return [
            'profileUser' => $user,
            'publishers' => NewsPublisher::whereIn('id', $publisherIds)->orderBy('name')->get(['id', 'name', 'slug']),
        ];
    }
}
