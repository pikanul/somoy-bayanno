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
        Schema::table('article_revisions', function (Blueprint $table) {
            $table->unsignedInteger('version')->nullable()->after('revision_number');
            $table->foreignId('changed_by')->nullable()->after('change_summary')->constrained('users')->nullOnDelete();

            $table->index(['article_id', 'version']);
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_revisions', function (Blueprint $table) {
            $table->dropIndex(['article_id', 'version']);
            $table->dropIndex(['changed_by']);
            $table->dropConstrainedForeignId('changed_by');
            $table->dropColumn('version');
        });
    }
};
