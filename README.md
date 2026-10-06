# LAKBAY — CHED Region IV-A (CALABARZON)

Regional off-campus activity compliance and records software. HEIs prepare plans and signed evidence; CHED reviews submissions, requests corrections, records acknowledgments, monitors activities, and prepares regional reports.

**Status:** working development baseline, pending policy confirmation and production validation. CHED acknowledgment records receipt/completeness and **does not authorize an activity**.

## Interfaces and implemented scope

| Interface | Workflow |
| --- | --- |
| / | Public guidelines, FAQs, approved blank templates, and portal links |
| /hei | Prepare → Submit & respond → After the activity → Follow up |
| /ched | Review → Acknowledge → Monitor & support → Report |

Home-page stages provide guidance; they are not automatic per-activity completion checklists.

Implemented: invitations/MFA, institution-scoped access, academic periods, CLOCA calendars, activity revisions, private scanned documents, immutable submissions, corrections/resubmissions, evidence requests, signed acknowledgments, completion/incident/change reports, monitoring, assistance, corrective actions, restricted cases, external clearance/reconsideration records, approved public templates, audit history, regional snapshots, and queued CSV/PDF exports.

## Technology

PHP 8.4, Laravel 13, Filament 5, Livewire 4, Blade, Tailwind CSS 4, Vite 8, Node.js 24, Spatie Laravel Permission, and DomPDF. SQLite is used locally; PostgreSQL 17 is the production target. Private documents use ClamAV; database queues handle scanning, notifications, and exports.

One modular Laravel application serves both panels. Business transitions live in Actions; record authorization combines permissions, Policies, and institutional/regional checks. Lockfiles record exact dependency versions.

## Fresh clone setup

Install Git, PHP 8.4, Composer 2, and Node.js 24. PHP needs curl, fileinfo, mbstring, openssl, intl, GD, ZIP, and PDO SQLite; PostgreSQL also needs PDO PostgreSQL. These commands assume PHP, Composer, and npm are on PATH:

    git clone https://github.com/itsmeches/LAKBAY.git
    cd LAKBAY
    composer install --no-interaction --prefer-dist
    Copy-Item .env.example .env
    php artisan key:generate
    if (-not (Test-Path database/database.sqlite)) {
        New-Item -ItemType File database/database.sqlite | Out-Null
    }
    php artisan migrate --seed
    php artisan filament:assets
    npm ci --ignore-scripts
    npm run build
    php artisan lakbay:admin your-official-email@example.gov.ph
    php artisan serve --host=127.0.0.1

Open **http://127.0.0.1:8000**. The admin command prompts for a password without echoing it. There are no seeded default accounts/passwords. Sign in at /ched and enroll MFA.

These initialization commands are for a fresh clone. Do not overwrite an established .env, application key, or database. Generated Filament/Vite assets are excluded from Git; run filament:assets after dependency updates and npm run build after frontend changes.

### Existing Windows workstation

The PowerShell helper uses portable PHP under %LOCALAPPDATA%\lakbay-dev-tools\php when available, otherwise PHP on PATH:

    .\scripts\lakbay.ps1 serve
    .\scripts\lakbay.ps1 worker
    .\scripts\lakbay.ps1 admin your-official-email@example.gov.ph

Run server and worker in separate terminals. Portable tools, PHP settings, Windows scheduled tasks, accounts, and the database are machine setup and are **not included in a clone**.

## Accounts and onboarding

An administrator creates institutions/campuses and invites personnel. Invitation acceptance establishes the user's password; users then enroll MFA.

| Role | Main responsibility |
| --- | --- |
| HEI preparer | Prepare activity records and evidence |
| HEI authorized submitter | Submit packages, respond, and file reports |
| CHED records officer | Register/access permitted records and documents |
| CHED reviewer | Review submissions; request evidence/corrections |
| CHED signatory | Record signed acknowledgment of a complete revision |
| CHED monitoring officer | Monitoring, assistance, corrective follow-up |
| Restricted-case officer | Confidential cases, restrictions, external decisions |
| CHED analyst | Regional reports and exports |
| System administrator | Onboarding and bounded policy configuration |

Roles do not override ownership, verified institutional membership, jurisdiction, or confidentiality. Administrators do not automatically receive restricted-case access.

Local MAIL_MAILER=log writes invitations and workflow emails to storage/logs/laravel.log. Protect that log because it contains invitation links. Configure approved SMTP before real onboarding. Queued notifications and exports need a worker.

## Policy baseline

The seeded July 2026 policy is an **unconfirmed draft**. For local manual testing only, set LAKBAY_ALLOW_DRAFT_POLICY=true in .env and run:

    php artisan config:clear

Draft submissions are refused outside local/testing. Production requires a confirmed policy and authoritative holidays. Do not confirm a policy solely to pass a test. See [CHED clarification register](docs/CHED-clarifications.md).

## Documents, scanning, and previews

