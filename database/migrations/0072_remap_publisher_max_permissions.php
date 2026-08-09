<?php

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
     * (see the now-removed App\Support\PublisherPermissions, whose catalog
     * is inlined below since the class itself no longer exists — see the
     * 0122 migration) — those names no longer existed in the catalog, so
     * groupedWithin() silently dropped the whole news/media groups for any
     * publisher whose ceiling still had the old names. Remap existing data
     * instead of leaving publishers stuck with an invisible ceiling, and
     * re-sync each publisher's roles against the new ceiling the same way
     * the (now-removed) Admin\NewsPublisherController::updateMaxPermissions
     * did, so the actual grants match what the (now visible) checkboxes show.
     */
    public function up(): void
    {
        $guard = 'publisher';

        $catalog = [
            'publisher.profile.edit', 'publisher.logo.upload',
            'publisher.news.view', 'publisher.news.edit', 'publisher.news.publish', 'publisher.news.delete',
            'publisher.media.view', 'publisher.media.upload', 'publisher.media.delete',
            'publisher.streams.view', 'publisher.streams.edit', 'publisher.streams.delete', 'publisher.streams.link',
            'publisher.vods.link',
            'publisher.roles.manage',
        ];

        foreach ($catalog as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        DB::table('news_publishers')->whereNotNull('max_permissions')->orderBy('id')->get()->each(function ($publisher) use ($guard) {
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

            foreach (Role::where('team_id', $publisher->id)->where('guard_name', $guard)->get() as $role) {
                $permissions = $role->name === 'publisher_owner'
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
