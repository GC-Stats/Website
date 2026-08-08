<?php

/**
 * GC-Stats — News access (organization/personal)
 *
 * Shared by Admin\NewsController, Admin\NewsMediaController and
 * Public\NewsController: an article (and its media) is manageable by:
 *  - a site editor holding the matching AdminPermission, or
 *  - an organization's own member holding the matching
 *    'organization.news.*'/'organization.media.*' permission (guard
 *    'organization') on the article's organization, or
 *  - the article's own linked author, if they hold the site-wide
 *    'news.author' permission (see AdminPermissions) and the article has
 *    no organization — a personal byline gets full autonomy over their own
 *    org-less articles, nothing more.
 *
 * $organizationPermission is nullable only for actions with no organization
 * equivalent at all (there are none left after the personal-only case is
 * excluded, but kept nullable for forward compatibility rather than forcing
 * every call site to pass one).
 *
 * Having a NewsAuthor profile alone grants no article capability — it's a
 * personal byline (name/bio/socials, self-editable — see
 * Admin\NewsAuthorController), not a content-management grant on its own.
 *
 * See App\Support\OrganizationScope for why this lookup can't go through
 * spatie's team-scoped `roles()` relation directly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

use App\Models\News;
use App\Support\OrganizationScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ManagesNewsAccess
{
    /**
     * @return Collection<int, int>
     */
    private function allowedOrganizationIds(Request $request, string $organizationPermission): Collection
    {
        return OrganizationScope::organizationIdsWithPermission($request->user()->id, $organizationPermission);
    }

    private function ensureCanManageArticle(
        Request $request,
        News $article,
        string $adminPermission,
        ?string $organizationPermission = null,
        ?string $personalPermission = null,
    ): void {
        abort_unless($this->canManageArticle($request, $article, $adminPermission, $organizationPermission, $personalPermission), 403);
    }

    /**
     * Non-aborting version of ensureCanManageArticle(), for gating whether
     * an action button should even render (e.g. Admin\NewsController::edit()
     * passing canPublish/canArchive to the view).
     */
    private function canManageArticle(
        Request $request,
        News $article,
        string $adminPermission,
        ?string $organizationPermission = null,
        ?string $personalPermission = null,
    ): bool {
        $user = $request->user();

        if ($user->can($adminPermission)) {
            return true;
        }

        if ($article->organization_id) {
            return $organizationPermission !== null
                && $this->allowedOrganizationIds($request, $organizationPermission)->contains($article->organization_id);
        }

        if ($personalPermission && $article->author_id && $user->can($personalPermission)) {
            return $article->author_id === $user->newsAuthor?->id;
        }

        return false;
    }
}
