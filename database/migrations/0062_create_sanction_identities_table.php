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
        Schema::create('sanction_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sanction_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->timestamps();

            $table->unique(['sanction_id', 'type', 'value']);
            $table->index(['type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanction_identities');
    }
};
