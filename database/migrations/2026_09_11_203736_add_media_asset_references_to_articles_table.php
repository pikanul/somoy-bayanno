<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->foreignId('featured_media_id')->nullable()->after('source_url')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('social_media_id')->nullable()->after('social_image')->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('social_media_id');
            $table->dropConstrainedForeignId('featured_media_id');
        });
    }
};
