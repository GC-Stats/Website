<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // The ceiling of permissions (from App\Support\TeamPermissions)
            // this team's own roles can ever be granted, set by a site
            // admin — null/empty means the team has no self-management
            // access at all until an admin grants some.
            $table->json('max_permissions')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('max_permissions');
        });
    }
};
