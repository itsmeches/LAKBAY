# System architecture

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

## Purpose and boundaries

The system centralizes regional off-campus activity documentary preparation, intake, review, historical evidence and monitoring. HEI consultation, approval, signing and notarization remain external. OSDS and CEB are external organizations whose correspondence/decisions are recorded locally; they have no national panel or live service integration.

## Implemented structure

| Layer | Current implementation | Evidence |
|---|---|---|
| Public interface | Blade guidance, FAQ, portal links and controlled registered-template downloads | `routes/web.php`; `PublicTemplateController::{index,download}` |
| HEI workspace | Filament resources/custom pages, staged navigation, reactive forms | `HeiPanelProvider::panel`; `app/Filament/Hei`; `ScopedResource` |
| CHED workspace | Review, letters, monitoring, reporting, restricted cases and administration | `ChedPanelProvider::panel`; `app/Filament/Ched` |
| UI communication | Session/CSRF-protected HTTP and Livewire server updates; web print/download endpoints | panel providers; `routes/web.php` |
| Domain transitions | Shared Actions under Activities, Submissions, Reports, Documents, Identity, Clearances and Operations | `app/Actions`; `WorkflowUi::actions` |
| Rules | Versioned policy requirements, named predicates, deadlines and classification assessment | `RequirementResolver::{resolve,requirementCategory}`; `PolicyRules`; `ClassificationEvaluator` |
| Persistence | Eloquent relational records, immutable versions/snapshots and some bounded JSON payloads | `app/Models`; migrations; [data dictionary](05-database-and-data-flow.md) |
| Asynchronous work | Laravel jobs for scanning, notices and CSV/PDF exports | `ScanDocument`, `NotifyInstitution`, `NotifyChedChangeReport`, `GenerateExport` |
| Output | Protected Blade/DOMPDF forms; current drafts versus preserved submitted preparations | `app/Http/Controllers`; `app/Services/Reporting` |

This is a **modular monolith**: one Laravel application, one relational database per environment, two role-specific panels. Modules are organizational boundaries, not separately deployed microservices. Shared Actions reduce duplicated business rules between UI and controller entry points. Laravel transactions, locks and unique links protect selected transitions; production concurrency still requires PostgreSQL tests.

## Installed stack, not merely planned versions

The dependency inventory below is derived from `composer.lock`/`package-lock.json`; PHP was checked using the workstation executable. The backend runs PHP 8.4.26. Laravel 13, Filament 5, Livewire 4, Spatie permissions, DOMPDF, Blade/Tailwind and Vite are installed. PHPUnit, Larastan/PHPStan, Pint and Playwright are development tools. See [audit evidence](audit-evidence.md) for exact locked versions.

## Database and storage

Local demonstrated persistence uses SQLite. PostgreSQL 17 is represented in Compose/CI configuration but is not locally executed. Institutional ownership lives on records and verified memberships; CHED access is region-based. Eloquent models reference institutions, activity versions, submissions, documents, reports and operational records. Versioned JSON preserves printed form payloads and requirement parameters; it is not a substitute for all relational links.

Uploads use a private disk, explicit confidentiality and scan state. A queue worker invokes a real configured local scanner. Signed official evidence is PDF; ordinary evidence can include validated PNG/JPG. No student master or medical-history database is introduced. Incident persons belong to individual reports.

## External interactions

Configured mail transport sends invitations/operational notices; QA captures mail locally. ClamAV is a configured executable/signature database, not a verified hosted SaaS. OSDS/CEB routing is recorded correspondence, not an API call. S3 is an available filesystem configuration option, not evidence of an operating bucket. No public REST/mobile API is implemented: routes are web/Livewire endpoints; the `api/*` exception condition does not create an API.

## Deployment status

`Dockerfile`, Compose and `docs/deployment.md` describe a PHP/Nginx/database/worker/scheduler deployment and an isolated PostgreSQL staging option. They are **prepared configuration**, not a verified deployment. HTTPS/domain/proxy, institutional SMTP, scanner updates, monitoring, key management, retention and tested restore require ICT decisions and staging validation.

Daily reminders run at 08:00 Asia/Manila when the scheduler is actually installed; failed queue jobs are pruned after 168 hours. This prunes operational failures, not official evidence. No automatic official-record purge is enabled. Evidence: `routes/console.php`, `SendReminders::handle`, `docs/deployment.md`.

Output route protection includes authentication and an MFA-enrollment guard; that guard does not independently check challenge freshness. The normal challenge is supplied by Filament login. See the security qualification before making a broader authentication claim.

See [diagrams](03-system-diagrams.md). Avoid describing this design as proven large-scale infrastructure: capacity/load and production operations have not been measured.
