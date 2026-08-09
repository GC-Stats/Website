<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The full publisher → organization cutover: creates an Organization
     * for every existing NewsPublisher (name/slug/socials/logo only — not
     * roles/permissions, which admins re-grant manually afterward through
     * the normal organization role UI), repoints news/stream_channels/vods
     * at the new organizations, then drops publisher_id from all three
     * tables and the news_publishers table itself, along with every
     * guard='publisher' role/permission row. Deliberately uses raw DB
     * queries throughout rather than Eloquent models, since migrations
     * shouldn't depend on application model classes that can change shape
     * over time (and NewsPublisher no longer exists as a model at all).
     */
    public function up(): void
    {
        Schema::table('stream_channels', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('publisher_id')->constrained('organization')->nullOnDelete();
        });

        Schema::table('vods', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('publisher_id')->constrained('organization')->nullOnDelete();
        });

        $existingSlugs = DB::table('organization')->pluck('slug')->all();

        $idMap = [];

        foreach (DB::table('news_publishers')->get() as $publisher) {
            $slug = $publisher->slug;
            $suffix = 1;
            while (in_array($slug, $existingSlugs, true)) {
                $slug = $publisher->slug.'-'.(++$suffix);
            }
            $existingSlugs[] = $slug;

            $organizationId = DB::table('organization')->insertGetId([
                'name' => $publisher->name,
                'slug' => $slug,
                'types' => json_encode(['media']),
                'socials' => $publisher->socials ?? new Expression('(JSON_OBJECT())'),
                'created_at' => $publisher->created_at,
                'updated_at' => now(),
            ]);

            $idMap[$publisher->id] = $organizationId;

            DB::table('logos')
                ->where('entity_type', 'publisher')
                ->where('entity_id', $publisher->id)
                ->update(['entity_type' => 'organization', 'entity_id' => $organizationId]);
        }

        foreach ($idMap as $publisherId => $organizationId) {
            DB::table('news')->where('publisher_id', $publisherId)->update(['organization_id' => $organizationId]);
            DB::table('stream_channels')->where('publisher_id', $publisherId)->update(['organization_id' => $organizationId]);
            DB::table('vods')->where('publisher_id', $publisherId)->update(['organization_id' => $organizationId]);
        }

        Schema::table('news', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publisher_id');
        });

        Schema::table('stream_channels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publisher_id');
        });

        Schema::table('vods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publisher_id');
        });

        Schema::dropIfExists('news_publishers');

        $publisherRoleIds = DB::table('roles')->where('guard_name', 'publisher')->pluck('id');
        DB::table('model_has_roles')->whereIn('role_id', $publisherRoleIds)->delete();
        DB::table('role_has_permissions')->whereIn('role_id', $publisherRoleIds)->delete();
        DB::table('roles')->where('guard_name', 'publisher')->delete();

        $publisherPermissionIds = DB::table('permissions')->where('guard_name', 'publisher')->pluck('id');
        DB::table('model_has_permissions')->whereIn('permission_id', $publisherPermissionIds)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $publisherPermissionIds)->delete();
        DB::table('permissions')->where('guard_name', 'publisher')->delete();
    }

    /**
     * Irreversible — publisher_id/news_publishers/guard='publisher' data is
     * gone by the time down() would run. Rolling back only restores the
     * empty columns/table shape, not the migrated content.
     */
    public function down(): void
    {
        Schema::create('news_publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'));
            $table->json('max_permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('publisher_id')->nullable()->after('author_id')->constrained('news_publishers')->nullOnDelete();
        });

        Schema::table('stream_channels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->foreignId('publisher_id')->nullable()->after('id')->constrained('news_publishers')->nullOnDelete();
        });

        Schema::table('vods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->foreignId('publisher_id')->nullable()->after('game_map_id')->constrained('news_publishers')->nullOnDelete();
        });
    }
};
