<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Author;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArticleArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_schema_contains_editorial_fields_and_normalized_pivots(): void
    {
        $this->assertTrue(Schema::hasColumns('articles', [
            'type',
            'headline_bn',
            'headline_en',
            'short_headline_bn',
            'slug',
            'subheadline_bn',
            'summary_bn',
            'body_bn',
            'primary_category_id',
            'reporter_name',
            'location',
            'source_name',
            'source_url',
            'featured_image_url',
            'image_caption',
            'image_credit',
            'video_url',
            'status',
            'visibility',
            'comments_enabled',
            'is_breaking',
            'is_featured',
            'published_at',
            'scheduled_at',
            'updated_content_at',
            'seo_title',
            'seo_description',
            'canonical_url',
            'social_title',
            'social_description',
            'social_image',
            'correction_note',
            'internal_editor_note',
            'created_by',
            'updated_by',
            'published_by',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasTable('article_category'));
        $this->assertTrue(Schema::hasTable('article_tag'));
        $this->assertTrue(Schema::hasTable('article_topic'));
        $this->assertTrue(Schema::hasTable('article_author'));
        $this->assertTrue(Schema::hasTable('article_revisions'));
        $this->assertTrue(Schema::hasColumns('article_revisions', [
            'article_id',
            'revision_number',
            'version',
            'snapshot',
            'change_summary',
            'changed_by',
            'created_by',
            'created_at',
        ]));
    }

    public function test_article_relationships_are_normalized(): void
    {
        $primaryCategory = Category::factory()->create();
        $secondaryCategory = Category::factory()->create();
        $tag = Tag::factory()->create();
        $topic = Topic::factory()->create();
        $author = Author::factory()->create();
        $user = User::factory()->create();

        $article = Article::factory()->create([
            'primary_category_id' => $primaryCategory->getKey(),
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
            'published_by' => $user->getKey(),
            'headline_bn' => 'প্রধান সংবাদ',
            'body_bn' => 'পূর্ণ সংবাদ কনটেন্ট',
        ]);

        $article->categories()->attach($primaryCategory, ['is_primary' => true, 'sort_order' => 0]);
        $article->categories()->attach($secondaryCategory, ['is_primary' => false, 'sort_order' => 1]);
        $article->tags()->attach($tag);
        $article->topics()->attach($topic);
        $article->authors()->attach($author, ['credit' => 'Reporter', 'sort_order' => 0]);

        $this->assertTrue($article->primaryCategory->is($primaryCategory));
        $this->assertTrue($article->creator->is($user));
        $this->assertTrue($article->updater->is($user));
        $this->assertTrue($article->publisher->is($user));
        $this->assertCount(2, $article->categories);
        $this->assertTrue($article->tags->first()->is($tag));
        $this->assertTrue($article->topics->first()->is($topic));
        $this->assertTrue($article->authors->first()->is($author));
        $this->assertDatabaseHas('article_category', [
            'article_id' => $article->getKey(),
            'category_id' => $primaryCategory->getKey(),
            'is_primary' => true,
        ]);
    }

    public function test_article_casts_status_type_visibility_and_dates(): void
    {
        $article = Article::factory()->published()->create([
            'type' => ArticleType::Breaking,
            'visibility' => ArticleVisibility::Public,
            'scheduled_at' => now()->addHour(),
            'comments_enabled' => false,
            'is_breaking' => true,
            'is_featured' => true,
        ]);

        $this->assertSame(ArticleType::Breaking, $article->type);
        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertSame(ArticleVisibility::Public, $article->visibility);
        $this->assertFalse($article->comments_enabled);
        $this->assertTrue($article->is_breaking);
        $this->assertTrue($article->is_featured);
        $this->assertNotNull($article->published_at);
        $this->assertNotNull($article->scheduled_at);
    }

    public function test_public_visibility_scope_excludes_draft_private_unlisted_and_future_content(): void
    {
        $visible = Article::factory()->published()->create(['slug' => 'visible-story']);
        Article::factory()->create(['slug' => 'draft-story', 'status' => ArticleStatus::Draft]);
        Article::factory()->published()->create(['slug' => 'private-story', 'visibility' => ArticleVisibility::Private]);
        Article::factory()->published()->create(['slug' => 'unlisted-story', 'visibility' => ArticleVisibility::Unlisted]);
        Article::factory()->published()->create(['slug' => 'future-story', 'published_at' => now()->addHour()]);

        $visibleArticles = Article::query()->publiclyVisible()->pluck('slug')->all();

        $this->assertSame([$visible->slug], $visibleArticles);
        $this->assertTrue($visible->isPubliclyVisible());
    }

    public function test_internal_editor_note_is_hidden_from_public_serialization(): void
    {
        $article = Article::factory()->published()->create([
            'internal_editor_note' => 'Private newsroom note',
            'correction_note' => 'Published correction note',
        ]);

        $payload = $article->toArray();

        $this->assertArrayNotHasKey('internal_editor_note', $payload);
        $this->assertArrayHasKey('correction_note', $payload);
    }

    public function test_article_revisions_preserve_snapshot_and_actor(): void
    {
        $actor = User::factory()->create();
        $article = Article::factory()->create([
            'headline_bn' => 'নতুন শিরোনাম',
            'body_bn' => 'নতুন কনটেন্ট',
            'created_by' => $actor->getKey(),
        ]);

        $revision = ArticleRevision::factory()->create([
            'article_id' => $article->getKey(),
            'revision_number' => 1,
            'version' => 1,
            'snapshot' => [
                'headline_bn' => 'পুরোনো শিরোনাম',
                'body_bn' => 'পুরোনো কনটেন্ট',
                'status' => ArticleStatus::Draft->value,
            ],
            'changed_by' => $actor->getKey(),
            'created_by' => $actor->getKey(),
        ]);

        $this->assertTrue($article->revisions->first()->is($revision));
        $this->assertTrue($revision->article->is($article));
        $this->assertTrue($revision->actor->is($actor));
        $this->assertTrue($revision->changedBy->is($actor));
        $this->assertSame('পুরোনো শিরোনাম', $revision->snapshot['headline_bn']);
    }

    public function test_article_policy_separates_own_edit_from_any_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole(Rbac::REPORTER);

        $otherReporter = User::factory()->create();
        $otherReporter->assignRole(Rbac::REPORTER);

        $editor = User::factory()->create();
        $editor->assignRole(Rbac::NEWS_EDITOR);

        $article = Article::factory()->create(['created_by' => $owner->getKey()]);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $article));
        $this->assertFalse(Gate::forUser($otherReporter)->allows('update', $article));
        $this->assertTrue(Gate::forUser($editor)->allows('update', $article));
    }
}
