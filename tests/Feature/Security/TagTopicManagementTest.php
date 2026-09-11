<?php

namespace Tests\Feature\Security;

use App\Enums\TagStatus;
use App\Enums\TopicStatus;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\TopicResource;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Services\TagAdministrationService;
use App\Services\TopicAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TagTopicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_panel_user_cannot_access_tag_or_topic_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $contributor = User::factory()->create();
        $contributor->assignRole(Rbac::CONTRIBUTOR);

        $this->actingAs($contributor)
            ->get('/newsroom/tags')
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get('/newsroom/topics')
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_tag_with_bangla_text(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $tag = app(TagAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'নির্বাচন',
            'name_en' => 'Election',
            'slug' => 'election',
            'status' => TagStatus::Active->value,
        ]);

        $this->assertSame('নির্বাচন', $tag->name_bn);
        $this->assertSame('election', $tag->slug);
        $this->assertSame(TagStatus::Active, $tag->status);
        $this->assertDatabaseHas('tags', ['name_bn' => 'নির্বাচন', 'slug' => 'election']);
    }

    public function test_authorized_user_can_create_topic_with_bangla_text_and_seo_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $topic = app(TopicAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'বিশেষ প্রতিবেদন',
            'name_en' => 'Special Report',
            'slug' => 'special-report',
            'description' => 'গভীর বিশ্লেষণধর্মী সংবাদ ও প্রতিবেদন',
            'featured' => true,
            'status' => TopicStatus::Active->value,
            'seo_title' => 'বিশেষ প্রতিবেদন',
            'seo_description' => 'গভীর বিশ্লেষণধর্মী সংবাদ, প্রতিবেদন ও ব্যাখ্যা',
        ]);

        $this->assertSame('বিশেষ প্রতিবেদন', $topic->name_bn);
        $this->assertTrue($topic->featured);
        $this->assertSame(TopicStatus::Active, $topic->status);
        $this->assertDatabaseHas('topics', ['name_bn' => 'বিশেষ প্রতিবেদন', 'slug' => 'special-report']);
    }

    public function test_tag_slug_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Tag::factory()->create(['slug' => 'election']);

        $this->expectException(ValidationException::class);

        app(TagAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'ভোট',
            'slug' => 'election',
            'status' => TagStatus::Active->value,
        ]);
    }

    public function test_topic_slug_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Topic::factory()->create(['slug' => 'analysis']);

        $this->expectException(ValidationException::class);

        app(TopicAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'বিশ্লেষণ',
            'slug' => 'analysis',
            'description' => null,
            'featured' => false,
            'status' => TopicStatus::Active->value,
            'seo_title' => null,
            'seo_description' => null,
        ]);
    }

    public function test_tag_and_topic_safe_deletion_uses_soft_deletes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->superAdmin();
        $tag = Tag::factory()->create();
        $topic = Topic::factory()->create();

        app(TagAdministrationService::class)->delete($admin, $tag);
        app(TopicAdministrationService::class)->delete($admin, $topic);

        $this->assertSoftDeleted('tags', ['id' => $tag->getKey()]);
        $this->assertSoftDeleted('topics', ['id' => $topic->getKey()]);
    }

    public function test_tag_and_topic_resources_use_policy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $contributor = User::factory()->create();
        $contributor->assignRole(Rbac::CONTRIBUTOR);

        $admin = $this->superAdmin();

        $this->actingAs($contributor);
        $this->assertFalse(TagResource::canAccess());
        $this->assertFalse(TopicResource::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(TagResource::canAccess());
        $this->assertTrue(TopicResource::canAccess());
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Rbac::SUPER_ADMIN);

        return $admin;
    }
}
