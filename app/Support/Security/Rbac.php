<?php

namespace App\Support\Security;

final class Rbac
{
    public const SUPER_ADMIN = 'Super Admin';

    public const EDITOR_IN_CHIEF = 'Editor-in-Chief';

    public const EXECUTIVE_EDITOR = 'Executive Editor';

    public const NEWS_EDITOR = 'News Editor';

    public const SECTION_EDITOR = 'Section Editor';

    public const SUB_EDITOR = 'Sub Editor';

    public const REPORTER = 'Reporter';

    public const CONTRIBUTOR = 'Contributor';

    public const PHOTO_JOURNALIST = 'Photo Journalist';

    public const VIDEO_EDITOR = 'Video Editor';

    public const SEO_EDITOR = 'SEO Editor';

    public const ADVERTISEMENT_MANAGER = 'Advertisement Manager';

    public const SOCIAL_MEDIA_MANAGER = 'Social Media Manager';

    public const SUBSCRIBER_MANAGER = 'Subscriber Manager';

    public const IT_ADMINISTRATOR = 'IT Administrator';

    public const AUDITOR = 'Auditor';

    /** @return array<int, string> */
    public static function roles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::EDITOR_IN_CHIEF,
            self::EXECUTIVE_EDITOR,
            self::NEWS_EDITOR,
            self::SECTION_EDITOR,
            self::SUB_EDITOR,
            self::REPORTER,
            self::CONTRIBUTOR,
            self::PHOTO_JOURNALIST,
            self::VIDEO_EDITOR,
            self::SEO_EDITOR,
            self::ADVERTISEMENT_MANAGER,
            self::SOCIAL_MEDIA_MANAGER,
            self::SUBSCRIBER_MANAGER,
            self::IT_ADMINISTRATOR,
            self::AUDITOR,
        ];
    }

    /** @return array<int, string> */
    public static function permissions(): array
    {
        return array_values(array_unique(array_merge(
            self::contentPermissions('article', ['review', 'approve', 'publish', 'unpublish', 'archive', 'schedule', 'feature', 'restore-revision']),
            self::contentPermissions('category', ['reorder']),
            self::contentPermissions('tag', ['merge']),
            self::contentPermissions('topic', ['feature']),
            self::contentPermissions('author', ['verify']),
            self::contentPermissions('media', ['upload', 'edit-metadata', 'delete-any']),
            self::contentPermissions('video', ['encode', 'publish', 'unpublish']),
            self::contentPermissions('gallery', ['publish', 'unpublish']),
            [
                'breaking-news.view',
                'breaking-news.create',
                'breaking-news.update',
                'breaking-news.publish',
                'breaking-news.unpublish',
                'breaking-news.delete',
                'homepage.view',
                'homepage.manage',
                'advertisement.view',
                'advertisement.create',
                'advertisement.update',
                'advertisement.approve',
                'advertisement.publish',
                'advertisement.unpublish',
                'advertisement.delete',
                'comment.view',
                'comment.moderate',
                'comment.approve',
                'comment.reject',
                'comment.delete',
                'subscriber.view',
                'subscriber.create',
                'subscriber.update',
                'subscriber.disable',
                'subscriber.delete',
                'seo.view',
                'seo.manage',
                'menu.view',
                'menu.manage',
                'page.view',
                'page.create',
                'page.update',
                'page.publish',
                'page.unpublish',
                'page.delete',
                'user.view',
                'user.create',
                'user.update',
                'user.disable',
                'user.delete',
                'role.view',
                'role.manage',
                'permission.view',
                'permission.manage',
                'activity-log.view',
                'backup.view',
                'backup.manage',
                'settings.view',
                'settings.manage',
            ],
        )));
    }

    /** @return array<string, array<int, string>> */
    public static function rolePermissions(): array
    {
        return [
            self::SUPER_ADMIN => self::permissions(),
            self::EDITOR_IN_CHIEF => self::only([
                'article.*', 'category.*', 'tag.*', 'topic.*', 'author.*', 'media.view', 'media.upload',
                'video.view', 'gallery.view', 'breaking-news.*', 'homepage.*', 'comment.*', 'seo.*',
                'menu.view', 'page.*', 'user.view', 'role.view', 'permission.view', 'activity-log.view',
            ]),
            self::EXECUTIVE_EDITOR => self::only([
                'article.view', 'article.create', 'article.edit-own', 'article.edit-any', 'article.review',
                'article.approve', 'article.publish', 'article.unpublish', 'article.archive', 'article.schedule',
                'category.view', 'tag.*', 'topic.*', 'author.view', 'media.view', 'media.upload',
                'video.view', 'gallery.view', 'breaking-news.*', 'homepage.view', 'homepage.manage',
                'comment.*', 'seo.view', 'page.view', 'page.update',
            ]),
            self::NEWS_EDITOR => self::only([
                'article.view', 'article.create', 'article.edit-own', 'article.edit-any', 'article.review',
                'article.approve', 'article.publish', 'article.unpublish', 'article.archive',
                'category.view', 'tag.*', 'topic.view', 'topic.create', 'topic.edit-any',
                'author.view', 'media.view', 'media.upload', 'breaking-news.view', 'breaking-news.create',
                'breaking-news.update', 'comment.view', 'comment.moderate', 'seo.view',
            ]),
            self::SECTION_EDITOR => self::only([
                'article.view', 'article.create', 'article.edit-own', 'article.edit-any', 'article.review',
                'article.approve', 'article.archive', 'category.view', 'tag.view', 'tag.create',
                'topic.view', 'media.view', 'media.upload', 'comment.view', 'seo.view',
            ]),
            self::SUB_EDITOR => self::only([
                'article.view', 'article.create', 'article.edit-own', 'article.edit-any', 'article.review',
                'tag.view', 'topic.view', 'media.view', 'media.upload', 'seo.view',
            ]),
            self::REPORTER => self::only([
                'article.view', 'article.create', 'article.edit-own', 'media.view', 'media.upload',
                'tag.view', 'topic.view',
            ]),
            self::CONTRIBUTOR => self::only([
                'article.view', 'article.create', 'article.edit-own', 'media.view',
            ]),
            self::PHOTO_JOURNALIST => self::only([
                'media.view', 'media.upload', 'media.edit-metadata', 'gallery.view', 'gallery.create',
                'gallery.edit-own', 'gallery.edit-any', 'article.view',
            ]),
            self::VIDEO_EDITOR => self::only([
                'video.view', 'video.create', 'video.edit-own', 'video.edit-any', 'video.encode',
                'video.publish', 'video.unpublish', 'media.view', 'media.upload', 'article.view',
            ]),
            self::SEO_EDITOR => self::only([
                'article.view', 'article.edit-any', 'category.view', 'tag.view', 'topic.view',
                'author.view', 'seo.view', 'seo.manage', 'page.view', 'page.update',
            ]),
            self::ADVERTISEMENT_MANAGER => self::only([
                'advertisement.*', 'homepage.view',
            ]),
            self::SOCIAL_MEDIA_MANAGER => self::only([
                'article.view', 'breaking-news.view', 'homepage.view', 'media.view', 'video.view',
                'gallery.view', 'seo.view',
            ]),
            self::SUBSCRIBER_MANAGER => self::only([
                'subscriber.*', 'comment.view',
            ]),
            self::IT_ADMINISTRATOR => self::only([
                'user.*', 'role.view', 'permission.view', 'activity-log.view', 'backup.*', 'settings.view',
            ]),
            self::AUDITOR => self::readOnlyPermissions(),
        ];
    }

    /** @return array<int, string> */
    public static function panelRoles(): array
    {
        return self::roles();
    }

    /** @return array<int, string> */
    public static function privilegedTwoFactorRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::EDITOR_IN_CHIEF,
            self::IT_ADMINISTRATOR,
        ];
    }

    /** @param array<int, string> $extra */
    private static function contentPermissions(string $area, array $extra = []): array
    {
        return array_map(
            fn (string $action): string => "{$area}.{$action}",
            array_merge(['view', 'create', 'edit-own', 'edit-any', 'delete'], $extra),
        );
    }

    /** @param array<int, string> $patterns */
    private static function only(array $patterns): array
    {
        return array_values(array_filter(
            self::permissions(),
            fn (string $permission): bool => self::matchesAny($permission, $patterns),
        ));
    }

    private static function matchesAny(string $permission, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $permission) {
                return true;
            }

            if (str_ends_with($pattern, '*') && str_starts_with($permission, substr($pattern, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private static function readOnlyPermissions(): array
    {
        return array_values(array_filter(
            self::permissions(),
            fn (string $permission): bool => str_ends_with($permission, '.view'),
        ));
    }
}
