<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A key belongs either to a single user (existing admin-issued keys) or
     * to an organization (see App\Http\Controllers\Admin\ApiKeyController)
     * — never both. user_id stays nullable from 0088 for exactly this
     * reason.
     */
    public function up(): void
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->foreignId('organization_id')->after('user_id')->nullable()->constrained('organization')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
