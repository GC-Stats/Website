<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the Organization-based attribution alongside the existing
     * publisher_id — deliberately additive: publisher_id/news_publishers
     * carry real production data, so the actual cutover (moving existing
     * articles' publisher_id into an equivalent organization_id, then
     * dropping publisher_id/news_publishers) is its own later migration
     * step, not this one. An article should only ever have one of the two
     * set at a time (enforced in Admin\NewsController, not at the DB level).
     *
     * Also adds scheduled_at (deferred auto-publish, see
     * App\Console\Commands\PublishScheduledNews) and validated_at/
     * validated_by (the review "validated" state — orthogonal to `status`,
     * a precondition checked at publish time rather than a status value
     * itself; reset to null whenever the article is edited again).
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('publisher_id')->constrained('organization')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->timestamp('validated_at')->nullable()->after('scheduled_at');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['scheduled_at', 'validated_at']);
        });
    }
};
