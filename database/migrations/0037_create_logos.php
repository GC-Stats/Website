<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->timestamp('from')->useCurrent();
            $table->timestamp('until')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        DB::table('team_logos')->orderBy('from')->each(function ($row) {
            DB::table('logos')->insert([
                'id' => $row->id,
                'entity_type' => 'team',
                'entity_id' => $row->team_id,
                'from' => $row->from,
                'until' => $row->until,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::dropIfExists('team_logos');
    }

    public function down(): void
    {
        Schema::create('team_logos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamp('from')->useCurrent();
            $table->timestamp('until')->nullable();
            $table->timestamps();
        });

        DB::table('logos')->where('entity_type', 'team')->orderBy('from')->each(function ($row) {
            DB::table('team_logos')->insert([
                'id' => $row->id,
                'team_id' => $row->entity_id,
                'from' => $row->from,
                'until' => $row->until,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        Schema::dropIfExists('logos');
    }
};
