# LAKBAY — CMO conformance fix backlog

Original read-only audit, 8 October 2026. Subsequent authorized work implements FIX-01, FIX-02 slices 1–3 and the scoped FIX-03 personnel readiness delivery (12 October). FIX-02/03 retain explicit CHED and production-verification questions. Other fixes remain proposed. The [final-CMO delta check](cmo-final-delta-check.md) updates findings only. Preserve current accounts, policy history, snapshots and signed originals. Publication remains unverified and CMO 24 must stay a candidate.

This accompanies [the report](CMO-conformance-report.md) and [traceability matrix](traceability-matrix.csv). Severity is an impact assessment. CHED decisions are not code defects; external procedural records do not imply transferring legal authority to software.

## Recommended sequence

1. Correct the independently verified source matrix (FIX-01).
2. Resolve applicable CHED interpretations while implementing classification/personnel and intake gaps (FIX-02–04).
3. Complete external procedural and regional reporting records (FIX-05–07).
4. Harden retrieval integrity (FIX-08), resolve remaining scope/feedback choices, then independently repeat isolated QA (QA-01).
5. Activate only after verified publication/reference/effectivity and authorized pilot sign-off.

## FIX-01 — Correct ordinary Category 3 SAA

**Status:** IMPLEMENTED ? gap #1 only, verified 8 October 2026. Full PHPUnit: 394/394, 1,920 assertions; current browser: 34/34; original 15 failures: individually pass after environment/test configuration correction. The pre-gap commit has two pre-existing QA defects. A production candidate-selection regression introduced by gap #1 was reproduced and fixed. Pint/PHPStan/PHP lint pass; history checksums and scratch restore pass. PostgreSQL remains blocked. See [verification report](gap1-verification-report.md) and [activation runbook](policy-activation-runbook.md). Other backlog items are unchanged.

Fresh seeding uses Categories 1–2. `lakbay:correct-saa` dry-run/`--apply` creates an idempotent candidate successor for existing incorrect baselines; new draft forms follow it and corrections retain their original policy. Annex F supports explained N/A with its SAA checkbox unchecked. Existing evidence can be reused for the HEI term under the pending working interpretation. No migration, historical update, deletion, re-evaluation or policy activation was performed.

Actual normal/QA application: each added 1 candidate policy, 64 rules and 13 template copies. Backup comparisons found zero existing rows changed/deleted across 60 ID-bearing tables per database. QA retained six existing CMO submissions; one unsubmitted ordinary Category 3 plan recomputes on opening. Backups are ignored under storage/app/gap1-backups/20261008-132544.

**Priority/type:** Major — confirmed contradiction.

**Traceability:** SEC14-R10-C3; SEC14-R10-T3.

**Finding:** Section 14 row 10, PDF p.9 marks Categories 1 and 2 only. The candidate seeder and category provider incorrectly include 3.

**Proposed task:** Correct a prospectively versioned candidate matrix and its source-derived tests. Remove the misleading Category 3 SAA assertions/guidance. Preserve historical snapshots; retain SAA when Category 3 receives Category 2 requirement treatment.

**Acceptance:** Ordinary Category 3 has no row-10 SAA blocker; escalated Category 3 does. Independently compare all 66 category cells against page images; demonstrate existing submissions unchanged.

**Evidence:** database/seeders/Cmo24PolicySeeder.php; tests/Feature/Cmo24AlignmentTest.php.

## FIX-02 — Validate classification facts and changed activity treatment

**Slice 3:** explicit HEI recommendation/authorized confirmation, immutable classification history, CHED submitted-snapshot action, selected Annex F consistency, receipt notices and regional export metadata are implemented. No activation, historical rewrite or throttle adjustment. See [Slice 3 report](gap2-slice3-report.md) for real verification results and remaining gates. Software interfaces are available for configured eligible rules; the overall regulatory verdict remains PARTIAL. Sources: CMO No. 24 §§12–13, PDF pp.5–7; §14, PDF pp.7–12; §16, PDF p.12; §24, PDF p.13; Annex F, PDF pp.25–26; Annex I, PDF p.29.

