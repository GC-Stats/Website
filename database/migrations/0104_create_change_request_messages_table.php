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
        Schema::create('change_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();

            // Nullable: system messages (e.g. "item #3 accepted by ...")
            // have no author, same as requested_by on change_requests.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type')->default('comment');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();

            $table->timestamps();

            $table->index('change_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_request_messages');
    }
};
