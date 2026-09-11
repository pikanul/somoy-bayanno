<?php

namespace Tests\Feature\Security;

use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoAdministrationService;
use App\Services\VideoProviderService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VideoManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_youtube_video_and_embed_url_is_generated_from_validated_id(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $video = app(VideoAdministrationService::class)->create($this->userWithRole(Rbac::VIDEO_EDITOR), [
            'title_bn' => 'শিরোনাম',
            'description_bn' => 'ভিডিও বিবরণ',
            'provider' => VideoProvider::YouTube->value,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => VideoStatus::Draft->value,
        ]);

        $this->assertSame(VideoProvider::YouTube, $video->provider);
        $this->assertSame('dQw4w9WgXcQ', $video->provider_video_id);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->embedUrl());
    }

    public function test_user_without_video_create_permission_cannot_create_video(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(AuthorizationException::class);

        app(VideoAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), [
            'title_bn' => 'শিরোনাম',
            'provider' => VideoProvider::YouTube->value,
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'status' => VideoStatus::Draft->value,
        ]);
    }

    public function test_javascript_urls_and_user_supplied_embed_html_are_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(VideoAdministrationService::class)->create($this->userWithRole(Rbac::VIDEO_EDITOR), [
            'title_bn' => 'শিরোনাম',
            'provider' => VideoProvider::YouTube->value,
            'video_url' => '<iframe src="javascript:alert(1)"></iframe>',
            'status' => VideoStatus::Draft->value,
        ]);
    }

    public function test_mismatched_provider_domain_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(VideoAdministrationService::class)->create($this->userWithRole(Rbac::VIDEO_EDITOR), [
            'title_bn' => 'শিরোনাম',
            'provider' => VideoProvider::YouTube->value,
            'video_url' => 'https://evil.example/watch?v=dQw4w9WgXcQ',
            'status' => VideoStatus::Draft->value,
        ]);
    }

    public function test_stored_xss_tags_are_removed_from_text_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $video = app(VideoAdministrationService::class)->create($this->userWithRole(Rbac::VIDEO_EDITOR), [
            'title_bn' => '<script>alert(1)</script>শিরোনাম',
            'description_bn' => '<img src=x onerror=alert(1)>নিরাপদ বিবরণ',
            'provider' => VideoProvider::Vimeo->value,
            'video_url' => 'https://vimeo.com/123456789',
            'status' => VideoStatus::Published->value,
            'published_at' => now(),
            'seo_title' => '<b>SEO</b>',
            'seo_description' => '<script>alert(1)</script>Summary',
        ]);

        $this->assertSame('শিরোনাম', $video->title_bn);
        $this->assertSame('নিরাপদ বিবরণ', $video->description_bn);
        $this->assertSame('SEO', $video->seo_title);
        $this->assertSame('Summary', $video->seo_description);
    }

    public function test_authorized_hls_requires_allowlisted_https_host_and_m3u8_path(): void
    {
        config(['video.authorized_hls_hosts' => ['stream.example.com']]);

        $payload = app(VideoProviderService::class)->validatedProviderPayload(
            VideoProvider::Hls->value,
            'https://stream.example.com/live/news.m3u8',
        );

        $this->assertSame(VideoProvider::Hls->value, $payload['provider']);
        $this->assertNull($payload['provider_video_id']);
    }

    public function test_unauthorized_hls_host_is_rejected(): void
    {
        config(['video.authorized_hls_hosts' => ['stream.example.com']]);

        $this->expectException(ValidationException::class);

        app(VideoProviderService::class)->validatedProviderPayload(
            VideoProvider::Hls->value,
            'https://internal.example.test/live/news.m3u8',
        );
    }

    public function test_authorized_cdn_mp4_requires_allowlisted_https_host_and_mp4_path(): void
    {
        config(['video.authorized_mp4_hosts' => ['cdn.example.com']]);

        $payload = app(VideoProviderService::class)->validatedProviderPayload(
            VideoProvider::CdnMp4->value,
            'https://cdn.example.com/videos/news.mp4',
        );

        $this->assertSame(VideoProvider::CdnMp4->value, $payload['provider']);
        $this->assertNull($payload['provider_video_id']);
    }

    public function test_owner_can_update_own_video_but_non_video_role_cannot_update_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = $this->userWithRole(Rbac::VIDEO_EDITOR);
        $reporter = $this->userWithRole(Rbac::REPORTER);
        $video = Video::factory()->create(['created_by' => $owner->getKey()]);

        $this->assertTrue($owner->can('update', $video));
        $this->assertFalse($reporter->can('update', $video));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
