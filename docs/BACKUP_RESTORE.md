# Backup and Restore Runbook

## Scope

Phase 28 uses `spatie/laravel-backup` for local and staging-ready backup foundations.

Backups include:

- Database dump for the configured `DB_CONNECTION`.
- Media files under `storage/app/private/media` and `storage/app/public/media` when those directories exist.
- Laravel application configuration files from `config/`.

Backups do not include:

- `.env` or secret-bearing environment values.
- `vendor/`, `node_modules/`, framework cache/session files, temporary backup files, or existing backups.
- Anything under `public/`.

Local backups are stored on the `backup_local` filesystem disk at:

```text
storage/app/private/backups
```

That location is intentionally outside the public web root and is not served by Laravel.

## Configuration

Local defaults are defined in `.env.example`:

```dotenv
BACKUP_LOCAL_DISK=backup_local
BACKUP_MAX_AGE_DAYS=7
BACKUP_MAX_STORAGE_MB=1024
BACKUP_ARCHIVE_ENCRYPTION=none
# BACKUP_ARCHIVE_PASSWORD=
```

For staging or production, keep the same application flow but move backup destinations to a private remote disk such as S3 or Cloudflare R2. Do not reuse public buckets, public object ACLs, or web-accessible filesystem paths.

## Backup

Run a backup:

```bash
php artisan backup:run --no-interaction
```

List backups and inspect status, timestamp, size, and storage location:

```bash
php artisan backup:list --no-interaction
```

Monitor backup health:

```bash
php artisan backup:monitor --no-interaction
```

Clean old backups according to retention settings:

```bash
php artisan backup:clean --no-interaction
```

Expected metadata for each backup is:

- Backup status: successful command exit and healthy monitor result.
- Timestamp: archive filename and `backup:list` date.
- Size: `backup:list` archive size.
- Storage location: configured disk plus `storage/app/private/backups` for local.
- Verification status: archive verification is enabled in `config/backup.php`; a backup command failure means the archive did not pass creation or verification.

## Verification

After every manual backup:

1. Confirm `php artisan backup:run --no-interaction` exits successfully.
2. Run `php artisan backup:list --no-interaction` and record the newest archive timestamp and size.
3. Run `php artisan backup:monitor --no-interaction` and confirm the backup is healthy.
4. Confirm the archive is not inside `public/` and is not reachable through a browser URL.
5. Confirm `.env` is absent from the archive before promoting this architecture beyond local development.

Optional local archive check:

```bash
unzip -l storage/app/private/backups/*/*.zip | grep -E '(^|/)\.env$'
```

The command should return no matches.

## Restore

There is intentionally no one-click production restore button in Phase 28.

Use this controlled restore process:

1. Put the target environment into maintenance mode.
2. Copy the selected backup archive to a restricted working directory outside `public/`.
3. Verify the archive name, timestamp, size, and source environment.
4. Extract the archive into a temporary restore directory.
5. Restore media files to their matching `storage/app/private/media` and `storage/app/public/media` paths.
6. Restore configuration files only after comparing them with the current deployed release.
7. Restore the database dump with the database-native tool for the configured driver.
8. Run migrations only if the restored data and current code require them.
9. Clear caches.
10. Smoke test admin login, public article pages, media URLs, search, and editorial workflows.
11. Disable maintenance mode.

Never test a restore against the active local, staging, or production database. Restore into a disposable database or isolated environment first.

## Rollback

Before any restore or risky data operation:

1. Create a fresh pre-restore backup.
2. Record its timestamp, size, storage disk, and verification result.
3. Keep the pre-restore backup separate from cleanup windows until the restore is accepted.

If rollback is needed:

1. Put the environment back into maintenance mode.
2. Restore the pre-restore backup using the same controlled restore process.
3. Re-run verification and smoke tests.
4. Record the final backup and rollback timestamps in the incident notes.
