# Public Performance Cache

Phase 29 caches only anonymous public content. It must not be used for authorization decisions, user-specific admin pages, CSRF tokens, session data, previews, or private drafts.

## Keys

- `public:content:version`: monotonically increasing namespace version for public content.
- `public:v{version}:homepage`: homepage payload.
- `public:v{version}:article:{slug}`: published public article payload by slug.
- `public:v{version}:navigation`: public category navigation.
- `public:v{version}:breaking-news`: active breaking-news ticker items.
- `public:v{version}:filter-options`: public archive/search category and author filter lists.

The default TTL is controlled by `PUBLIC_CACHE_TTL_SECONDS` and is never less than 60 seconds.

## Invalidation

The file cache driver is supported locally, so the app does not rely on cache tags. Instead, observers bump `public:content:version` when public CMS records change.

Version-bumped models:

- `Article`: also forgets the current and original article slug key.
- `Category`: invalidates navigation, filters, homepage, and article/category lists.
- `BreakingNews`: invalidates ticker and homepage layout data.
- `HomepageSection` and `HomepageItem`: invalidate curated homepage data.

This intentionally avoids global caching for admin pages and authorization-sensitive data.

## Redis Readiness

Redis can be enabled later by setting `CACHE_STORE=redis` after local or environment Redis availability is verified. The key version strategy works with both file and Redis stores; no Redis-only cache tag behavior is required.
