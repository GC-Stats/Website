<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per failed Data Explorer request (AI query or query
     * builder) — request_id is the same UUID sent to GC-Stats-API and
     * logged there too, so the two sides can be cross-referenced. Shown to
     * the user as a reference code; the full detail (payload, raw error)
     * stays server-side. Pruned after 30 days, see
     * PruneDataExplorerErrorLogs.
     */
    public function up(): void
    {
        Schema::create('data_explorer_error_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->json('request_payload');
            $table->string('error_code')->nullable();
            $table->text('error_message');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_explorer_error_logs');
    }
};
