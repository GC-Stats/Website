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
        Schema::create('team_name_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');

            $table->string('name');
            $table->date('from');
            $table->date('until')->nullable();
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_name_history');
    }
};
