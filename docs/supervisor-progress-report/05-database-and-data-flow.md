# Database and information flow

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

## Relational structure

Institutions belong to regions and have campuses and academic periods. Users join institutions through `institution_user`, including verification and submitter authority. Activities belong to institutions and optionally calendars; immutable `activity_versions` hold the filed plan facts. Personnel, itinerary and classifications reference versions. A `submission_activities` join links a receipt to the exact versions; `submission_requirements` holds evidence/verdict/deadline snapshots for them. Submission corrections use a supersedes link.

Evidence files are represented by `documents`, document types and document links, with private path, hash, signature flag, confidentiality and scan state. Policy versions, requirements, templates and classification rule sets retain source/parameters. Submission/annex JSON captures the exact prepared data alongside referenced signed originals. A nullable legacy payload means unknown historical preparation, not permission to reconstruct it from current HEI data.

Report drafts and immutable filed completion/incident/change reports are distinct. Persons are scoped to an incident report, not a student master. Annex L consent generation is response-only for personal particulars; uploaded signed evidence remains separately protected.

Monitoring records have 14 items, findings/signatory data and corrective follow-up. Restricted cases have immutable events; clearance has exact activity-version references, recorded routing and reconsideration. Regional reports store frozen aggregate payloads; exports store output/status/expiry. Audit rows record transitions/access, not a legally tamper-proof independent ledger.

Evidence: `database/migrations/2026_10_06_000001_create_lakbay_tables.php` plus additive migrations; `app/Models`; `SubmitActivity::execute`; `SubmitReport::execute`; `CaseProcedure::history`; `RegionalReportBuilder::build`.

## Data movement

1. HEI input → validation → editable preparation / a new activity version.
2. Print preparation → external signing → private upload → quarantined document → genuine scan.
3. Authorized submission → requirement resolution/readiness → transaction → immutable receipt/version/evidence/annex snapshots → queued notice.
4. CHED review → findings/status history → correction revision or completeness record → separate signed acknowledgment.
5. Incident/ROC/RCD intake → preserved report evidence and timing parameters; late intake remains possible. CMO incidents use 48 hours, ROC uses actual conclusion +72 hours; formal RCD anchor is unresolved. Historical July calculations are preserved.
6. Monitoring/restricted external records → audit/procedural history → regional aggregation/queued export with access rechecks.

## History and privacy qualifications

Immutable model guards, locks and snapshot tests protect application transitions; this is not cryptographically sealed storage against database administrators. SHA-256 is checked during scan; generic retrieval currently lacks a fresh hash check (FIX-08). Records rely on organizational retention/legal-hold rules still awaiting confirmation. No automatic official purge or medical-history master is represented.

## Complete inspected schema dictionary

The following schema inventory is read-only introspection of the **migrated FIX-05 QA database**, not an assertion that the normal database has all columns. Column nullability/type/PK and declared foreign keys are generated from SQLite metadata. Infrastructure and permission tables are included. Source migrations remain authoritative for intended PostgreSQL structure; SQLite types alone do not prove cross-database equivalence.

### `academic_periods`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `label` | varchar | No | — |
| `starts_on` | date | No | — |
| `ends_on` | date | No | — |
| `term` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `institution_id` → `institutions.id` (delete: RESTRICT).

### `accountability_affidavits`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `academic_period_id` | INTEGER | No | — |
| `form_template_id` | INTEGER | Yes | — |
| `title` | varchar | No | — |
| `details` | TEXT | Yes | — |
| `revision` | INTEGER | No | — |
| `created_by` | INTEGER | No | — |
| `updated_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `updated_by` → `users.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT); `form_template_id` → `form_templates.id` (delete: RESTRICT); `academic_period_id` → `academic_periods.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `acknowledgment_drafts`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_id` | INTEGER | No | — |
| `prepared_by` | INTEGER | No | — |
| `revision` | INTEGER | No | — |
| `payload` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `prepared_by` → `users.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT).

### `acknowledgments`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_id` | INTEGER | No | — |
| `document_id` | INTEGER | No | — |
| `reference` | varchar | No | — |
| `recipient` | varchar | No | — |
| `signatory` | varchar | No | — |
| `letter_date` | date | No | — |
| `issued_by` | INTEGER | No | — |
| `summary` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `issued_by` → `users.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT).

### `activities`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `campus_id` | INTEGER | Yes | — |
| `calendar_id` | INTEGER | Yes | — |
| `academic_period_id` | INTEGER | No | — |
| `public_id` | varchar | No | — |
| `title` | varchar | No | — |
| `execution_status` | varchar | No | — |
| `actual_started_at` | datetime | Yes | — |
| `actual_ended_at` | datetime | Yes | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `academic_period_id` → `academic_periods.id` (delete: RESTRICT); `calendar_id` → `calendars.id` (delete: RESTRICT); `campus_id` → `campuses.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `activity_classifications`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_version_id` | INTEGER | No | — |
| `declared_category` | INTEGER | No | — |
| `requirement_category` | INTEGER | No | — |
| `reason` | TEXT | No | — |
| `confirmed_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `confirmed_by` → `users.id` (delete: RESTRICT); `activity_version_id` → `activity_versions.id` (delete: RESTRICT).