**Slice 2:** shared evaluator, SaveActivity assessment integration, confirmation/stale-state checks, policy-aware submission guard and immutable assessment snapshots implemented behind explicit context selection. Original calendar/planning facts, schedule/scope/design, configured materiality, sequential changes/reversions, Category 3 triggers, holiday lead boundaries and unresolved RCD timing have focused coverage. This was the Slice 2 boundary; the authorized Slice 3 interfaces are described above. Overall verdict remains PARTIAL. See [Slice 2 report](gap2-slice2-report.md). Source: CMO No. 24 §§12–13, PDF pp.5–7; §16, PDF p.12; §24, PDF p.13; Annex I, PDF p.29.

**Slice 1:** additive rule-set/assessment foundation implemented; defaults remain proposed data, not active classification behavior. Existing SaveActivity/resolver/UI behavior is unchanged. See [plan](gap2-implementation-plan.md), [source register](policy-source-register.md) and [Slice 1 report](gap2-slice1-report.md). Overall verdict remains PARTIAL until separately authorized slices 2–3 and their verification complete. Source: CMO No. 24 §§12–13, PDF pp.5–7; §16, PDF p.12; §24, PDF p.13; Annex I, PDF p.29.

**Priority/type:** Major — partial implementation.

**Traceability:** SEC12-03–05; SEC13-02–08; FLOW-HEI-01-B2.

**Finding:** Declared category and Category 3 escalation are supported, but original-calendar inclusion/pre-year planning and Category 1 revisions are incompletely checked.

**Proposed task:** Collect/reference original calendar and planning facts. Recommend the applicable treatment after schedule/scope/design changes; require an authorized confirmation and reason. Preserve declared category and revisions. Clarify exact treatment for each change with CHED.

**Acceptance:** Fixtures distinguish original annual activities, newly proposed activities, original external invitations and later modifications. Overrides and historical classifications remain auditable.

**Evidence:** app/Actions/Activities/SaveActivity.php; app/Services/Compliance/RequirementResolver.php.

## FIX-03 — Complete personnel qualification checks

**12 October 2026 delivery:** APIC employment, accompaniment, relevant qualifications/experience and basic first-aid knowledge now participate in shared CMO readiness and filing checks for Category 1/2 treatment, including escalated Category 3. Drafts remain saveable with missing particulars; the filing failure identifies the APIC and missing fields. APIC designation remains optional, APICs are excluded from PIC ratios, and no APIC training-proof upload is required. A nullable additive column stores knowledge without historical backfill; existing positive training declarations supply knowledge only when that new field is null. New versions/snapshots carry the declaration; submitted snapshots and verdicts remain unchanged. Sources: final CMO No. 24 §12.G/H, PDF pp.5–6; §14 row 12, PDF p.10; Annex F, PDF p.25. Scope for optional PIC/APIC in ordinary Category 3 remains a CHED question in `open-questions.md`; overall FIX-03 remains PARTIAL for that decision and production verification.

Focused regression coverage passed 27 tests/85 assertions. Full PHPUnit passed **476/476, 2,290 assertions**. Coverage includes each missing APIC attribute, all five escalation triggers, Categories 1–2, ordinary Category 3 supervisor-only staffing, PIC ratios, APIC knowledge without training proof, legacy positive training declarations without backfill, actual Filament form persistence, and immutable filed personnel/verdicts after later changes. No ordinary Category 3 PIC requirement, APIC ratio counting, policy activation, historical rewrite, authentication/throttle change or normal-database migration is included. The normal installation needs the new additive migration through the backed-up deployment process; only disposable test/QA copies were migrated.

**Executed verification, 12 October:** ignored artifacts are under `storage/app/fix03/`. Installed APIs: Laravel 13.34.0, Filament 5.9.0, PHP 8.4. Pint/PHPStan pass; PHP syntax checks pass for 383 files; JavaScript syntax and diff whitespace checks pass. The referenced testing-best-practices skill was not available in the installed skill directories; tests follow the existing PHPUnit/Livewire fixture conventions.

