<?php

namespace Tests\Feature\Security;

use App\Enums\AuthorStatus;
use App\Filament\Resources\AuthorResource;
use App\Http\Resources\PublicAuthorResource;
use App\Models\Author;
use App\Models\User;
use App\Services\AuthorAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_panel_user_cannot_access_author_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $contributor = User::factory()->create();
        $contributor->assignRole(Rbac::CONTRIBUTOR);

        $this->actingAs($contributor)
            ->get('/newsroom/authors')
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_author_with_optional_cms_user_link(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cmsUser = User::factory()->create();
        $author = app(AuthorAdministrationService::class)->create($this->superAdmin(), [
            'user_id' => $cmsUser->getKey(),
            'name_bn' => 'রফিকুল ইসলাম',
            'name_en' => 'Rafiqul Islam',
            'slug' => 'rafiqul-islam',
            'designation' => 'Senior Reporter',
            'bio_bn' => 'বাংলা জীবনী',
            'bio_en' => 'English biography',
            'photo' => 'authors/rafiqul.jpg',
            'email' => 'rafiqul@example.com',
            'phone' => '+8801700000000',
            'facebook_url' => 'https://facebook.com/rafiqul',
            'x_url' => 'https://x.com/rafiqul',
            'linkedin_url' => 'https://linkedin.com/in/rafiqul',
            'website_url' => 'https://example.com',
            'status' => AuthorStatus::Active->value,
            'featured' => true,
            'seo_title' => 'রফিকুল ইসলাম',
            'seo_description' => 'রফিকুল ইসলামের লেখক প্রোফাইল',
        ]);

        $this->assertSame('রফিকুল ইসলাম', $author->name_bn);
        $this->assertTrue($author->user->is($cmsUser));
        $this->assertTrue($author->featured);
        $this->assertSame(AuthorStatus::Active, $author->status);
        $this->assertDatabaseHas('authors', ['slug' => 'rafiqul-islam']);
    }

    public function test_author_slug_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Author::factory()->create(['slug' => 'duplicate-author']);

        $this->expectException(ValidationException::class);

        app(AuthorAdministrationService::class)->create($this->superAdmin(), [
            'name_bn' => 'ডুপ্লিকেট লেখক',
            'slug' => 'duplicate-author',
            'status' => AuthorStatus::Active->value,
            'featured' => false,
        ]);
    }

    public function test_author_safe_deletion_uses_soft_deletes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $author = Author::factory()->create();

        app(AuthorAdministrationService::class)->delete($this->superAdmin(), $author);

        $this->assertSoftDeleted('authors', ['id' => $author->getKey()]);
    }

    public function test_public_author_output_excludes_private_phone_and_cms_user(): void
    {
        $author = Author::factory()->create([
            'name_bn' => 'সাবিনা ইয়াসমিন',
            'slug' => 'sabina-yasmin',
            'phone' => '+8801700000000',
            'status' => AuthorStatus::Active,
        ]);

        $payload = PublicAuthorResource::make($author)->resolve();

        $this->assertArrayHasKey('name_bn', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
        $this->assertArrayNotHasKey('user', $payload);
    }

    public function test_public_author_profile_only_loads_active_authors(): void
    {
        Author::factory()->create([
            'name_bn' => 'প্রকাশিত লেখক',
            'slug' => 'public-author',
            'status' => AuthorStatus::Active,
        ]);
        Author::factory()->create([
            'name_bn' => 'অপ্রকাশিত লেখক',
            'slug' => 'inactive-author',
            'status' => AuthorStatus::Inactive,
        ]);

        $this->get('/authors/public-author')
            ->assertOk()
            ->assertSee('প্রকাশিত লেখক')
            ->assertDontSee('+880');

        $this->get('/authors/inactive-author')
            ->assertNotFound();
    }

    public function test_author_resource_uses_policy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $contributor = User::factory()->create();
        $contributor->assignRole(Rbac::CONTRIBUTOR);

        $admin = $this->superAdmin();

        $this->actingAs($contributor);
        $this->assertFalse(AuthorResource::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(AuthorResource::canAccess());
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Rbac::SUPER_ADMIN);

        return $admin;
    }
}
