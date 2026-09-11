<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Services\ArticleAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleCmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_create_draft_article_with_normalized_relationships_and_sanitized_body(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = $this->userWithRole(Rbac::REPORTER);
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $topic = Topic::factory()->create();
        $author = Author::factory()->create();

        $article = app(ArticleAdministrationService::class)->create($reporter, $this->validPayload([
            'primary_category_id' => $category->getKey(),
            'tag_ids' => [$tag->getKey()],
            'topic_ids' => [$topic->getKey()],
            'author_ids' => [$author->getKey()],
            'body_bn' => '<p>নিরাপদ লেখা</p><script>alert("x")</script><a href="javascript:alert(1)">bad</a>',
        ]));

        $this->assertSame($reporter->getKey(), $article->created_by);
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertStringContainsString('<p>নিরাপদ লেখা</p>', $article->body_bn);
        $this->assertStringNotContainsString('<script>', $article->body_bn);
        $this->assertStringNotContainsString('javascript:', $article->body_bn);
        $this->assertTrue($article->categories->first()->is($category));
        $this->assertTrue($article->tags->first()->is($tag));
        $this->assertTrue($article->topics->first()->is($topic));
        $this->assertTrue($article->authors->first()->is($author));
        $this->assertDatabaseHas('article_revisions', ['article_id' => $article->getKey(), 'revision_number' => 1]);
    }

    public function test_reporter_cannot_use_publication_controls_without_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(ArticleAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), $this->validPayload([
            'status' => ArticleStatus::Published->value,
            'published_at' => now(),
            'is_featured' => true,
        ]));
    }

    public function test_reporter_can_edit_own_draft_but_not_another_reporter_article_or_own_published_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = $this->userWithRole(Rbac::REPORTER);
        $otherReporter = $this->userWithRole(Rbac::REPORTER);
        $draft = Article::factory()->create(['created_by' => $owner->getKey(), 'status' => ArticleStatus::Draft]);
        $othersDraft = Article::factory()->create(['created_by' => $otherReporter->getKey(), 'status' => ArticleStatus::Draft]);
        $published = Article::factory()->published()->create(['created_by' => $owner->getKey()]);

        $updated = app(ArticleAdministrationService::class)->update($owner, $draft, $this->validPayload([
            'headline_bn' => 'নিজস্ব খসড়া সংশোধন',
            'slug' => $draft->slug,
        ]));

        $this->assertSame('নিজস্ব খসড়া সংশোধন', $updated->headline_bn);
        $this->assertTrue(Gate::forUser($owner)->denies('update', $othersDraft));
        $this->assertTrue(Gate::forUser($owner)->denies('update', $published));
    }

    public function test_direct_article_creation_cannot_start_as_published_even_for_editor(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);

        $this->expectException(ValidationException::class);

        app(ArticleAdministrationService::class)->create($editor, $this->validPayload([
            'status' => ArticleStatus::Published->value,
            'published_at' => now()->subMinute(),
            'is_breaking' => true,
        ]));
    }

    public function test_internal_editor_notes_are_restricted_and_hidden_from_public_serialization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(ArticleAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), $this->validPayload([
            'internal_editor_note' => 'Private newsroom instruction',
        ]));
    }

    public function test_authorized_editor_can_store_internal_editor_note_without_public_serialization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $article = app(ArticleAdministrationService::class)->create($this->userWithRole(Rbac::NEWS_EDITOR), $this->validPayload([
            'internal_editor_note' => 'Private newsroom instruction',
        ]));

        $this->assertSame('Private newsroom instruction', $article->internal_editor_note);
        $this->assertArrayNotHasKey('internal_editor_note', $article->toArray());
    }

    public function test_external_urls_are_validated(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(ArticleAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), $this->validPayload([
            'source_url' => 'not-a-valid-url',
        ]));
    }

    public function test_article_resource_uses_policy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $userWithoutRole = User::factory()->create();
        $reporter = $this->userWithRole(Rbac::REPORTER);

        $this->actingAs($userWithoutRole);
        $this->assertFalse(ArticleResource::canAccess());

        $this->actingAs($reporter);
        $this->assertTrue(ArticleResource::canAccess());
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_replace([
            'type' => ArticleType::Standard->value,
            'headline_bn' => 'বাংলা সংবাদ শিরোনাম',
            'headline_en' => null,
            'short_headline_bn' => null,
            'slug' => 'bangla-news-'.fake()->unique()->numberBetween(1000, 999999),
            'subheadline_bn' => null,
            'summary_bn' => 'সংক্ষিপ্ত সারাংশ',
            'body_bn' => '<p>পূর্ণ সংবাদ প্রতিবেদন</p>',
            'primary_category_id' => $category->getKey(),
            'category_ids' => [],
            'tag_ids' => [],
            'topic_ids' => [],
            'author_ids' => [],
            'reporter_name' => null,
            'location' => null,
            'source_name' => null,
            'source_url' => null,
            'featured_image_url' => null,
            'image_caption' => null,
            'image_credit' => null,
            'video_url' => null,
            'status' => ArticleStatus::Draft->value,
            'visibility' => ArticleVisibility::Public->value,
            'comments_enabled' => true,
            'is_breaking' => false,
            'is_featured' => false,
            'published_at' => null,
            'scheduled_at' => null,
            'updated_content_at' => null,
            'seo_title' => null,
            'seo_description' => null,
            'canonical_url' => null,
            'social_title' => null,
            'social_description' => null,
            'social_image' => null,
            'correction_note' => null,
            'internal_editor_note' => null,
        ], $overrides);
    }
}