### `activity_personnel`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_version_id` | INTEGER | No | — |
| `role` | varchar | No | — |
| `name` | varchar | No | — |
| `position` | varchar | No | — |
| `contact` | varchar | No | — |
| `qualifications` | TEXT | Yes | — |
| `first_aid_trained` | tinyint(1) | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `hei_employed` | tinyint(1) | No | — |
| `accompanies_activity` | tinyint(1) | No | — |
| `first_aid_reference` | varchar | Yes | — |
| `basic_first_aid_knowledge` | tinyint(1) | Yes | — |

Declared foreign keys: `activity_version_id` → `activity_versions.id` (delete: RESTRICT).

### `activity_versions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_id` | INTEGER | No | — |
| `version` | INTEGER | No | — |
| `category` | INTEGER | No | — |
| `requirement_category` | INTEGER | No | — |
| `academic_nature` | varchar | No | — |
| `activity_type` | varchar | No | — |
| `initiator` | varchar | No | — |
| `title` | varchar | No | — |
| `description` | TEXT | No | — |
| `learning_objectives` | TEXT | No | — |
| `program` | varchar | Yes | — |
| `responsible_office` | varchar | No | — |
| `starts_at` | datetime | No | — |
| `ends_at` | datetime | No | — |
| `departure_at` | datetime | Yes | — |
| `return_at` | datetime | Yes | — |
| `venue` | varchar | No | — |
| `student_count` | INTEGER | No | — |
| `other_count` | INTEGER | No | — |
| `conditions` | TEXT | No | — |
| `authorization` | TEXT | No | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `contracts` | TEXT | Yes | — |
| `preparation` | TEXT | Yes | — |
| `cloca` | TEXT | Yes | — |
| `diagnostic` | TEXT | Yes | — |
| `racp` | TEXT | Yes | — |
| `annex_j` | TEXT | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT).

### `agency_coordinations`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_version_id` | INTEGER | No | — |
| `agency` | varchar | No | — |
| `type` | varchar | No | — |
| `contact` | varchar | Yes | — |
| `coordinated_on` | date | Yes | — |
| `assistance` | TEXT | Yes | — |
| `evidence_reference` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `activity_version_id` → `activity_versions.id` (delete: RESTRICT).

### `audit_events`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `actor_id` | INTEGER | Yes | — |
| `institution_id` | INTEGER | Yes | — |
| `action` | varchar | No | — |
| `record_type` | varchar | No | — |
| `record_id` | INTEGER | No | — |
| `correlation_id` | varchar | No | — |
| `metadata` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `institution_id` → `institutions.id` (delete: RESTRICT); `actor_id` → `users.id` (delete: RESTRICT).

### `cache`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `key` | varchar | No | Yes |
| `value` | TEXT | No | — |
| `expiration` | INTEGER | No | — |

### `cache_locks`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `key` | varchar | No | Yes |
| `owner` | varchar | No | — |
| `expiration` | INTEGER | No | — |