```text
php -c storage/app/verification-triage/current.ini vendor/bin/phpunit --log-junit storage/app/fix03/phpunit.xml
PHPUNIT_EXIT=0
{"tool":"phpunit","result":"passed","tests":476,"passed":476,"assertions":2290,"duration_ms":641843}
php -c storage/app/verification-triage/current.ini vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}
php -c storage/app/verification-triage/current.ini vendor/bin/phpstan analyse --no-progress --error-format=table
PHPSTAN_EXIT=0
{"tool":"phpstan","result":"passed","errors":0}
PHP_LINT_FILES=383 ERRORS=0
JS_SYNTAX_EXIT=0
```

The first full Chromium run passed **34/36 in 13.1m**, including the new APIC save/reopen/readiness scenario. Two existing reviewer scenarios failed: MFA navigation remained on login, and the correction-view body was incomplete. The latter has a logged `Maximum execution time of 30 seconds exceeded` in the compiled submission view during concurrent PHPUnit/browser work. After PHPUnit finished, both scenarios passed individually **2/2 in 23.9s** without changing application code, PHP timeouts, credentials, login helpers or throttle/MFA settings. The first MFA navigation failure's precise cause remains unproven; do not turn a successful retry into an authentication/load-readiness claim.

Final complete Chromium run on a fresh disposable copy, without concurrent PHPUnit work:

```text
node node_modules/@playwright/test/cli.js test
BROWSER_FINAL_EXIT=0
ok 36 ... APIC knowledge saves without training proof and missing knowledge appears in readiness (18.4s)
36 passed (6.9m)
node node_modules/vite/bin/vite.js build
BUILD_EXIT=0
✓ built in 15.29s
```

The build retains the existing optional fontaine warning; no dependency was added. Browser artifacts preserve both initial failures and subsequent runs. MFA/scanner isolation remains real; no forced-clean evidence or real-recipient mail is used. The final history comparison, `preservation-final.json`, compares every original row by ID and original columns, with matching hashes for immutable groups:

| Table | Before → after | Original changed / deleted |
|---|---|---|
| submissions | 6 → 6 | 0 / 0 |
| submission_requirements | 69 → 69 | 0 / 0 |
| acknowledgments | 2 → 2 | 0 / 0 |
| submission_reviews | 5 → 5 | 0 / 0 |
| activity_classifications | 19 → 23 | 0 / 0 |
| activity_personnel | 19 → 25 | 0 / 0 |
| certification_drafts | 2 → 2 | 1 / 0 |
| audit_events | 205 → 235 | 0 / 0 |
| policy_versions | 3 → 3 | 0 / 0 |

The certification change is the existing browser scenario's unsigned editable draft, not a filed certification. Activity versions/personnel and audit events are appended by the classification and APIC scenarios. Submitted snapshots/verdicts and old personnel values remain unchanged; the new column is null on original rows.

Migration dry run emits only a nullable `basic_first_aid_knowledge` column addition for this fix, with no UPDATE/backfill. In the disposable original QA copy: 19 existing personnel rows, zero APIC rows, zero original personnel values changed, zero backfilled values. Repeating `artisan migrate --no-interaction` after QA application reports `Nothing to migrate`. A read-only normal-database count found two personnel rows, zero APIC rows and no new column yet: one column is pending, zero rows need backfill. No normal migration ran. Exact count evidence is in `migration-preservation.json` and `normal-dry-run-counts.json`.

PostgreSQL runtime/concurrency and production recovery remain unverified: neither Docker nor psql is on PATH. Local scanner testing remains distinct from PHPUnit's simulated document rows. Normal deployment requires database/private-file backup before migration; the migration refuses destructive rollback. FIX-04 and all other backlog work remain untouched.

**Ordinary Category 3 PIC blocker fixed separately, 8 October 2026:** the save-time PIC guard now uses the resolved requirement category after classification/evaluation, matching `ActivityCompliance`. Ordinary Category 3 can be prepared/submitted with a supervising faculty member and no PIC; Categories 1–2 and all five escalated Category 3 triggers retain the PIC guard. Missing-PIC saves/revisions roll back atomically. No policy rows, historical records, APIC proof rules or Slice 3 UI changes were modified. Tests: `Cmo24AlignmentTest::test_ordinary_category_three_can_be_prepared_and_submitted_with_supervisor_only`, `test_applicable_pic_requirement_cannot_be_replaced_by_a_supervisor` (seven cases), `ClassificationEvaluationTest::test_confirmed_ordinary_category_three_can_use_supervisor_without_pic`. SEC14-R12-C3 is now IMPLEMENTED for designation applicability. Remaining personnel qualification questions in FIX-03 stay open; see [verification](ordinary-category3-pic-fix.md).

