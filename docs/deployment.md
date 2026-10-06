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