### `calendars`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `academic_period_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `effective_on` | date | Yes | — |
| `responsible_office` | varchar | No | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `annex_b` | TEXT | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `academic_period_id` → `academic_periods.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `campuses`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `name` | varchar | No | — |
| `address` | TEXT | Yes | — |
| `locality` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `institution_id` → `institutions.id` (delete: RESTRICT).

### `case_events`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `case_record_id` | INTEGER | No | — |
| `type` | varchar | No | — |
| `occurred_at` | datetime | No | — |
| `notes` | TEXT | No | — |
| `document_id` | INTEGER | Yes | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `procedure` | TEXT | Yes | — |
| `event_key` | varchar | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `case_record_id` → `case_records.id` (delete: RESTRICT).

### `case_records`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | Yes | — |
| `jurisdiction_region_id` | INTEGER | No | — |
| `reference` | varchar | No | — |
| `type` | varchar | No | — |
| `summary` | TEXT | No | — |
| `status` | varchar | No | — |
| `opened_at` | datetime | No | — |
| `closed_at` | datetime | Yes | — |
| `legal_hold` | tinyint(1) | No | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `source_document_id` | INTEGER | Yes | — |

Declared foreign keys: `source_document_id` → `documents.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `jurisdiction_region_id` → `regions.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT).

### `certification_drafts`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `academic_period_id` | INTEGER | No | — |
| `form_template_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `details` | TEXT | Yes | — |
| `revision` | INTEGER | No | — |
| `created_by` | INTEGER | No | — |
| `updated_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `updated_by` → `users.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT); `form_template_id` → `form_templates.id` (delete: RESTRICT); `academic_period_id` → `academic_periods.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `change_report_drafts`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `original_version_id` | INTEGER | No | — |
| `revised_version_id` | INTEGER | No | — |
| `original_submission_id` | INTEGER | Yes | — |
| `supplements_report_id` | INTEGER | Yes | — |
| `title` | varchar | No | — |
| `revision` | INTEGER | No | — |
| `context` | TEXT | No | — |
| `details` | TEXT | No | — |
| `created_by` | INTEGER | No | — |
| `updated_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `updated_by` → `users.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT); `supplements_report_id` → `change_reports.id` (delete: RESTRICT); `original_submission_id` → `submissions.id` (delete: RESTRICT); `revised_version_id` → `activity_versions.id` (delete: RESTRICT); `original_version_id` → `activity_versions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `change_report_items`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `change_report_id` | INTEGER | No | — |
| `field` | varchar | No | — |
| `previous_value` | TEXT | Yes | — |
| `revised_value` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `change_report_id` → `change_reports.id` (delete: RESTRICT).

### `change_reports`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `original_version_id` | INTEGER | No | — |
| `revised_version_id` | INTEGER | No | — |
| `changed_at` | datetime | No | — |
| `due_at` | datetime | Yes | — |
| `is_late` | tinyint(1) | Yes | — |
| `reason` | TEXT | No | — |
| `document_id` | INTEGER | Yes | — |
| `submitted_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `policy_version_id` | INTEGER | Yes | — |
| `holiday_calendar_version` | varchar | Yes | — |
| `deadline_parameters` | TEXT | Yes | — |
| `intake_kind` | varchar | No | — |
| `notified_at` | datetime | Yes | — |
| `original_submission_reference` | varchar | Yes | — |
| `change_report_draft_id` | INTEGER | Yes | — |
| `annex_i` | TEXT | Yes | — |
| `supplements_report_id` | INTEGER | Yes | — |

Declared foreign keys: `supplements_report_id` → `change_reports.id` (delete: RESTRICT); `change_report_draft_id` → `change_report_drafts.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `original_version_id` → `activity_versions.id` (delete: RESTRICT); `revised_version_id` → `activity_versions.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `submitted_by` → `users.id` (delete: RESTRICT).

### `classification_assessments`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_version_id` | INTEGER | No | — |
| `previous_assessment_id` | INTEGER | Yes | — |
| `baseline_version_id` | INTEGER | Yes | — |
| `baseline_submission_id` | INTEGER | Yes | — |
| `classification_rule_set_id` | INTEGER | No | — |
| `event_key` | varchar | No | — |
| `state` | varchar | No | — |
| `declared_category` | INTEGER | No | — |
| `recommended_category` | INTEGER | No | — |
| `confirmed_category` | INTEGER | Yes | — |
| `facts` | TEXT | No | — |
| `changes` | TEXT | No | — |
| `requirements_before` | TEXT | No | — |
| `requirements_after` | TEXT | No | — |
| `timing` | TEXT | No | — |
| `source_references` | TEXT | No | — |
| `reason` | TEXT | No | — |
| `recorded_by` | INTEGER | No | — |
| `confirmed_by` | INTEGER | Yes | — |
| `confirmed_at` | datetime | Yes | — |
| `occurred_at` | datetime | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `confirmed_by` → `users.id` (delete: RESTRICT); `recorded_by` → `users.id` (delete: RESTRICT); `classification_rule_set_id` → `classification_rule_sets.id` (delete: RESTRICT); `baseline_submission_id` → `submissions.id` (delete: RESTRICT); `baseline_version_id` → `activity_versions.id` (delete: RESTRICT); `previous_assessment_id` → `classification_assessments.id` (delete: RESTRICT); `activity_version_id` → `activity_versions.id` (delete: RESTRICT).

