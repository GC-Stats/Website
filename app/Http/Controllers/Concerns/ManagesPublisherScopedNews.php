<?php

/**
 * GC-Stats — Publisher-scoped news access
 *
 * Shared by Admin\NewsController and Admin\NewsMediaController: an article
 * (and its media) is manageable by a site editor holding the matching
 * AdminPermission, or by a publisher's own member holding the matching
 * 'publisher.news.*'/'publisher.media.*' permission (see
 * App\Support\PublisherPermissions) on the article's publisher.
 *
 * Deliberately does NOT grant anything to the article's own linked author:
 * a NewsAuthor profile is a personal byline (name/bio/socials,
 * self-editable — see Admin\NewsAuthorController), not a content-management
 * grant. Writing/editing/publishing/deleting an article always requires an
 * explicit publisher membership with the matching permission, same as
 * everything else in this system — an author with no publisher role has no
 * article/media capability at all.
 *
 * See App\Support\PublisherScope for why the publisher lookup can't go
 * through spatie's team-scoped `roles()` relation directly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

use App\Models\News;
use App\Support\PublisherScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ManagesPublisherScopedNews
{
    /**
     * @return Collection<int, int>
     */
    private function allowedPublisherIds(Request $request, string $publisherPermission): Collection
    {
        return PublisherScope::publisherIdsWithPermission($request->user()->id, $publisherPermission);
    }

    private function ensureCanManageArticle(Request $request, News $article, string $adminPermission, string $publisherPermission): void
    {
        abort_unless($this->canManageArticle($request, $article, $adminPermission, $publisherPermission), 403);
    }

    /**
     * Non-aborting version of ensureCanManageArticle(), for gating whether
     * an action button should even render (e.g. Admin\NewsController::edit()
     * passing canPublish/canArchive to the view).
     */
    private function canManageArticle(Request $request, News $article, string $adminPermission, string $publisherPermission): bool
    {
        $user = $request->user();

        if ($user->can($adminPermission)) {
            return true;
        }

        return $article->publisher_id
            && $this->allowedPublisherIds($request, $publisherPermission)->contains($article->publisher_id);
    }
}