**Priority/type:** Major — partial implementation.

**Traceability:** SEC12-07–14.

**Historical finding before the separate PIC fix:** PIC qualification checks operated for requirement categories 1/2, but the then-current `SaveActivity` required PIC designation for ordinary Category 3. The separate fix above resolved that contradiction; the 12 October delivery adds APIC readiness checks. Section 14 row 12, PDF p.10 marks only Categories 1–2; ordinary Category 3 remains supervisor-only capable. Optional named PIC/APIC qualification scope remains a CHED question rather than a silently expanded blocker.

**Proposed task:** Remove the ordinary Category 3 universal-PIC blocker through an independently authorized change and add supervisor-only preparation coverage; retain applicable PIC requirements for Categories 1–2/escalated Category 3. Validate designated personnel employment, accompaniment and relevant experience where applicable. Section 12.H, PDF pp.5–6 requires APIC basic first-aid knowledge, but Annex F PDF p.25 names training proof only for PIC. Do not add mandatory APIC training-proof uploads without CHED confirmation. Keep APIC out of the PIC ratio unless CHED authorizes counting.

**Acceptance:** Missing applicable personnel particulars produce clear field/readiness messages. Existing 30/50 ratio boundaries pass. No student medical-history fields are added.

**Evidence:** app/Services/Compliance/ActivityCompliance.php; app/Actions/Activities/SaveActivity.php.

## FIX-04 — Track deficiencies before conduct and notify change intake

**Priority/type:** Major — partial implementation.

**Traceability:** SEC16-01; SEC19-02; SEC21-01.

**Finding:** Correction notices exist, but a pre-conduct review queue/escalation anchor is missing. Immediate change intake lacks a dedicated CHED notification job.

**Proposed task:** Expose next activity start in the review queue and overdue pre-conduct advice. Add idempotent, authorized CHED change-notice notifications. Do not invent a legal review interval.

**Acceptance:** An impending activity with unreviewed deficiencies is visible; correction/change notices reach only authorized local-test recipients once. Late reporting remains accepted.

**Evidence:** app/Actions/Submissions/ReviewSubmission.php; app/Notifications/WorkflowNotice.php.

## FIX-05 — Record complaint/investigation/decision notice history

**Priority/type:** Major — partial procedural history.

**Traceability:** SEC34-03–06.

**Finding:** Events can be recorded without complete procedural history; activity-location referral and written notice to the HEI are not explicitly tracked.

**Proposed task:** Record referral jurisdiction, fact-finding, committee recommendation, external decision authority, signed order and HEI dispatch/receipt. Flag incomplete history; allow documented historical external decisions without inventing events.

**Acceptance:** A complaint cannot create suspension. A consequential decision identifies its authoritative evidence and missing prerequisites/history. Restricted permissions apply to each step and attachment.

**Evidence:** app/Actions/Operations/RecordCaseEvent.php; app/Actions/Operations/RecordOperationalRecord.php.

## FIX-06 — Complete exceptional-clearance routing

**Priority/type:** Major — partial procedural history.

**Traceability:** SEC27-01–09.

**Finding:** Application timing and routing/decisions exist; routing completeness and OSDS complete-staff-work evidence are not enforced as a complete recorded chain.

**Proposed task:** Add an external-stage completeness checklist and explicit staff-work evidence reference. Record missing historical routing as unknown. Confirm whether the 90-day period means calendar days; distinguish late receipt from acceptance.

**Acceptance:** Exact activity-version grant/denial, signed decision, application receipt, endorsement, OSDS work, CEB routing and one receipt-based reconsideration can be traced. No receipt authorizes conduct.

**Evidence:** app/Actions/Operations/RecordClearanceRouting.php; app/Actions/Clearances/RecordClearanceDecision.php.

## FIX-07 — Include findings/recommendations in annual regional reporting

**Priority/type:** Major — partial output.

**Traceability:** SEC33-08; SEC38-01; FLOW-RO-06-B1; FLOW-ARROW-15–16.

