# Phase 34 Release Candidate Validation

Date: 2026-09-11
Environment: local
Laravel: 13.31.0
PHP dependencies checked with Composer
JS dependencies checked with npm

## Summary

Overall result: FAIL

This release candidate is not production-ready because critical validation did not pass:

- Full test suite failed: 155 passed, 1 failed, 156 total.
- Backup health check failed: no backups are present on the configured `backup_local` disk.
- Local database migration status could not be checked against `.env` MySQL because the configured database user is empty and access was denied.
- Dedicated advertisement management/expiry implementation was not found; only placeholders, RBAC permissions, and logging helper coverage exist.

## Commands Run

| Command | Result | Notes |
| --- | --- | --- |
| `composer show --direct` | PASS | Package versions confirmed. |
| `php artisan route:list --except-vendor --no-interaction` | PASS | 48 application routes listed. |
| `php artisan test --compact` | FAIL | 156 tests, 155 passed, 1 failed. |
| `composer audit` | PASS | No security vulnerability advisories found. |
| `npm audit` | PASS | 0 vulnerabilities found. |
| `npm run build` | PASS | Vite production build completed; non-fatal optional `fontaine` warning. |
| `php artisan backup:list --no-interaction` | FAIL | Destination reachable but unhealthy; no backups present. |
| `php artisan backup:monitor --no-interaction` | FAIL | Non-zero exit; no backups present. |
| `php artisan migrate:status --no-interaction` | FAIL | MySQL access denied for empty configured DB username. |
| Log scan/tail under `storage/logs` | PASS | Recent sampled logs showed testing INFO entries only; no unexpected ERROR/CRITICAL stack traces found. |

## Full Test Suite Failure

Failing test:

`Tests\Feature\ActivityLoggingTest::test_activity_logger_sanitizes_backup_settings_and_advertisement_payloads`

Failure:

`Failed asserting that '' contains "backup.sql".`

Impact:

Activity log sanitization is expected to remove secrets while retaining useful non-sensitive audit context such as backup filenames and advertisement placements. The failing assertion means audit usefulness for backup/advertisement activity is not currently validated.

## Subsystem Results

| Subsystem | Result | Evidence |
| --- | --- | --- |
| Public homepage | PASS | `PublicFrontendFoundationTest`, homepage route, responsive shell test, Vite build. |
| Article pages | PASS | `PublicArticlePageTest`; non-public articles return not found. |
| Categories | PASS | `CategoryManagementTest`; hierarchy, uniqueness, safe deletion, authorization. |
| Search | PASS | `PublicSearchTest`; Unicode search, filters, validation, pagination, rate limit. |
| Archive | PASS | `PublicArchiveTest`; index, year/month/date browsing, filters, invalid dates. |
| Authors | PASS | `AuthorManagementTest`, `SeoFoundationTest`; public active author profile and SEO. |
| Videos | PASS | `VideoManagementSecurityTest`; provider validation, embed URL safety, XSS rejection. |
| Galleries | PASS | `GalleryManagementTest`; ordered media items, duplicate rejection, safe reordering. |
| Breaking news | PASS | `BreakingNewsManagementTest`; scheduling, expiry, status, priority, URL validation. |
| Advertisements | FAIL | No dedicated advertisement model/resource/table found; activity logging test covering advertisement payloads failed. |
| CMS login | PASS | `PrivilegedTwoFactorAuthenticationTest`, `UserAdministrationTest`; disabled users denied, 2FA paths covered. |
| Roles | PASS | `RbacAuthorizationTest`; role seeder idempotency and role gates covered. |
| Permissions | PASS | `RbacAuthorizationTest`; unauthorized users denied, auditor read-only, super admin full access. |
| Article workflow | PASS | `ArticleEditorialWorkflowTest`; submit, approve, schedule, publish, unpublish, archive, illegal transitions. |
| Article revisions | PASS | `ArticleRevisionHistoryTest`; revision snapshots, compare, restore, no public route. |
| Media uploads | PASS | `MediaLibrarySecurityTest`; real PNG storage, spoofed extension/signature rejection, SVG rejection. |
| External media URLs | PASS | `MediaLibrarySecurityTest`, `ArticleCmsWorkflowTest`; HTTPS/public URL and SSRF protections. |
| SEO | PASS | `SeoFoundationTest`; metadata, canonical URLs, structured data, robots. |
| Sitemaps | PASS | `SeoFoundationTest`; sitemap includes public canonical URLs and excludes scheduled/private content. |
| Activity logs | FAIL | Full suite failure in backup/advertisement sanitization context preservation. |
| Backup process | FAIL | Backup destination reachable but unhealthy; no backups present. |
| Security | FAIL | Most security tests pass, Composer/npm audits clean, but activity-log failure and backup health failure remain release blockers. |
| Mobile behavior | PASS | Responsive homepage shell covered by tests and production build passes. No browser-device screenshot run was performed. |
| Unauthorized requests | PASS | RBAC, resource policy, CMS, media, video, gallery, category, author, breaking news, and article tests cover denial paths. |
| Broken URLs | PASS | Invalid archive dates and non-public/broken article slugs return not found in public tests. |
| Scheduled article behavior | PASS | Scheduled/future articles excluded from public article pages and sitemap; workflow scheduling covered. |
| Expired advertisements | FAIL | Dedicated advertisement expiry implementation not found; only expired homepage managed items are covered. |
| Expired breaking news | PASS | Active breaking-news scope honors expired `ends_at` records. |
| Logs | PASS | No unexpected recent error-level log entries found in sampled local logs. |
| Dependency audit | PASS | `composer audit` and `npm audit` clean. |

## Release Decision

FAIL.

Do not call this release candidate production-ready until the failed test, backup health, local database readiness, and advertisement expiry gap are resolved or explicitly accepted as out of scope.
