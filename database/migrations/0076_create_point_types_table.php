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
        Schema::create('point_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index('name');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreignId('point_type_id')->nullable()->after('category')->constrained('point_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('point_type_id');
        });

        Schema::dropIfExists('point_types');
    }
};