**Finding:** Regional snapshots retain criteria/rating but omit monitoring narrative findings and recommendations. Dispatch evidence uses an institution-owned monitoring document instead of a distinct regional-report association.

**Proposed task:** Freeze narrative results/recommendations with the annual report; provide an appropriately scoped signed regional-report document association. Validate historical-cutoff status interpretation and reporting period.

**Acceptance:** Two institutions with distinct findings/recommendations remain distinguishable in a frozen export. Later edits do not change it. Dispatch and OSDS receipt identify the actual regional report.

**Evidence:** app/Services/Reporting/RegionalReportBuilder.php; app/Actions/Operations/ManageRegionalReport.php.

## FIX-08 — Verify original bytes at private retrieval

**Priority/type:** Major — supporting security hardening.

**Traceability:** AUD-SEC-01.

**Finding:** Preview/download checks authorization and clean scan state but not the current file hash. This is a static hardening finding, not a demonstrated exploit or an explicit CMO technical mandate.

**Proposed task:** Verify stored hash before serving private evidence or otherwise enforce equivalent immutable storage. Fail closed on changed/missing bytes and log an integrity failure without revealing the file.

**Acceptance:** An isolated regression replaces previously scanned bytes and proves the changed file cannot be served; normal authorized files work. Do not tamper with existing records/uploads.

**Evidence:** app/Http/Controllers/DocumentController.php; app/Jobs/ScanDocument.php.

## DEC-01 — Confirm publication and remaining operational interpretations

**Priority/type:** Blocker — CHED/source decision.

**Traceability:** SEC41-01; SEC40-03; SEC20-01; SEC24-01; SEC13-10; SEC14-R01-T3; SEC14-R02-T3; SEC14-R06-T3; SEC14-R08-COND; SEC14-R09-COND; SEC14-R12-DOC; SEC14-R22-T1–T3; ANX-K-PERIOD; ANX-K-STUDENTS.

**Finding:** Publication/reference remain absent. Category 3 timelines, SAA semester coverage, acknowledgment sequence, RCD anchor, LGU/DOT applicability, first-aid upload, transition handling and student-count semantics need authoritative decisions.

**Proposed task:** Obtain written CHED answers and verified publication reference. Keep candidate unactivated; retain null unresolved deadlines and distinguish electronic receipt from signed completeness acknowledgment.

**Acceptance:** No guessed publication/deadline is introduced. Approved interpretations carry source/effective-version references and independent regression tests.

**Evidence:** docs/CHED-clarifications.md; app/Services/Compliance/PolicyRules.php.

## QA-01 — Finish independently captured environment and browser verification

**Priority/type:** Major — verification limitation.

**Traceability:** All SOFTWARE rows; particularly IMPLEMENTED entries.

**Finding:** Existing automated checks support implementation but cannot establish legal correctness or production behavior. Fresh browser capture covers seven workspaces; complete lifecycle/PDF interaction was not repeated read-only.

**Proposed task:** In a separately authorized disposable QA run, retest the corrected source matrix, every Annex B–M form/print, correction/acknowledgment lifecycle, queue/scanner failures and recovery. Run isolated PostgreSQL concurrency/migrations/restore when tooling exists.

**Acceptance:** Save exact command totals/artifact timestamps and separate fresh from prior results. Confirm no real mail, bypassed MFA, forced-clean files or activated candidate policy.

**Evidence:** tests/Feature; tests/Browser; docs/UAT-guide.md.

## FIX-09 — Expose monitoring participation and feedback handoff

**Priority/type:** Minor — scope/UX support.

**Traceability:** FLOW-ARROW-14; FLOW-ARROW-15.

**Finding:** HEI corrective-action responses exist; a general monitoring-participation/feedback handoff is not explicit.

**Proposed task:** Decide whether corrective-response evidence is sufficient. If not, add a minimal authorized response linked to the monitoring record and regional feedback summary.

**Acceptance:** An HEI can provide the requested response without viewing confidential CHED cases or another institution's monitoring records.

**Evidence:** app/Actions/Operations/ManageCorrectiveAction.php.

## DEC-02 — Domestic scope and generated Annex K package coverage

**Priority/type:** Minor — scope confirmation.

