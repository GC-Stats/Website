<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 32)->nullable()->after('name');
        });

        $this->backfillUsernames();

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 32)->nullable(false)->change();
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    /**
     * Derive a unique username for every existing account from its display
     * name, since the column is being made required + unique in the same
     * migration and there is no prior username to fall back on.
     */
    private function backfillUsernames(): void
    {
        $taken = [];

        DB::table('users')->orderBy('id')->select(['id', 'name'])->cursor()->each(function ($user) use (&$taken) {
            $base = substr(Str::slug($user->name, '_'), 0, 32);
            $base = $base !== '' ? $base : 'user';

            $username = $base;
            $suffix = 1;

            while (isset($taken[$username])) {
                $suffix++;
                $suffixString = '_'.$suffix;
                $username = substr($base, 0, 32 - strlen($suffixString)).$suffixString;
            }

            $taken[$username] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }
};