### `classification_rule_sets`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `policy_version_id` | INTEGER | No | — |
| `supersedes_id` | INTEGER | Yes | — |
| `code` | varchar | No | — |
| `status` | varchar | No | — |
| `rules` | TEXT | No | — |
| `source` | TEXT | No | — |
| `decision_source` | TEXT | No | — |
| `created_by` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `supersedes_id` → `classification_rule_sets.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT).

### `clearance_requests`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_version_id` | INTEGER | No | — |
| `case_record_id` | INTEGER | No | — |
| `justification` | TEXT | No | — |
| `status` | varchar | No | — |
| `endorsement_reference` | varchar | Yes | — |
| `osds_reference` | varchar | Yes | — |
| `decision` | varchar | Yes | — |
| `decided_at` | datetime | Yes | — |
| `decision_received_at` | datetime | Yes | — |
| `decision_document_id` | INTEGER | Yes | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `policy_version_id` | INTEGER | Yes | — |
| `application_received_at` | datetime | Yes | — |
| `application_due_at` | datetime | Yes | — |
| `application_is_late` | tinyint(1) | Yes | — |
| `application_document_id` | INTEGER | Yes | — |
| `deadline_parameters` | TEXT | Yes | — |
| `routing_history` | TEXT | Yes | — |
| `decision_receipt_reference` | varchar | Yes | — |

Declared foreign keys: `application_document_id` → `documents.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT); `activity_version_id` → `activity_versions.id` (delete: RESTRICT); `case_record_id` → `case_records.id` (delete: RESTRICT); `decision_document_id` → `documents.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT).

### `completion_report_drafts`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `activity_version_id` | INTEGER | No | — |
| `title` | varchar | No | — |
| `revision` | INTEGER | No | — |
| `context` | TEXT | No | — |
| `details` | TEXT | No | — |
| `created_by` | INTEGER | No | — |
| `updated_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `updated_by` → `users.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT); `activity_version_id` → `activity_versions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `completion_reports`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `submission_id` | INTEGER | Yes | — |
| `ended_at` | datetime | No | — |
| `due_at` | datetime | No | — |
| `is_late` | tinyint(1) | No | — |
| `venue` | varchar | No | — |
| `student_count` | INTEGER | No | — |
| `implementation` | varchar | No | — |
| `summary` | TEXT | No | — |
| `incidents_encountered` | tinyint(1) | No | — |
| `document_id` | INTEGER | No | — |
| `submitted_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `policy_version_id` | INTEGER | Yes | — |
| `holiday_calendar_version` | varchar | Yes | — |
| `deadline_parameters` | TEXT | Yes | — |
| `change_explanation` | TEXT | Yes | — |
| `incident_report_ids` | TEXT | Yes | — |
| `completion_report_draft_id` | INTEGER | Yes | — |
| `other_count` | INTEGER | Yes | — |
| `annex_g` | TEXT | Yes | — |

Declared foreign keys: `completion_report_draft_id` → `completion_report_drafts.id` (delete: RESTRICT); `submitted_by` → `users.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT).

### `corrective_actions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `monitoring_record_id` | INTEGER | Yes | — |
| `action` | TEXT | No | — |
| `responsible_party` | varchar | No | — |
| `due_on` | date | No | — |
| `status` | varchar | No | — |
| `response` | TEXT | Yes | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `monitoring_record_id` → `monitoring_records.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `document_links`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `document_id` | INTEGER | No | — |
| `record_type` | varchar | No | — |
| `record_id` | INTEGER | No | — |
| `purpose` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `document_id` → `documents.id` (delete: RESTRICT).

### `document_requests`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `submission_id` | INTEGER | No | — |
| `document_type_id` | INTEGER | No | — |
| `reason` | TEXT | No | — |
| `due_at` | datetime | No | — |
| `status` | varchar | No | — |
| `requested_by` | INTEGER | No | — |
| `response_document_id` | INTEGER | Yes | — |
| `responded_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `signature_mode` | varchar | No | — |
| `deadline_parameters` | TEXT | Yes | — |

