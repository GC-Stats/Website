<?php

// Intentionally empty — author scoping is handled via the X-Author-Id header
// validated inside the HMAC payload, not via api_key rows (those are reserved
// for the external Rust API). This file is kept as a placeholder so migration
// history stays intact.

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