**Traceability:** SEC09-03; SEC20-01.

**Finding:** Venue is free text; domestic scope is not explicitly validated. Generated Annex K requires a calendar-type submission although Category 2 CLOCA applicability exists.

**Proposed task:** Confirm domestic-scope validation and how supplementary Category 2 CLOCA packages receive Annex K. Keep externally uploaded signed correspondence supported while unresolved.

**Acceptance:** Foreign activities are clearly routed to applicable separate guidance; the approved CLOCA acknowledgment package can generate its correct signed-letter preparation.

**Evidence:** app/Actions/Activities/SaveActivity.php; app/Services/Reporting/AcknowledgmentBuilder.php.

## Delta review additions — proposed only

8 October 2026, [read-only final-CMO check](cmo-final-delta-check.md). These do not authorize application changes or Slice 3.

- **FIX-10 — Annex K exact-output adaptation (Minor / PARTIAL):** Annex K PDF p.31 fixed paragraphs match, but the printed app adds a Reference line and adapts layout. Confirm acceptance of additions or prepare an exact-template option prospectively. Traceability `AUD-DELTA-K-EXACT`; evidence `acknowledgment.blade.php`, `AcknowledgmentTest`. Do not relabel historical signed letters or delete references from records.
- **FIX-11 — Verify both Annex N reference pages (Major / PARTIAL):** Annex N PDF pp.35–36 metadata exists, but no dedicated two-page output test or verified registered public download was established. Confirm the approved reference-file publication and preserve both pages; do not create an extra data-entry module. Order reconciliation remains a CHED decision recorded in open-questions. Traceability `AUD-DELTA-N-REFERENCE`; do not silently enforce one conflicting diagram.
- **FIX-06 extension — Clearance lead coordination (Major / PARTIAL):** §27 PDF pp.13–14 and §14 PDF pp.7–12 require distinct timelines. Record/clarify which complete evidence accompanies the 90-day application and how later pre-activity updates are handled; the three-working-day rule never waives the 90-day application. Calendar day interpretation remains provisional. Traceability `AUD-DELTA-CLEARANCE-LEADS`. Existing due-date/routing tests pass; no integrated coordination test added by this read-only review.
- **FIX-05 naming clarification (PARTIAL):** §34.D PDF p.16 names the CHED Committee. Current UI says generic Committee recommendation, and no Committee on Student Well-being hardcoding was found. Use the authoritative body name when that separately authorized workflow is completed; do not infer a new legal decision-maker.

## Complete unresolved-entry assignment

The table assigns every PARTIAL, MISSING, CONTRADICTS and NEEDS CHED DECISION entry (74) to a task. Grouped flow summaries can share one underlying issue; do not count them as separate defects. QA-01 includes confirmation of manual handoffs/representation where automatic enforcement is inappropriate.

