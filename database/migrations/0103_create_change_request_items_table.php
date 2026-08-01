<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('change_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();

            // Identifies both what changes and how to apply it — resolved
            // against a per subject_type/field FieldApplier, see
            // ChangeRequestApplierRegistry.
            $table->string('field');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();

            $table->string('status')->default('pending');

            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            // Accepted != applied: the applier can fail (stale data, roster
            // conflict...) without losing the human decision already made.
            $table->timestamp('applied_at')->nullable();
            $table->text('apply_error')->nullable();

            $table->timestamps();

            $table->index(['change_request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_request_items');
    }
};
