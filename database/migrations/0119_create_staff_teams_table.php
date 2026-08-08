<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct staff-to-team roster history, for staff who work for a team
     * with no organization involved — mirrors staff_organizations exactly
     * (role, joined_at, left_at), and likewise allows several concurrent
     * active rows: a staff member may work directly with more than one team
     * at once.
     */
    public function up(): void
    {
        Schema::create('staff_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('role');
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_teams');
    }
};
