<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_authors', function (Blueprint $table) {
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'))->after('bio');
        });

        Schema::table('news_publishers', function (Blueprint $table) {
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'))->after('slug');
        });

        DB::table('news_authors')->orderBy('id')->each(function ($author) {
            $socials = array_filter([
                'twitter' => $author->twitter,
                'discord' => $author->discord,
                'instagram' => $author->instagram,
                'twitch' => $author->twitch,
                'youtube' => $author->youtube,
                'website' => $author->website,
            ]);

            DB::table('news_authors')->where('id', $author->id)->update([
                'socials' => json_encode((object) $socials),
            ]);
        });

        DB::table('news_publishers')->orderBy('id')->each(function ($publisher) {
            $socials = array_filter([
                'twitter' => $publisher->twitter,
                'discord' => $publisher->discord,
                'instagram' => $publisher->instagram,
                'youtube' => $publisher->youtube,
                'website' => $publisher->website,
            ]);

            DB::table('news_publishers')->where('id', $publisher->id)->update([
                'socials' => json_encode((object) $socials),
            ]);
        });

        Schema::table('news_authors', function (Blueprint $table) {
            $table->dropColumn(['twitter', 'discord', 'instagram', 'twitch', 'youtube', 'website']);
        });

        Schema::table('news_publishers', function (Blueprint $table) {
            $table->dropColumn(['twitter', 'discord', 'instagram', 'youtube', 'website']);
        });
    }

    public function down(): void
    {
        Schema::table('news_authors', function (Blueprint $table) {
            $table->string('twitter')->nullable();
            $table->string('discord')->nullable();
            $table->string('instagram')->nullable();
            $table->string('twitch')->nullable();
            $table->string('youtube')->nullable();
            $table->string('website')->nullable();
        });

        Schema::table('news_publishers', function (Blueprint $table) {
            $table->string('twitter')->nullable();
            $table->string('discord')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('website')->nullable();
        });

        DB::table('news_authors')->orderBy('id')->each(function ($author) {
            $socials = json_decode($author->socials ?? '{}', true) ?? [];

            DB::table('news_authors')->where('id', $author->id)->update([
                'twitter' => $socials['twitter'] ?? null,
                'discord' => $socials['discord'] ?? null,
                'instagram' => $socials['instagram'] ?? null,
                'twitch' => $socials['twitch'] ?? null,
                'youtube' => $socials['youtube'] ?? null,
                'website' => $socials['website'] ?? null,
            ]);
        });

        DB::table('news_publishers')->orderBy('id')->each(function ($publisher) {
            $socials = json_decode($publisher->socials ?? '{}', true) ?? [];

            DB::table('news_publishers')->where('id', $publisher->id)->update([
                'twitter' => $socials['twitter'] ?? null,
                'discord' => $socials['discord'] ?? null,
                'instagram' => $socials['instagram'] ?? null,
                'youtube' => $socials['youtube'] ?? null,
                'website' => $socials['website'] ?? null,
            ]);
        });

        Schema::table('news_authors', function (Blueprint $table) {
            $table->dropColumn('socials');
        });

        Schema::table('news_publishers', function (Blueprint $table) {
            $table->dropColumn('socials');
        });
    }
};
