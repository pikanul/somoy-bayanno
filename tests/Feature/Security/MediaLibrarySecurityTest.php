<?php

namespace Tests\Feature\Security;

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaLibraryService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MediaLibrarySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['media-library.disk' => 'public']);
    }

    public function test_user_with_media_upload_permission_can_store_real_png_with_safe_random_path(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $user = $this->userWithRole(Rbac::REPORTER);
        $file = $this->uploadedFile('story.php', $this->pngBytes(), 'image/png');

        $media = app(MediaLibraryService::class)->storeUploadedImage($user, $file, [
            'alt_text' => 'Flood water near a market',
            'caption' => 'Residents cross the road.',
            'credit' => 'Somoy Bayanno',
            'photographer' => 'Staff Photographer',
        ]);

        $this->assertSame('story.php', $media->original_name);
        $this->assertSame('image/png', $media->mime_type);
        $this->assertSame(1, $media->width);
        $this->assertSame(1, $media->height);
        $this->assertSame('Flood water near a market', $media->alt_text);
        $this->assertStringStartsWith('media/originals/', $media->path);
        $this->assertStringEndsWith('.png', $media->path);
        $this->assertStringNotContainsString('story.php', $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_rejects_spoofed_extension_and_invalid_image_signature(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->expectException(ValidationException::class);

        app(MediaLibraryService::class)->storeUploadedImage(
            $this->userWithRole(Rbac::REPORTER),
            $this->uploadedFile('fake.jpg', '<?php echo "not an image";', 'image/jpeg'),
        );
    }

    public function test_upload_rejects_svg_for_normal_users(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->expectException(ValidationException::class);

        app(MediaLibraryService::class)->storeUploadedImage(
            $this->userWithRole(Rbac::REPORTER),
            $this->uploadedFile('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml'),
        );
    }

    public function test_external_import_requires_upload_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(AuthorizationException::class);

        app(MediaLibraryService::class)->importExternalImage(
            $this->userWithRole(Rbac::CONTRIBUTOR),
            'https://93.184.216.34/image.png',
        );
    }

    public function test_external_import_blocks_local_and_private_network_targets_before_fetching(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Http::fake();

        $this->expectException(ValidationException::class);

        app(MediaLibraryService::class)->importExternalImage(
            $this->userWithRole(Rbac::REPORTER),
            'https://127.0.0.1/private.png',
        );

        Http::assertNothingSent();
    }

    public function test_external_import_downloads_https_public_image_after_content_type_and_signature_checks(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        Http::fake([
            '93.184.216.34/*' => Http::response($this->pngBytes(), 200, ['Content-Type' => 'image/png']),
        ]);

        $media = app(MediaLibraryService::class)->importExternalImage(
            $this->userWithRole(Rbac::REPORTER),
            'https://93.184.216.34/news/photo.png',
            ['caption' => 'Imported caption'],
        );

        $this->assertSame('https://93.184.216.34/news/photo.png', $media->external_url);
        $this->assertSame('Imported caption', $media->caption);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_policy_allows_metadata_update_for_uploader_only_without_edit_any_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = $this->userWithRole(Rbac::REPORTER);
        $otherReporter = $this->userWithRole(Rbac::REPORTER);
        $media = MediaAsset::factory()->create(['uploaded_by' => $owner->getKey()]);

        $this->assertTrue($owner->can('update', $media));
        $this->assertFalse($otherReporter->can('update', $media));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function uploadedFile(string $name, string $contents, string $mimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'media-test-');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }
}