Declared foreign keys: `response_document_id` → `documents.id` (delete: RESTRICT); `requested_by` → `users.id` (delete: RESTRICT); `document_type_id` → `document_types.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `document_types`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `code` | varchar | No | — |
| `name` | varchar | No | — |
| `confidentiality` | varchar | No | — |
| `annex` | varchar | Yes | — |
| `source_page` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

### `documents`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `document_type_id` | INTEGER | No | — |
| `public_id` | varchar | No | — |
| `disk` | varchar | No | — |
| `path` | varchar | No | — |
| `original_name` | varchar | No | — |
| `mime` | varchar | No | — |
| `size` | INTEGER | No | — |
| `sha256` | varchar | No | — |
| `confidentiality` | varchar | No | — |
| `scan_status` | varchar | No | — |
| `is_signed` | tinyint(1) | No | — |
| `metadata` | TEXT | Yes | — |
| `uploaded_by` | INTEGER | No | — |
| `supersedes_document_id` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `supersedes_document_id` → `documents.id` (delete: RESTRICT); `uploaded_by` → `users.id` (delete: RESTRICT); `document_type_id` → `document_types.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `exports`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `requested_by` | INTEGER | No | — |
| `filters` | TEXT | No | — |
| `status` | varchar | No | — |
| `path` | varchar | Yes | — |
| `expires_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `requested_by` → `users.id` (delete: RESTRICT).

### `failed_jobs`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `uuid` | varchar | No | — |
| `connection` | varchar | No | — |
| `queue` | varchar | No | — |
| `payload` | TEXT | No | — |
| `exception` | TEXT | No | — |
| `failed_at` | datetime | No | — |

### `form_templates`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `policy_version_id` | INTEGER | No | — |
| `code` | varchar | No | — |
| `title` | varchar | No | — |
| `version` | INTEGER | No | — |
| `annex` | varchar | No | — |
| `source_page` | INTEGER | No | — |
| `path` | varchar | Yes | — |
| `active_from` | date | Yes | — |
| `active_until` | date | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `document_id` | INTEGER | Yes | — |
| `is_public` | tinyint(1) | No | — |
| `metadata` | TEXT | Yes | — |

Declared foreign keys: `document_id` → `documents.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT).

### `holidaies`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `date` | date | No | — |
| `name` | varchar | No | — |
| `scope` | varchar | No | — |
| `locality` | varchar | Yes | — |
| `calendar_version` | varchar | No | — |
| `source_reference` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

### `incident_persons`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `incident_report_id` | INTEGER | No | — |
| `name` | varchar | No | — |
| `role` | varchar | No | — |
| `remarks` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `incident_report_id` → `incident_reports.id` (delete: RESTRICT).

