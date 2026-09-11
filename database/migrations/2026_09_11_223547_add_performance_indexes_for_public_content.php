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
        Schema::table('articles', function (Blueprint $table): void {
            $table->index(['status', 'visibility', 'deleted_at', 'published_at', 'id'], 'articles_public_feed_index');
            $table->index(['slug', 'status', 'visibility', 'deleted_at', 'published_at'], 'articles_public_slug_index');
            $table->index(['is_featured', 'status', 'visibility', 'deleted_at', 'published_at'], 'articles_featured_public_index');
        });

        Schema::table('article_category', function (Blueprint $table): void {
            $table->index(['category_id', 'article_id', 'sort_order'], 'article_category_lookup_index');
        });

        Schema::table('article_author', function (Blueprint $table): void {
            $table->index(['author_id', 'article_id', 'sort_order'], 'article_author_lookup_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['status', 'show_in_menu', 'sort_order', 'name_bn'], 'categories_public_menu_index');
        });

        Schema::table('breaking_news', function (Blueprint $table): void {
            $table->index(['status', 'ends_at', 'starts_at', 'priority'], 'breaking_news_active_feed_index');
        });

        Schema::table('homepage_items', function (Blueprint $table): void {
            $table->index(['homepage_section_id', 'starts_at', 'ends_at', 'sort_order'], 'homepage_items_visible_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepage_items', function (Blueprint $table): void {
            $table->dropIndex('homepage_items_visible_index');
        });

        Schema::table('breaking_news', function (Blueprint $table): void {
            $table->dropIndex('breaking_news_active_feed_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_public_menu_index');
        });

        Schema::table('article_author', function (Blueprint $table): void {
            $table->dropIndex('article_author_lookup_index');
        });

        Schema::table('article_category', function (Blueprint $table): void {
            $table->dropIndex('article_category_lookup_index');
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex('articles_featured_public_index');
            $table->dropIndex('articles_public_slug_index');
            $table->dropIndex('articles_public_feed_index');
        });
    }
};
