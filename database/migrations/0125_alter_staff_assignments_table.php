<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Redesigns staff_assignments into the "XP" system's backing table:
     * staff_id becomes nullable (null = the organization itself is the
     * entry's holder, via organization_id doing double duty — see
     * StaffAssignment's docblock), organization_id and a free-form JSON
     * metadata column (role-specific extras, e.g. a caster's broadcast
     * language) are added. No stored dates — the displayed date is derived
     * from the linked tournament's start_date, see
     * StaffAssignment::tournamentStartDate().
     */
    public function up(): void
    {
        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('team_id')->constrained('organization')->nullOnDelete();
            $table->json('metadata')->nullable()->after('role');
        });

        DB::statement('ALTER TABLE staff_assignments MODIFY staff_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE staff_assignments MODIFY staff_id BIGINT UNSIGNED NOT NULL');

        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->dropColumn('metadata');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