### `incident_report_drafts`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `activity_version_id` | INTEGER | No | — |
| `supplements_report_id` | INTEGER | Yes | — |
| `title` | varchar | No | — |
| `revision` | INTEGER | No | — |
| `context` | TEXT | No | — |
| `details` | TEXT | No | — |
| `created_by` | INTEGER | No | — |
| `updated_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `updated_by` → `users.id` (delete: RESTRICT); `created_by` → `users.id` (delete: RESTRICT); `supplements_report_id` → `incident_reports.id` (delete: RESTRICT); `activity_version_id` → `activity_versions.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `incident_reports`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | No | — |
| `supplements_report_id` | INTEGER | Yes | — |
| `occurred_at` | datetime | No | — |
| `due_at` | datetime | No | — |
| `is_late` | tinyint(1) | No | — |
| `provisional_deadline` | tinyint(1) | No | — |
| `location` | varchar | No | — |
| `type` | varchar | No | — |
| `description` | TEXT | No | — |
| `immediate_actions` | TEXT | No | — |
| `current_status` | TEXT | No | — |
| `document_id` | INTEGER | Yes | — |
| `submitted_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `policy_version_id` | INTEGER | Yes | — |
| `holiday_calendar_version` | varchar | Yes | — |
| `deadline_parameters` | TEXT | Yes | — |
| `intake_kind` | varchar | No | — |
| `incident_report_draft_id` | INTEGER | Yes | — |
| `annex_h` | TEXT | Yes | — |

Declared foreign keys: `incident_report_draft_id` → `incident_report_drafts.id` (delete: RESTRICT); `submitted_by` → `users.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `supplements_report_id` → `incident_reports.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT).

### `institution_user`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `user_id` | INTEGER | No | — |
| `position` | varchar | No | — |
| `is_authorized` | tinyint(1) | No | — |
| `verified_at` | datetime | Yes | — |
| `verified_by` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `verified_by` → `users.id` (delete: RESTRICT); `user_id` → `users.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `institutional_restrictions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | Yes | — |
| `case_record_id` | INTEGER | Yes | — |
| `type` | varchar | No | — |
| `effective_from` | date | No | — |
| `effective_until` | date | Yes | — |
| `issuing_authority` | varchar | No | — |
| `document_id` | INTEGER | No | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `case_record_id` → `case_records.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `institutions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `region_id` | INTEGER | No | — |
| `ched_code` | varchar | No | — |
| `name` | varchar | No | — |
| `institution_type` | varchar | No | — |
| `address` | TEXT | Yes | — |
| `email` | varchar | Yes | — |
| `is_active` | tinyint(1) | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `region_id` → `regions.id` (delete: RESTRICT).

### `invitations`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `email` | varchar | No | — |
| `name` | varchar | No | — |
| `token_hash` | varchar | No | — |
| `institution_id` | INTEGER | Yes | — |
| `region_id` | INTEGER | Yes | — |
| `role` | varchar | No | — |
| `position` | varchar | No | — |
| `invited_by` | INTEGER | No | — |
| `expires_at` | datetime | No | — |
| `accepted_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `invited_by` → `users.id` (delete: RESTRICT); `region_id` → `regions.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `itinerary_items`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `activity_version_id` | INTEGER | No | — |
| `sequence` | INTEGER | No | — |
| `starts_at` | datetime | No | — |
| `description` | TEXT | No | — |
| `location` | varchar | No | — |
| `locality` | varchar | Yes | — |
| `dot_accreditation` | varchar | Yes | — |
| `responsible_person` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `activity_version_id` → `activity_versions.id` (delete: RESTRICT).

### `job_batches`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | varchar | No | Yes |
| `name` | varchar | No | — |
| `total_jobs` | INTEGER | No | — |
| `pending_jobs` | INTEGER | No | — |
| `failed_jobs` | INTEGER | No | — |
| `failed_job_ids` | TEXT | No | — |
| `options` | TEXT | Yes | — |
| `cancelled_at` | INTEGER | Yes | — |
| `created_at` | INTEGER | No | — |
| `finished_at` | INTEGER | Yes | — |

### `jobs`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `queue` | varchar | No | — |
| `payload` | TEXT | No | — |
| `attempts` | INTEGER | No | — |
| `reserved_at` | INTEGER | Yes | — |
| `available_at` | INTEGER | No | — |
| `created_at` | INTEGER | No | — |

### `migrations`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `migration` | varchar | No | — |
| `batch` | INTEGER | No | — |

### `model_has_permissions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `permission_id` | INTEGER | No | Yes |
| `model_type` | varchar | No | Yes |
| `model_id` | INTEGER | No | Yes |

Declared foreign keys: `permission_id` → `permissions.id` (delete: CASCADE).

### `model_has_roles`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `role_id` | INTEGER | No | Yes |
| `model_type` | varchar | No | Yes |
| `model_id` | INTEGER | No | Yes |

Declared foreign keys: `role_id` → `roles.id` (delete: CASCADE).

### `monitoring_items`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `monitoring_record_id` | INTEGER | No | — |
| `criterion_code` | varchar | No | — |
| `criterion` | TEXT | No | — |
| `compliance` | varchar | No | — |
| `verification` | TEXT | No | — |
| `remarks` | TEXT | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `monitoring_record_id` → `monitoring_records.id` (delete: RESTRICT).

