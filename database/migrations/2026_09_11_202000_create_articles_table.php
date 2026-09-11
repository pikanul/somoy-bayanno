<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->default(ArticleType::Standard->value)->index();
            $table->string('headline_bn');
            $table->string('headline_en')->nullable();
            $table->string('short_headline_bn')->nullable();
            $table->string('slug')->unique();
            $table->string('subheadline_bn')->nullable();
            $table->text('summary_bn')->nullable();
            $table->longText('body_bn');
            $table->foreignId('primary_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('reporter_name')->nullable();
            $table->string('location')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('featured_image_url', 2048)->nullable();
            $table->string('image_caption')->nullable();
            $table->string('image_credit')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->string('status', 48)->default(ArticleStatus::Draft->value)->index();
            $table->string('visibility', 32)->default(ArticleVisibility::Public->value)->index();
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('is_breaking')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('updated_content_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('social_title')->nullable();
            $table->text('social_description')->nullable();
            $table->string('social_image', 2048)->nullable();
            $table->text('correction_note')->nullable();
            $table->text('internal_editor_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['primary_category_id', 'status', 'visibility', 'published_at'], 'articles_primary_category_publication_index');
            $table->index(['created_by', 'status']);
            $table->index(['is_featured', 'status', 'published_at']);
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'category_id']);
            $table->index(['category_id', 'is_primary']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('article_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'topic_id']);
            $table->index('topic_id');
        });

        Schema::create('article_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->string('credit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'author_id']);
            $table->index(['author_id', 'sort_order']);
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['article_id', 'revision_number']);
            $table->index(['article_id', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_author');
        Schema::dropIfExists('article_topic');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('articles');
    }
};