Uploads accept PDF, JPG, or PNG up to **20 MB**. Signed official evidence must be **PDF**; cryptographic signatures and notarization are not verified.

1. Upload and save: the private file is Pending.
2. A worker runs ClamAV and checks the stored hash.
3. Only Clean files allow authorized downloads and PNG/JPG previews.
4. Open the document's View page for its preview, Open image, and Download actions.

Missing scanners, failed scans, and changed files keep evidence quarantined; infected files remain blocked. Incident intake can accept missing supporting evidence.

Install [official ClamAV](https://www.clamav.net/downloads), configure FreshClam, and download its signatures. Set these values in .env using your actual paths:

    LAKBAY_SCANNER_COMMAND='C:\tools\ClamAV\clamscan.exe'
    LAKBAY_SCANNER_DATABASE_PATH='C:\tools\ClamAV\database'

The database setting is optional if ClamAV already has a valid default location. Keep signatures current with FreshClam.

    php artisan config:clear
    php artisan queue:restart
    php artisan queue:work --tries=3 --timeout=180

Restart a worker after queue:restart unless a supervisor manages it. Review failures with queue:failed; retry the relevant scan job after fixing its cause.

### Windows upload troubleshooting

If transfer reaches 100% but fails, inspect the failed Network request's Response. PHP needs writable absolute folders for **both** upload_tmp_dir and sys_temp_dir. Example php.ini settings:

    upload_tmp_dir="C:/tools/php/upload-tmp"
    sys_temp_dir="C:/tools/php/upload-tmp"
    upload_max_filesize=20M
    post_max_size=25M
    output_buffering=4096

Create the folder, allow the PHP process to write to it, and restart the server. These are PHP settings, not .env values. Livewire stream_get_meta_data() errors can indicate PHP tmpfile() cannot create a file.

On the original workstation, ClamAV and signature databases live outside the repository under lakbay-dev-tools; a local queue worker and daily signature-update task were configured. Other machines need their own setup, and the local worker must be restarted after reboot.

## First manual acceptance flow

Use dummy documents and separate browser profiles for each role.

1. Administrator creates an HEI and invites submitter, reviewer, and signatory accounts.
2. HEI prepares an academic period, calendar, and Category 1 activity with personnel/authorization.
3. HEI uploads the signed evidence required by the policy and waits for Clean status.
4. HEI submits the calendar; its reference and preserved snapshot appear.
5. Reviewer requests specific corrections.
6. HEI revises the plan/evidence and resubmits against the correction request.
7. Reviewer marks the new revision complete.
8. Signatory uploads/records the clean signed acknowledgment.
9. HEI downloads it; earlier revisions and review history remain intact.
10. Verify a second HEI cannot see or download the first HEI's records.

After conclusion, record actual completion and file applicable reports. HEI authorization, acknowledgment, execution status, and exceptional clearance remain separate.

## Validation

    php artisan test --compact
    php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
    php vendor/bin/pint --test
    composer audit
    npm audit
    npm run build

Windows helpers: .\scripts\lakbay.ps1 test and .\scripts\lakbay.ps1 check.

Local validation on **6 October 2026**: **111 tests, 412 assertions**, PHPStan and Pint passed. Tests use isolated fixtures and storage; they do not establish production readiness. GitHub Actions is configured for SQLite/PostgreSQL 17, dependency audits, formatting, and frontend build checks.

## Architecture and deployment

Actions: app/Actions. Access/compliance/reporting: app/Services. Models: app/Models. Authorization: app/Policies. Panels: app/Filament/Hei and Ched. Views: resources/views. Jobs: app/Jobs. Schema/policy defaults: database/migrations and seeders.

See [architecture](docs/architecture.md), [deployment and backups](docs/deployment.md), and [outstanding CHED decisions](docs/CHED-clarifications.md).

Production needs HTTPS, APP_DEBUG=false, PostgreSQL, approved mail/storage/hosting, supervised workers, Laravel scheduler, current signatures, backups, and tested restoration. Docker configuration is provided but has not been verified on this workstation.

## Known limits

- Internal HEI consultation, assessments, approvals, signing, and notarization happen externally; the app records evidence.
- Central Office/national consolidation, public complaint intake, student/guardian portals, spreadsheet import, full diagnostic/RACP editors, mobile clients, and digital signing are deferred.
- Incident reminders provisionally use 24 hours and changes 72 hours; CHED must resolve draft contradictions.
- Holidays, receipt rules, evidence applicability, retention, and final templates require confirmation.
- Student totals are estimated participations, not unique students.
- No automatic official-record purge is enabled.
- Browser/accessibility UAT, load tests, PostgreSQL concurrency, container execution, and recovery verification remain rollout gates.

## Repository hygiene

Commit source, migrations, tests, documentation, and lockfiles. Exclude .env, keys, invitation links, credentials, SQLite databases, uploaded evidence, exports, logs, backups, portable tools, and generated assets. Only sanitized environment examples belong in Git.