### `monitoring_records`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | Yes | — |
| `period` | varchar | No | — |
| `monitored_on` | date | No | — |
| `type` | varchar | No | — |
| `respondent` | varchar | No | — |
| `overall_rating` | varchar | No | — |
| `findings` | TEXT | No | — |
| `good_practices` | TEXT | Yes | — |
| `suggestions` | TEXT | Yes | — |
| `monitored_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `criteria_version` | varchar | Yes | — |
| `annex_metadata` | TEXT | Yes | — |
| `annex_m` | TEXT | Yes | — |

Declared foreign keys: `monitored_by` → `users.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `notification_deliveries`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `user_id` | INTEGER | No | — |
| `event_key` | varchar | No | — |
| `channel` | varchar | No | — |
| `delivered_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `user_id` → `users.id` (delete: RESTRICT).

### `password_reset_tokens`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `email` | varchar | No | Yes |
| `token` | varchar | No | — |
| `created_at` | datetime | Yes | — |

### `permissions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `name` | varchar | No | — |
| `guard_name` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

### `policy_requirements`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `policy_version_id` | INTEGER | No | — |
| `requirement_definition_id` | INTEGER | No | — |
| `category` | INTEGER | No | — |
| `predicate` | varchar | No | — |
| `evidence_mode` | varchar | No | — |
| `deadline_type` | varchar | No | — |
| `deadline_anchor` | varchar | No | — |
| `deadline_amount` | INTEGER | No | — |
| `source_reference` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `section_row` | INTEGER | Yes | — |
| `stage` | varchar | No | — |
| `signature_mode` | varchar | No | — |
| `deadline_status` | varchar | No | — |
| `supporting_document_codes` | TEXT | Yes | — |

Declared foreign keys: `requirement_definition_id` → `requirement_definitions.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT).

### `policy_versions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `code` | varchar | No | — |
| `title` | varchar | No | — |
| `status` | varchar | No | — |
| `effective_on` | date | Yes | — |
| `publication_on` | date | Yes | — |
| `confirmed_by` | INTEGER | Yes | — |
| `confirmed_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `confirmation_reference` | varchar | Yes | — |
| `issued_on` | date | Yes | — |
| `source_sha256` | varchar | Yes | — |
| `publication_reference` | varchar | Yes | — |
| `parameters` | TEXT | Yes | — |

Declared foreign keys: `confirmed_by` → `users.id` (delete: RESTRICT).

### `reconsiderations`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `clearance_request_id` | INTEGER | No | — |
| `filed_at` | datetime | No | — |
| `due_at` | datetime | No | — |
| `is_late` | tinyint(1) | No | — |
| `grounds` | TEXT | No | — |
| `document_id` | INTEGER | No | — |
| `decision` | varchar | Yes | — |
| `decision_document_id` | INTEGER | Yes | — |
| `created_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `filing_status` | varchar | No | — |

Declared foreign keys: `created_by` → `users.id` (delete: RESTRICT); `decision_document_id` → `documents.id` (delete: RESTRICT); `document_id` → `documents.id` (delete: RESTRICT); `clearance_request_id` → `clearance_requests.id` (delete: RESTRICT).

### `regional_reports`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `region_id` | INTEGER | No | — |
| `period` | varchar | No | — |
| `cutoff_at` | datetime | No | — |
| `filters` | TEXT | No | — |
| `totals` | TEXT | No | — |
| `prepared_by` | INTEGER | No | — |
| `document_id` | INTEGER | Yes | — |
| `sent_to_osds_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `reporting_year` | INTEGER | Yes | — |
| `report_type` | varchar | No | — |
| `due_window` | varchar | Yes | — |
| `due_at` | datetime | Yes | — |
| `dispatch_reference` | varchar | Yes | — |
| `osds_received_at` | datetime | Yes | — |
| `receipt_reference` | varchar | Yes | — |

Declared foreign keys: `document_id` → `documents.id` (delete: RESTRICT); `prepared_by` → `users.id` (delete: RESTRICT); `region_id` → `regions.id` (delete: RESTRICT).

### `regions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `code` | varchar | No | — |
| `name` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

### `requirement_definitions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `code` | varchar | No | — |
| `title` | varchar | No | — |
| `document_type_id` | INTEGER | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `document_type_id` → `document_types.id` (delete: RESTRICT).

