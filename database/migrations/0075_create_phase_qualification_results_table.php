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
        Schema::create('phase_qualification_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('phase_qualification_id')->constrained('phase_qualifications')->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('rank')->nullable();

            $table->timestamps();

            $table->unique(['phase_qualification_id', 'entity_type', 'entity_id'], 'pqr_qualification_entity_unique');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_qualification_results');
    }
};
