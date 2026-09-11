<?php

namespace Tests\Feature\Security;

use App\Enums\CategoryStatus;
use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_panel_user_cannot_access_category_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = User::factory()->create();
        $reporter->assignRole(Rbac::REPORTER);

        $this->actingAs($reporter)
            ->get('/newsroom/categories')
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_category_with_bangla_text(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $category = app(CategoryAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'জাতীয়',
            'name_en' => 'National',
            'slug' => 'national',
            'description' => 'বাংলা সংবাদ বিভাগের বর্ণনা',
            'parent_id' => null,
            'status' => CategoryStatus::Active->value,
            'sort_order' => 10,
            'show_in_menu' => true,
            'seo_title' => 'জাতীয় সংবাদ',
            'seo_description' => 'জাতীয় সংবাদ, রাজনীতি ও জনজীবনের খবর',
        ]);

        $this->assertSame('জাতীয়', $category->name_bn);
        $this->assertSame('national', $category->slug);
        $this->assertSame(CategoryStatus::Active, $category->status);
        $this->assertTrue($category->show_in_menu);
        $this->assertDatabaseHas('categories', ['name_bn' => 'জাতীয়', 'slug' => 'national']);
    }

    public function test_slug_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Category::factory()->create(['slug' => 'politics']);

        $this->expectException(ValidationException::class);

        app(CategoryAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'রাজনীতি',
            'slug' => 'politics',
            'parent_id' => null,
            'status' => CategoryStatus::Active->value,
            'sort_order' => 20,
            'show_in_menu' => true,
        ]);
    }

    public function test_parent_child_category_hierarchy_is_supported(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $service = app(CategoryAdministrationService::class);
        $parent = $service->create($this->superAdmin(), $this->payload('খেলা', 'sports'));
        $child = $service->create($this->superAdmin(), [
            ...$this->payload('ক্রিকেট', 'cricket'),
            'parent_id' => $parent->getKey(),
            'sort_order' => 11,
        ]);

        $this->assertTrue($parent->hasChild($child));
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_invalid_category_hierarchy_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $service = app(CategoryAdministrationService::class);
        $admin = $this->superAdmin();
        $parent = $service->create($admin, $this->payload('অর্থনীতি', 'economy'));
        $child = $service->create($admin, [
            ...$this->payload('ব্যাংক', 'banking'),
            'parent_id' => $parent->getKey(),
        ]);

        $this->expectException(ValidationException::class);

        $service->update($admin, $parent, [
            ...$parent->only([
                'name_bn',
                'name_en',
                'slug',
                'description',
                'status',
                'sort_order',
                'show_in_menu',
                'seo_title',
                'seo_description',
            ]),
            'status' => $parent->status->value,
            'parent_id' => $child->getKey(),
        ]);
    }

    public function test_parent_category_with_children_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $service = app(CategoryAdministrationService::class);
        $admin = $this->superAdmin();
        $parent = $service->create($admin, $this->payload('প্রযুক্তি', 'technology'));
        $service->create($admin, [
            ...$this->payload('মোবাইল', 'mobile'),
            'parent_id' => $parent->getKey(),
        ]);

        $this->expectException(AuthorizationException::class);

        $service->delete($admin, $parent);
    }

    public function test_category_seeder_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(11, Category::query()->count());
        $this->assertDatabaseHas('categories', ['name_bn' => 'জাতীয়', 'slug' => 'national']);
        $this->assertDatabaseHas('categories', ['name_bn' => 'মতামত', 'slug' => 'opinion']);
    }

    public function test_category_resource_uses_policy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = User::factory()->create();
        $reporter->assignRole(Rbac::REPORTER);

        $admin = $this->superAdmin();

        $this->actingAs($reporter);
        $this->assertFalse(CategoryResource::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(CategoryResource::canAccess());
    }

    /** @return array<string, mixed> */
    private function payload(string $nameBn, string $slug): array
    {
        return [
            'name_bn' => $nameBn,
            'name_en' => null,
            'slug' => $slug,
            'description' => null,
            'parent_id' => null,
            'status' => CategoryStatus::Active->value,
            'sort_order' => 10,
            'show_in_menu' => true,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Rbac::SUPER_ADMIN);

        return $admin;
    }
}
