<?php

use App\Services\PublisherRoleService;
use App\Support\PublisherPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Publisher permissions were split into granular news/media actions
     * after publishers already had 'publisher.news.manage' and
     * 'publisher.authors.manage' baked into their max_permissions ceiling
     * (see App\Support\PublisherPermissions) — those names no longer exist
     * in the catalog, so PublisherPermissions::groupedWithin() silently
     * drops the whole news/media groups for any publisher whose ceiling
     * still has the old names. Remap existing data instead of leaving
     * publishers stuck with an invisible ceiling, and re-sync each
     * publisher's roles against the new ceiling the same way
     * Admin\NewsPublisherController::updateMaxPermissions does, so the
     * actual grants match what the (now visible) checkboxes show.
     */
    public function up(): void
    {
        foreach (PublisherPermissions::all() as $permission) {
            Permission::findOrCreate($permission, PublisherPermissions::GUARD);
        }

        DB::table('news_publishers')->whereNotNull('max_permissions')->orderBy('id')->get()->each(function ($publisher) {
            $ceiling = json_decode($publisher->max_permissions, true) ?? [];

            if (! in_array('publisher.news.manage', $ceiling, true) && ! in_array('publisher.authors.manage', $ceiling, true)) {
                return;
            }

            $ceiling = array_diff($ceiling, ['publisher.news.manage', 'publisher.authors.manage']);

            $ceiling = array_values(array_unique(array_merge($ceiling, [
                'publisher.news.view', 'publisher.news.edit', 'publisher.news.publish', 'publisher.news.delete',
                'publisher.media.view', 'publisher.media.upload', 'publisher.media.delete',
            ])));

            DB::table('news_publishers')->where('id', $publisher->id)->update([
                'max_permissions' => json_encode($ceiling),
            ]);

            foreach (Role::where('team_id', $publisher->id)->where('guard_name', PublisherPermissions::GUARD)->get() as $role) {
                $permissions = $role->name === PublisherRoleService::ROLE_OWNER
                    ? $ceiling
                    : array_intersect($role->permissions->pluck('name')->all(), $ceiling);

                $role->syncPermissions($permissions);
            }
        });
    }

    public function down(): void
    {
        // Not reversible — the previous single-permission names no longer
        // exist in the catalog, so there's nothing meaningful to roll back to.
    }
};