### `review_findings`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_review_id` | INTEGER | No | — |
| `deficiency` | TEXT | No | — |
| `requested_action` | TEXT | No | — |
| `resolved_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `submission_review_id` → `submission_reviews.id` (delete: RESTRICT).

### `role_has_permissions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `permission_id` | INTEGER | No | Yes |
| `role_id` | INTEGER | No | Yes |

Declared foreign keys: `role_id` → `roles.id` (delete: CASCADE); `permission_id` → `permissions.id` (delete: CASCADE).

### `roles`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `name` | varchar | No | — |
| `guard_name` | varchar | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

### `sessions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | varchar | No | Yes |
| `user_id` | INTEGER | Yes | — |
| `ip_address` | varchar | Yes | — |
| `user_agent` | TEXT | Yes | — |
| `payload` | TEXT | No | — |
| `last_activity` | INTEGER | No | — |

### `submission_activities`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_id` | INTEGER | No | — |
| `activity_version_id` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `activity_version_id` → `activity_versions.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT).

### `submission_requirements`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_id` | INTEGER | No | — |
| `policy_requirement_id` | INTEGER | No | — |
| `activity_version_id` | INTEGER | No | — |
| `evidence_status` | varchar | No | — |
| `reason` | TEXT | Yes | — |
| `document_ids` | TEXT | No | — |
| `due_at` | datetime | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `rule_snapshot` | TEXT | Yes | — |

Declared foreign keys: `activity_version_id` → `activity_versions.id` (delete: RESTRICT); `policy_requirement_id` → `policy_requirements.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT).

### `submission_reviews`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `submission_id` | INTEGER | No | — |
| `reviewed_by` | INTEGER | No | — |
| `outcome` | varchar | No | — |
| `remarks` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `reviewed_by` → `users.id` (delete: RESTRICT); `submission_id` → `submissions.id` (delete: RESTRICT).

### `submissions`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `calendar_id` | INTEGER | Yes | — |
| `policy_version_id` | INTEGER | No | — |
| `public_id` | varchar | No | — |
| `reference` | varchar | No | — |
| `type` | varchar | No | — |
| `status` | varchar | No | — |
| `revision` | INTEGER | No | — |
| `supersedes_submission_id` | INTEGER | Yes | — |
| `submitted_by` | INTEGER | No | — |
| `assigned_to` | INTEGER | Yes | — |
| `submitted_at` | datetime | No | — |
| `received_at` | datetime | No | — |
| `due_at` | datetime | Yes | — |
| `is_late` | tinyint(1) | No | — |
| `holiday_calendar_version` | varchar | No | — |
| `intake_channel` | varchar | No | — |
| `snapshot` | TEXT | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `stage` | varchar | No | — |

Declared foreign keys: `institution_id` → `institutions.id` (delete: RESTRICT); `calendar_id` → `calendars.id` (delete: RESTRICT); `policy_version_id` → `policy_versions.id` (delete: RESTRICT); `supersedes_submission_id` → `submissions.id` (delete: RESTRICT); `submitted_by` → `users.id` (delete: RESTRICT); `assigned_to` → `users.id` (delete: RESTRICT).

### `technical_assistance_logs`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `institution_id` | INTEGER | No | — |
| `activity_id` | INTEGER | Yes | — |
| `type` | varchar | No | — |
| `provided_on` | date | No | — |
| `summary` | TEXT | No | — |
| `follow_up` | TEXT | Yes | — |
| `provided_by` | INTEGER | No | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |

Declared foreign keys: `provided_by` → `users.id` (delete: RESTRICT); `activity_id` → `activities.id` (delete: RESTRICT); `institution_id` → `institutions.id` (delete: RESTRICT).

### `users`

| Column | SQLite type | Nullable | Primary key |
|---|---|---|---|
| `id` | INTEGER | No | Yes |
| `name` | varchar | No | — |
| `email` | varchar | No | — |
| `email_verified_at` | datetime | Yes | — |
| `password` | varchar | No | — |
| `remember_token` | varchar | Yes | — |
| `created_at` | datetime | Yes | — |
| `updated_at` | datetime | Yes | — |
| `region_id` | INTEGER | Yes | — |
| `is_active` | tinyint(1) | No | — |
| `app_authentication_secret` | TEXT | Yes | — |
| `app_authentication_recovery_codes` | TEXT | Yes | — |