| Requirement ID | Current verdict | Owner task |
|---|---|---|
| SEC09-03 | PARTIAL | DEC-02 |
| SEC12-03 | PARTIAL | FIX-02 |
| SEC12-04 | PARTIAL | FIX-02 |
| SEC12-05 | PARTIAL | FIX-02 |
| SEC12-07 | PARTIAL | FIX-03 |
| SEC12-08 | PARTIAL | FIX-03 |
| SEC12-09 | PARTIAL | FIX-03 |
| SEC12-10 | PARTIAL | FIX-03 |
| SEC12-11 | PARTIAL | FIX-03 |
| SEC12-12 | PARTIAL | FIX-03 |
| SEC12-13 | PARTIAL | FIX-03 |
| SEC12-14 | PARTIAL | FIX-03 |
| SEC13-02 | PARTIAL | FIX-02 |
| SEC13-03 | PARTIAL | FIX-02 |
| SEC13-05 | PARTIAL | FIX-02 |
| SEC13-06 | PARTIAL | FIX-02 |
| SEC13-07 | PARTIAL | FIX-02 |
| SEC13-08 | PARTIAL | FIX-02 |
| SEC13-10 | NEEDS CHED DECISION | DEC-01 |
| SEC16-01 | PARTIAL | FIX-04 |
| SEC19-02 | PARTIAL | FIX-04 |
| SEC20-01 | NEEDS CHED DECISION | DEC-01 |
| SEC21-01 | PARTIAL | FIX-04 |
| SEC24-01 | NEEDS CHED DECISION | DEC-01 |
| SEC27-01 | PARTIAL | FIX-06 |
| SEC27-02 | NEEDS CHED DECISION | DEC-01 |
| SEC27-06 | PARTIAL | FIX-06 |
| SEC27-07 | PARTIAL | FIX-06 |
| SEC33-08 | PARTIAL | FIX-07 |
| SEC34-03 | PARTIAL | FIX-05 |
| SEC34-04 | PARTIAL | FIX-05 |
| SEC34-05 | PARTIAL | FIX-05 |
| SEC34-06 | MISSING | FIX-05 |
| SEC35-03 | PARTIAL | FIX-05 |
| SEC38-01 | PARTIAL | FIX-07 |
| SEC40-03 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R01-T3 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R02-T3 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R06-T3 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R08-COND | NEEDS CHED DECISION | DEC-01 |
| SEC14-R09-COND | NEEDS CHED DECISION | DEC-01 |
| SEC14-R10-C3 | IMPLEMENTED | FIX-01 — completed |
| SEC14-R10-T3 | IMPLEMENTED | FIX-01 — completed |
| SEC14-R12-DOC | NEEDS CHED DECISION | DEC-01 |
| SEC14-R22-T1 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R22-T2 | NEEDS CHED DECISION | DEC-01 |
| SEC14-R22-T3 | NEEDS CHED DECISION | DEC-01 |
| ANX-K-PERIOD | NEEDS CHED DECISION | DEC-01 |
| ANX-K-STUDENTS | NEEDS CHED DECISION | DEC-01 |
| FLOW-HEI-01 | PARTIAL | FIX-02 |
| FLOW-HEI-01-B2 | PARTIAL | FIX-02 |
| FLOW-RO-02 | NEEDS CHED DECISION | DEC-01 |
| FLOW-RO-02-B1 | NEEDS CHED DECISION | DEC-01 |
| FLOW-RO-06 | PARTIAL | FIX-07 |
| FLOW-RO-06-B1 | PARTIAL | FIX-07 |
| FLOW-ARROW-11 | PARTIAL | FIX-07 |
| FLOW-ARROW-14 | PARTIAL | FIX-09 |
| FLOW-ARROW-15 | PARTIAL | FIX-07 |
| FLOW-ARROW-16 | PARTIAL | FIX-07 |
| FLOW-ARROW-17 | PARTIAL | QA-01 |
| FLOW2-HEI-2 | PARTIAL | DEC-01 |
| FLOW2-RO-2 | NEEDS CHED DECISION | DEC-01 |
| FLOW2-RO-3 | NEEDS CHED DECISION | DEC-01 |
| FLOW2-RO-4 | PARTIAL | DEC-01 |
| FLOW2-HEI-ARROW-1 | PARTIAL | DEC-01 |
| FLOW2-HEI-ARROW-2 | PARTIAL | DEC-01 |
| FLOW2-HEI-ARROW-3 | PARTIAL | DEC-01 |
| FLOW2-HEI-ARROW-4 | PARTIAL | DEC-01 |
| FLOW2-HEI-ARROW-5 | PARTIAL | DEC-01 |
| FLOW2-RO-ARROW-1 | NEEDS CHED DECISION | DEC-01 |
| FLOW2-RO-ARROW-2 | NEEDS CHED DECISION | DEC-01 |
| FLOW2-RO-ARROW-3 | PARTIAL | DEC-01 |
| AUD-SEC-01 | PARTIAL | FIX-08 |
| AUD-REV-03 | PARTIAL | DEC-01 |

## Unverified human and operational obligations

COULD NOT VERIFY entries are retained in the CSV. HEI diligence, real signatures/notarization, actual participant safety, agency/OSDS/CEB performance, sanctions authority and incorporated standards need competent human evidence. They are not a blanket instruction to build modules. Informational examples require no executable feature.

Use disposable fictional data and local mail capture for future QA. Keep private-file quarantine and MFA active. Reports must identify fresh versus prior results, exact test totals, blockers, and PostgreSQL/scanner/recovery outcomes separately. Never infer production readiness from SQLite or navigation alone.
