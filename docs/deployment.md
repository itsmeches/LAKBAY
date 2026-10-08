# Deployment and recovery

This configuration is supplied for review; no external deployment has been performed.

## Production prerequisites

CHED ICT selects hosting, domain, TLS reverse proxy, institutional SMTP and backup destination. DPO/records staff approve access, retention and legal holds. Confirm final policy/deadlines and the holiday calendar. Do not confirm policy merely to bypass the draft guard.

## Containers

1. Copy .env.production.example to .env.production and fill secrets and official settings.
2. Generate APP_KEY with PHP artisan key:generate --show in a controlled terminal; save it in the secret store and environment, never version control.
3. Run docker compose --env-file .env.production build.
4. Start db and run migrations/seeding using the app image.
5. Create initial administrator using lakbay:admin with its official email.
6. Initialize ClamAV database: docker compose --env-file .env.production run --rm --user root app freshclam. Schedule signature updates on the host. Scanner failures remain quarantined.
7. Start services. Configure approved HTTPS reverse proxy to localhost:8080, including forwarded-header trust scoped to that proxy.
8. Verify workers, scheduler, mail, MFA, invitation flow, private downloads, and monitoring. No database port is published.
9. Back up database/documents/key, restore into isolated staging, and complete CHED/HEI UAT.

The Nginx image receives the compiled public assets from the PHP build stage. No host public-directory mount is needed.

## Isolated PostgreSQL staging preparation (8 October 2026)

`compose.staging-db.yaml` prepares a database-only PostgreSQL 17 service for the existing workstation PHP application. It uses a separate `lakbay-staging` Compose project/volume, fixed database/user `lakbay_staging`, and loopback port 5433. The staging application uses port 8002; normal development and the existing SQLite QA server remain separate. Docker/PostgreSQL tooling is currently absent, so this configuration has **not** been run or validated by Docker.

Once CHED ICT or the workstation administrator provides working Docker Desktop and PHP `pdo_pgsql`:

1. Copy `.env.staging.example` to ignored `.env.staging`. Set a unique database password and the real installed scanner path/signature location; keep mail logging and draft-policy activation disabled. Do not copy production credentials or records.
2. In a dedicated PowerShell terminal, establish isolated storage **before every staging Artisan command**:

   ```powershell
   $stageStorageRoot = Join-Path (Get-Location).Path 'storage/app/staging'
   $env:LARAVEL_STORAGE_PATH = $stageStorageRoot
   if (Test-Path 'bootstrap/cache/config.php') {
       throw 'Use an uncached isolated checkout for staging; do not clear or reuse another running environment configuration.'
   }
   foreach ($directory in @('app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs')) {
       New-Item -ItemType Directory -Force -Path (Join-Path $stageStorageRoot $directory) | Out-Null
   }
   docker compose --env-file .env.staging -f compose.staging-db.yaml config --quiet
   docker compose --env-file .env.staging -f compose.staging-db.yaml up -d --wait
   php artisan config:show app.env --env=staging
   php artisan config:show database.default --env=staging
   php artisan config:show database.connections.pgsql.database --env=staging
   # Confirm staging / pgsql / lakbay_staging before continuing.
   php artisan key:generate --env=staging --no-interaction
   php artisan migrate --env=staging --no-interaction
   php artisan db:seed --env=staging --class=DatabaseSeeder --no-interaction
   php artisan serve --env=staging --host=127.0.0.1 --port=8002 --no-interaction
   ```

3. Verify the database/storage configuration points to staging before creating records. Use an independently approved initial administrator and invitations/MFA; do not use default passwords or copy the SQLite demo seeder into PostgreSQL. The demo seeders deliberately refuse non-SQLite databases. CMO 24 stays a candidate: official submissions remain blocked pending verified publication/effectivity. Candidate workflow tests belong in isolated automated tests, not a fabricated staging policy confirmation.
4. In another dedicated staging terminal, set the same `LARAVEL_STORAGE_PATH`; run `queue:work --env=staging --tries=3 --timeout=180` and `schedule:work --env=staging`. Verify genuine scanning and private access, with local mail only.
5. Before subsequent migrations, back up PostgreSQL with `pg_dump -Fc` and copy the matching private storage; preserve the staging APP_KEY separately. Restore into a **different disposable database**, then verify counts, document hashes, MFA and protected retrieval. Never restore over normal development, QA or the active staging database.
6. Record migration, workflow, concurrent review, queue/scanner and restore outcomes in the UAT guide. CI is configured to run the feature suite against PostgreSQL 17; the six SQLite-demo-specific tests are excluded only from that PostgreSQL job, leaving 375 eligible tests. This CI configuration has not been executed remotely during this follow-up. This service is preparation for local validation, not a complete production deployment.

Do not run `migrate:fresh`, `docker compose down -v`, or remove persistent volumes as part of routine setup. No infrastructure installation, container startup or external deployment was performed during this preparation.

## Backup/recovery

- Use encrypted backup destinations with limited service accounts.
- Take nightly pg_dump custom-format backups and enable host-managed PostgreSQL WAL archiving/PITR for an approved recovery point.
- Back up the private_documents volume alongside database backups. Do not back up quarantine material into an unclassified destination.
- Preserve APP_KEY and other required secrets separately; loss of APP_KEY makes encrypted MFA secrets unusable.
- On restore, place the app in maintenance, restore PostgreSQL and the matching private-document snapshot, restore keys, and verify hashes and protected access before opening service.
- Test restores quarterly. Initial proposed targets: one-hour RPO and one-business-day RTO; ICT must approve them.
- Review legal holds before deletion. Official-record deletion is disabled by default.
- Expired exports may be removed using an approved operational cleanup job; they are not official records.

## Operations

Monitor /up, HTTP error rate, queue lag/failures, scheduler execution, scanner signature age, SMTP failures, disk space and backup success. /up is liveness only, not a substitute for dependency monitoring.

Deploy with tests, static analysis, formatting checks, dependency audits and asset build. Back up before migrations. Prefer additive migrations; roll back application code only when its database compatibility is verified.

Containers/PostgreSQL and production SMTP/scanner were not executed in the local Windows environment. Validate them before rollout.
