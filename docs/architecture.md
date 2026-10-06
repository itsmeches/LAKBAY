# Application architecture

LAKBAY is one modular Laravel application, with two Filament panels and a public Blade site. PostgreSQL is the production database. SQLite is the local development convenience.

## Code map

- app/Models: relational records, casts, relationships and immutability guards.
- app/Actions: explicit business operations grouped by Activities, Submissions, Documents, Reports, Operations, Identity and Clearances. These enforce permissions, ownership, transitions and evidence requirements independently of the interface.
- app/Services/Access.php and app/Policies: regional/institutional ownership and confidential access. There is no administrative superuser bypass.
- app/Services/Compliance: bounded predicates, category treatment, deadlines and recorded readiness.
- app/Filament/Hei and Ched: panel resources/pages. Support contains shared fields, resource definitions and workflow actions. Large calendars use paginated activities.
- app/Jobs: scanning, notification delivery and private exports. Jobs use record IDs and recheck access where applicable.
- app/Http: authorized private retrieval, invitations and MFA enrollment checks.
- database/migrations: foreign keys and unique constraints; seeders provide roles and an explicitly provisional draft policy.
- config/monitoring.php: the draft's 14 monitoring criteria.
- tests/Feature/LakbayWorkflowTest.php: domain, private access and Livewire regression checks.
- docs/CHED-clarifications.md: unresolved policy decisions.
- docs/deployment.md, Dockerfile and compose.yaml: deployment and recovery preparation.

## Integrity and authorization

Activity details create immutable versions. Submission freezes exact versions, evidence, document hashes and policy/holiday references before review. Corrections create new revisions. Acknowledgment references one revision and does not authorize implementation.

Database transactions and row locks protect state-changing actions; unique constraints protect acknowledgment/reconsideration limits. Model guards prevent application-level historical edits. Database administrators must use separate operational credentials; those guards cannot prevent direct SQL by privileged operators.

Private uploads are quarantined until ClamAV returns clean and their hash matches. Documents are streamed through authorized routes. No public storage symlink is required. Restricted-case access is separate from administration and ordinary regional review.

Working dates use versioned holiday records and Asia/Manila display. Receipt is application receipt time in the current implementation, pending CHED confirmation. Late incidents remain accepted. Report deadline parameters and references are recorded with intake.

Signed originals are uploaded externally; the system records them and their hashes without claiming cryptographic verification. No legal approval is inferred from a complaint, acknowledgment or technical assistance entry.

## Scope and validation limits

The evidence registry is intentionally smaller than a full student or internal HEI approval platform. It does not collect master student, guardian or medical histories. Public pages do not reveal protected records.

Local checks do not prove multi-process PostgreSQL concurrency, production email delivery, real scanner behavior, browser accessibility or approved policy interpretation. The CI database matrix and pilot UAT cover those follow-on environments. Do not treat this implementation as deployment approval.
