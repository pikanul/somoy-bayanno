<?php

namespace Tests\Feature\Security;

use App\Enums\GalleryStatus;
use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\GalleryAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_journalist_can_create_gallery_with_ordered_media_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $first = MediaAsset::factory()->create(['type' => 'image']);
        $second = MediaAsset::factory()->create(['type' => 'image']);

        $gallery = app(GalleryAdministrationService::class)->create($this->userWithRole(Rbac::PHOTO_JOURNALIST), [
            'title' => 'Monsoon gallery',
            'slug' => 'monsoon-gallery',
            'description' => 'Images from the field',
            'cover_image_id' => $first->getKey(),
            'photographer' => 'Staff Photographer',
            'status' => GalleryStatus::Draft->value,
            'items' => [
                ['media_id' => $second->getKey(), 'caption' => 'Second shown first', 'credit' => 'Desk', 'sort_order' => 99],
                ['media_id' => $first->getKey(), 'caption' => 'First shown second', 'credit' => 'Desk', 'sort_order' => 0],
            ],
        ]);

        $this->assertSame('Monsoon gallery', $gallery->title);
        $this->assertSame($first->getKey(), $gallery->cover_image_id);
        $this->assertSame([0, 1], $gallery->items->pluck('sort_order')->all());
        $this->assertSame([$second->getKey(), $first->getKey()], $gallery->items->pluck('media_id')->all());
    }

    public function test_reporter_without_gallery_create_permission_cannot_create_gallery(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(AuthorizationException::class);

        app(GalleryAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), [
            'title' => 'Denied gallery',
            'slug' => 'denied-gallery',
            'status' => GalleryStatus::Draft->value,
        ]);
    }

    public function test_gallery_rejects_duplicate_media_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $media = MediaAsset::factory()->create(['type' => 'image']);

        $this->expectException(ValidationException::class);

        app(GalleryAdministrationService::class)->create($this->userWithRole(Rbac::PHOTO_JOURNALIST), [
            'title' => 'Duplicate gallery',
            'slug' => 'duplicate-gallery',
            'status' => GalleryStatus::Draft->value,
            'items' => [
                ['media_id' => $media->getKey()],
                ['media_id' => $media->getKey()],
            ],
        ]);
    }

    public function test_reorder_items_only_accepts_existing_items_from_same_gallery(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->userWithRole(Rbac::PHOTO_JOURNALIST);
        $gallery = Gallery::factory()->create(['created_by' => $user->getKey()]);
        $otherGallery = Gallery::factory()->create(['created_by' => $user->getKey()]);
        $first = GalleryItem::factory()->create(['gallery_id' => $gallery->getKey(), 'sort_order' => 0]);
        $second = GalleryItem::factory()->create(['gallery_id' => $gallery->getKey(), 'sort_order' => 1]);
        $other = GalleryItem::factory()->create(['gallery_id' => $otherGallery->getKey(), 'sort_order' => 0]);

        $this->expectException(ValidationException::class);

        app(GalleryAdministrationService::class)->reorderItems($user, $gallery, [
            $second->getKey(),
            $other->getKey(),
            $first->getKey(),
        ]);
    }

    public function test_reorder_items_updates_sort_order_safely(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->userWithRole(Rbac::PHOTO_JOURNALIST);
        $gallery = Gallery::factory()->create(['created_by' => $user->getKey()]);
        $first = GalleryItem::factory()->create(['gallery_id' => $gallery->getKey(), 'sort_order' => 0]);
        $second = GalleryItem::factory()->create(['gallery_id' => $gallery->getKey(), 'sort_order' => 1]);

        $updated = app(GalleryAdministrationService::class)->reorderItems($user, $gallery, [
            $second->getKey(),
            $first->getKey(),
        ]);

        $this->assertSame([$second->getKey(), $first->getKey()], $updated->items->pluck('id')->all());
        $this->assertSame([0, 1], $updated->items->pluck('sort_order')->all());
    }

    public function test_gallery_text_fields_strip_html_to_reduce_stored_xss(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $media = MediaAsset::factory()->create(['type' => 'image']);

        $gallery = app(GalleryAdministrationService::class)->create($this->userWithRole(Rbac::PHOTO_JOURNALIST), [
            'title' => '<script>alert(1)</script>Safe title',
            'slug' => 'safe-title',
            'description' => '<b>Visible</b><script>alert(1)</script>',
            'photographer' => '<img src=x onerror=alert(1)>Name',
            'status' => GalleryStatus::Draft->value,
            'seo_title' => '<strong>SEO</strong>',
            'seo_description' => '<style>body{}</style>Summary',
            'items' => [
                ['media_id' => $media->getKey(), 'caption' => '<b>Caption</b>', 'credit' => '<script>alert(1)</script>Credit'],
            ],
        ]);

        $this->assertSame('Safe title', $gallery->title);
        $this->assertSame('Visible', $gallery->description);
        $this->assertSame('Name', $gallery->photographer);
        $this->assertSame('SEO', $gallery->seo_title);
        $this->assertSame('Summary', $gallery->seo_description);
        $this->assertSame('Caption', $gallery->items->first()->caption);
        $this->assertSame('Credit', $gallery->items->first()->credit);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
