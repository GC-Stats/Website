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
        Schema::create('point_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('point_type_id')->constrained('point_types')->onDelete('cascade');
            $table->integer('amount');
            $table->foreignId('phase_qualification_result_id')->nullable()->constrained('phase_qualification_results')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'point_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_entries');
    }
};
