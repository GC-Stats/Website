<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_vetos', function (Blueprint $table) {
            $table->string('side', 3)->nullable()->after('type');
            $table->foreignId('side_picked_by')->nullable()->constrained('teams')->after('side');
        });
    }

    public function down(): void
    {
        Schema::table('match_vetos', function (Blueprint $table) {
            $table->dropColumn('side');
            $table->dropForeign(['side_picked_by']);
            $table->dropColumn('side_picked_by');
        });
    }
};
