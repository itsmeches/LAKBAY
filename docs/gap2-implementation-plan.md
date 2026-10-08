# Gap #2 — classification and revision treatment: proposed plan

8 October 2026. **PLAN ONLY; not approved or implemented.** No policy activation, database changes, application changes or test changes were performed for this plan. FIX-02 remains PARTIAL. This plan does not authorize FIX-03/04 or other backlog work.

Approval addendum, 8 October 2026: the owner subsequently authorized implementation **in slices**, stopping after each. Slice 1 is data/configuration/history only. The original plan was committed first as `edec248`. All policy references below mean **CMO No. 24, s. 2026**, final supplied 36-page source, using PDF page numbers. The [source register and reference map](policy-source-register.md) supplies the section/page pairing for each requirement and identifies the archived July draft. No earlier draft section number overrides this map.

## Verification answers before the plan

### Committed QA fixes

Both fixes are committed in `070115561849061cf3c3bd53c4b1f39361035961` (`Preserve existing QA harness, security fixes and staging preparation`), on `fix/category-3-saa-verification`, an ancestor of current HEAD `7f3fa52`. They were earlier QA work preserved in that commit, not fixes present at baseline `f7a8af8`. They are no longer uncommitted patches. This does not claim a remote push.

- `app/Filament/Ched/Resources/Pages/ViewMonitoringRecord.php`: supplies header actions through `WorkflowUi::actions(...)`, binding the viewed record. This exposes the monitoring preview/print action.
- `app/Jobs/ScanDocument.php`: an exit code of 1 is classified as infection only when scanner output also matches a detection ending in `FOUND`. Execution failure remains a scanning failure/quarantine, rather than falsely labelling a file infected.

### Annex M source correction

The selected latest supplied **CMO No. 24, s. 2026**, 36-page PDF, SHA-256 `a7915e3eea2f89a6a5cb63ac83ce3947aa40abbd79bf42499aa4a17d22174c01`, contains **Annex M on PDF pages 33–34**: “Monitoring and Evaluation Tool of CHED Regional Offices (CHEDROs)”. It has 14 criteria, findings, signatories and HEI conforme. It is not an application-specific annex. An earlier draft's A–K range must not be used to identify annexes in this source version.

`resources/views/documents/monitoring.blade.php` correctly identifies Annex M as belonging to CMO 24. Removing that identification would misrepresent the selected source. Application-only workflow/rating metadata must remain separate from official template fields; historical template references remain associated with their original source version. No UI/export relabelling was applied.

### Login throttles — unchanged

Both panel providers use the standard Filament Login, the `web` guard and required recoverable app MFA.

| Limiter | Attempts/window | Key and consequence |
|---|---|---|
| Login `authenticate()` | 5 calls / 60 seconds; sixth blocked until remaining TTL expires | Livewire component class + method + request IP. No email or panel ID in key; HEI and CHED share the limit when using the same IP/cache. |
| MFA challenge | 5 challenge calls / 60 seconds; sixth blocked until remaining TTL expires | Auth guard + user class + user ID. Shared across the two panels for the same user. |

Evidence: `vendor/filament/filament/src/Auth/Pages/Login.php`, `vendor/danharrin/livewire-rate-limiting/src/WithRateLimiting.php`, `vendor/filament/filament/src/Auth/MultiFactor/MultiFactorChallenge.php`; `RateLimiter::hit()` defaults to 60 seconds. Successful attempts are also counted; these paths do not clear their limiter on success. Password and MFA form submissions both invoke `authenticate()`, so an ordinary MFA login normally consumes two IP-limiter calls. A shared office/NAT or rapid repeated logins can therefore temporarily throttle real users during normal use. This is temporary throttling, not permanent account deactivation. No production limits were loosened.

The earlier harness pacing/fresh TOTP addresses timing, but pacing is not full test isolation. Proposed test-only improvement, requiring review of its diff before application: a unique isolated server/database/cache/session namespace for each campaign or independent parallel worker, reusable authenticated browser state per role, and serialized/paced cross-role login setup within a worker. Start with empty scratch cache/session tables, never clear live cache or reuse credentials/session state across campaigns. Keep deliberate shared-IP throttle tests separate. Do not spoof trusted IP headers, disable MFA, increase production limits, or reset counters inside tests claiming to verify throttling. No harness change was made in this task.

### Gap #1 draft-selector regression

Commit `7f3fa5263991cefe1d1d6128130d63eaf15dee0b`, `app/Services/Compliance/PolicyRules.php::forDraft`:

```diff
- while ($next = PolicyVersion::where('parameters->supersedes_policy_version_id', $policy->id)->latest('id')->first()) {
+ while ($next = PolicyVersion::where('parameters->supersedes_policy_version_id', $policy->id)->latest('id')->get()->first(function (PolicyVersion $successor): bool {
+     try {
+         $this->authorizeSubmission($successor);
+         return true;
+     } catch (ValidationException) {
+         return false;
+     }
+ })) {
```

Previously the latest successor was followed regardless of activation eligibility. Now draft selection reuses submission eligibility: production skips unactivated/future successors; explicit local/testing candidate experiments remain possible. Confirmed CMO successors require verified publication/reference, publication +15-day effectivity, and an effective date already reached. Corrections keep their submitted baseline; no historical requirements/verdicts are recomputed. The committed regression test is `test_production_drafts_do_not_follow_unactivated_successors`. No fresh suite run was performed for this documentation-only task; prior results are recorded in `gap1-verification-report.md`.

## Gap and source boundaries

FIX-02 and traceability `SEC12-03–05`, `SEC13-02–08`, `FLOW-HEI-01-B2` concern missing proof of original CLOCA inclusion/pre-year planning and missing treatment of later revisions. Sections 12 and 13 distinguish planned/calendar-listed Category 1, new or revised Category 2, and student-initiated Category 3. Section 13.B.3 explicitly covers revisions of schedule, scope or design from the initially submitted or approved activity. It supplies no numerical materiality exemption. Section 13.C.5 requires Category 2 requirements for strenuous activity, accommodation, long-distance travel, vulnerable groups or class-size participation.

Section 16 requires immediate reporting of deviations using Annex I. **Section 22 concerns HEI diligence/discretion to proceed; it does not prescribe a 72-hour change deadline.** Section 24 and Section 14 row 22 provide “not later than 72 hours upon conduct”; the commencement/conclusion anchor is unresolved. Annex I (PDF p.29) records original submission date; previous/revised date/time, venue, PIC/APIC, participant count and other changes; reason, supporting documents and prepared/reviewed/noted particulars. Classification and change reporting are complementary obligations, not exclusive alternatives.

## Current implementation and missing checks

- `app/Actions/Activities/SaveActivity.php::execute` accepts category, initiator, calendar/period, conditions, personnel and itinerary. Every save creates an immutable activity version and classification record, updates the activity title, and audits `activity.revised`. It validates ownership, existing activity identity references, at least one PIC, and a reason when the declared category changes.
- `app/Services/Compliance/RequirementResolver.php::requirementCategory` returns the declared category except Category 3 with any of the five escalation flags, which receives requirement category 2. Removing all flags currently returns requirement category 3 for the next version automatically. It does not inspect original calendar membership, planning time, or a schedule/scope/design difference.
- The resolver's `resolve` uses the version's stored requirement category and policy/stage predicates. It does not independently reclassify the version.
- `app/Actions/Submissions/SubmitActivity.php` snapshots activity versions and applicable rules. CMO Category 1 pre-activity intake checks that an annual calendar submission contains the activity identity; it does not establish that the current schedule/scope/design matches the original submitted version. Annual calendar submission requires declared Category 1. Corrections retain their original policy.
- `app/Actions/Reports/SaveChangeReportDraft.php`, `SubmitReport.php` and `app/Services/Reporting/ChangeReportBuilder.php` already support separately prepared/filed Annex I with original/revised version references, source receipt validation, differences, immediate notice and signed report. Saving an activity revision does not itself file an RCD or determine revised category treatment.

Missing: trustworthy original-calendar/version and pre-AY planning facts; evidence of appropriate HEI approval/supervision; classification of original versus later invitations; normalized comparison against submitted/approved plans; reasons and authorized confirmation for treatment changes even when declared category is unchanged; explicit handling of removed triggers/reversion; submission-time stale-classification protection. Do not expand FIX-02 into new PIC qualification rules or the dedicated notification job in FIX-04.

## Materiality matrix — proposal, not a confirmed CHED rule

Outcomes: **1** recommend Category 2 treatment; **2** RCD only; **3** record only. Outcome 1 also requires Annex I when it changes already submitted details. For an unsubmitted/unapproved draft, edits are normally outcome 3 and classification is assessed against current facts; do not manufacture an original submitted baseline. Category 3 retains its declaration and uses Category 2 requirements when escalated. Category 2 stays Category 2, with RCD for submitted deviations.

| Changed field | Proposed pre-conduct outcome for previously submitted/approved Category 1 | Authority / decision needed |
|---|---|---|
| Start/end date, time, duration or itinerary schedule | 1 + RCD | §13.B.3 schedule; do not exempt a short time move by a guessed threshold. Clerical corrections: NEEDS CHED DECISION. |
| Objectives, learning outcomes, substantive description or scope | 1 + RCD | §13.B.3 scope/design. Meaning-preserving spelling edits: 3; distinguishing these needs CHED decision. |
| Activity type, planned methods or program design | 1 + RCD | §13.B.3 design. |
| Venue or destination/locations | Propose 1 + RCD when substantive; 2 for correcting an address without changing destination | Annex I names venue; whether every venue change constitutes design revision: NEEDS CHED DECISION. Default errs toward reassessment because risk/coordination can change. |
| PIC/APIC replacement or responsibility assignment | 2, with readiness/evidence reassessment | Annex I names PIC/APIC. Automatic Category 2 not explicit: NEEDS CHED DECISION; role replacement alone need not alter learning design. |
| Participant count | 2; 1 if it also changes scope/design | Annex I names count. Material count threshold: NEEDS CHED DECISION. Category 3 class-size trigger independently escalates. |
| Participant profile / vulnerable-group involvement | Reassess scope/risk; propose 1 if substantive | NEEDS CHED DECISION for Category 1 treatment. Category 3 vulnerable-group trigger is explicit §13.C.5. No medical-history collection. |
| Student/parent costs, payer or expense breakdown | 2 and conditional consultation/evidence reassessment; 1 if scope/design also changes | Consultation obligation does not establish automatic reclassification: NEEDS CHED DECISION. |
| Transport mode/provider/route | Propose 1 for changed route/mode affecting design; 2 for equivalent provider substitution | NEEDS CHED DECISION; reassess travel, contracts, itinerary and risk. |
| Accommodation added/removed or changed | Propose 1 + RCD | NEEDS CHED DECISION for Category 1. Category 3 accommodation added explicitly requires Category 2 requirements. |
| Strenuous/physical activity or long-distance travel introduced | 1 when schedule/scope/design changes | §13.B.3; explicit Category 3 triggers §13.C.5. Long-distance threshold NEEDS CHED DECISION. |
| Parallel activity plan / alternative learning provision | 2; 1 for substantive learning-design change | §12.F and §13.B.3; exact distinction NEEDS CHED DECISION. |
| External invitation received after original calendar | 1 if newly proposed or changed scope/schedule/design; otherwise assess facts | §13.B.1–3. Invitation alone does not override original calendar inclusion. |
| Coordination, assistance or accreditation/contract evidence | 2 if submitted operational details change; otherwise 3 with evidence reassessment | NEEDS CHED DECISION on what must enter Annex I “Other”. No category change for replacing a documentary copy alone. |
| Office responsible, program/course or organizer | Propose 2; 1 where it changes actual scope/design | NEEDS CHED DECISION. |
| Contact details, display spelling, letterhead/signatory metadata | 3 for clerical correction; retain audit and original output | NEEDS CHED DECISION where changed submitted contacts are relevant to safety or Annex I “Other”; substantive authorized-person substitution is not merely spelling. |
| Third-party services newly introduced | Propose 1 if design changes; otherwise 2 plus contract readiness | NEEDS CHED DECISION; do not treat contract evidence alone as an escalation trigger. |
| HEI, campus, academic period or calendar identity | No ordinary edit; use explicit new preparation/linking process | Current identity locks remain. Cross-institution moves forbidden; campus/period transfer procedure NEEDS CHED DECISION. |

## Proposed workflow and timing

1. Identify current draft facts, original submitted/approved baseline version, planning date/source, calendar receipt, initiator and supervision/authorization reference. Missing facts are “not established”, not silently false or fabricated.
2. Normalize and compare current facts against both the immediate prior version (change event) and original/latest filed baseline (cumulative outstanding changes). Recommend category/treatment and explain the source rule and evidence effects.
3. An authorized HEI confirmer records a reason, confirms treatment and reviews added/removed obligations. A preparer may save an incomplete draft; official submission must fail clearly if required classification facts/confirmation are missing. CHED reviewers record findings rather than overwrite HEI history. Confirm exact confirmer permission with CHED.
4. Before conduct, Category 2 applicable documents follow their individual policy deadlines, including the three-working-day lead where prescribed. Calculate from revised scheduled start, Asia/Manila and the referenced holiday calendar; keep old and revised due dates.
5. If already inside that lead, accept recorded changes/notices and flag lateness/insufficient lead; no silent refusal of urgent intake, automatic exceptional clearance, implied permission or backdating. HEI discretion under §22 and separate restrictions/clearance remain visible. Confirm which official filings may be accepted late; do not introduce a software ban on conduct without authority.
6. During/after conduct, record actual deviations and Annex I immediately. Preserve “72 hours upon conduct” as unresolved until CHED selects the anchor; show no authoritative calculated deadline or on-time verdict. A future confirmed anchor must be versioned, not applied retroactively. Do not retroactively convert the historical activity to an approved Category 2 plan.
7. After submission/acknowledgment, create a new version and change assessment/report, never edit the signed submission or letter. New treatment/evidence needs a new filing or existing correction pathway as appropriate; acknowledgment remains tied to the reviewed revision, not the latest activity.

## Edge cases and preservation

- Category 1 revised after CLOCA: compare to the actual included version. Schedule/scope/design change recommends Category 2 treatment for the new version and Annex I; original CLOCA and acknowledgment remain intact. Confirm supplemental-calendar representation and declared-category display with CHED.
- Reversion: record another version/event. Reversion before any submitted/approved baseline can simply recompute the draft. After a reported revision, do not erase that event or silently downgrade; require reasoned confirmation and record withdrawal/reversion status. CHED must decide whether restored Category 1/ordinary Category 3 treatment is permitted.
- Several changes: each event preserves previous/revised facts; pending report shows cumulative differences without cancelling history. Consolidated reporting versus a separate RCD per event needs CHED confirmation. Do not conflate a correction revision with an activity deviation.
- Category 3 gains any trigger: retain declaration 3, recommend requirement category 2, confirm with reason, include applicable SAA (existing term evidence under gap #1's pending coverage interpretation).
- Category 3 loses triggers: reassess prospectively; require authorized confirmation/reason rather than automatic downgrade. Historical SAA requirements/verdicts remain.
- Previous-rule submissions: read their original policy, activity versions, classification and requirement snapshots. A new amendment assessment may describe their current changed facts under an eligible prospective rule, but cannot reinterpret the old receipt/verdict. Existing correction revisions retain their original baseline; where new substantive changes require a new filing, expose that distinction explicitly and obtain CHED confirmation.

## Architecture and additive data proposal

Keep transitions in shared Actions. A named, versioned classification evaluator supplies recommendations to `SaveActivity`, submission validation and UI; the requirement resolver consumes the confirmed version result. Store bounded rule parameters/source references using existing policy mechanisms, not a programmable expression engine. Candidate rules remain test-only until existing activation gates permit them.

Reuse immutable `activity_versions` and `activity_classifications`. Proposed additive nullable classification metadata: previous classification/version ID, original calendar/submission/version IDs, classification-rule version, recommendation, confirmed treatment, change reason, changed-field/diff snapshot, source references, confirmer/time and before/after requirement-code snapshots with policy/stage IDs. Structured approval/planning references can live in versioned preparation metadata unless relational queries require columns. No existing classification is backfilled as if its facts were confirmed.

Use an append-only assessment/event for changes when necessary to distinguish saved draft recommendation from authorized confirmation and filed report. Decide whether the existing classification model can hold it before adding another table. Submission snapshots reference the confirmed assessment; do not mutate original snapshots. Perform transition and audit in one transaction with a row lock/expected current version; reject stale confirmation, duplicate action and mismatched original receipt. Reuse existing RCD links and supplements instead of parallel report tables.

Any migration is additive and runs once through Laravel migration tracking. Any optional reconciliation command is separately idempotent, default dry-run and reports affected unsubmitted drafts, existing submissions skipped, missing facts and proposed new assessments. Apply only appends events/new versions for explicit draft reconciliation; no bulk rewrite of historical versions. Preserve/check row counts and hashes before/after. Reopening an old unsubmitted draft shows a recommendation from eligible rules; persistence occurs on explicit save/confirmation, not a GET side effect.

## Downstream effects and scope limit

- HEI: clear “original plan / current revision / recommended treatment / confirmed treatment”, reason and added evidence, Annex I prompt and timing warnings. Validate in Actions as well as forms.
- CHED: see original receipt and revised assessment side by side, immutable review context, reasons and outstanding new requirements. Old completeness verdict remains old verdict.
- Completeness, Annex F and exports: use the same confirmed version/policy/stage; ordinary Category 3 remains SAA N/A, escalated Category 3 requires it. Pending/unconfirmed drafts are labelled; historical output is not regenerated from latest facts.
- Reporting: count activities and current filed revisions without double counting; retain historical category and treatment distinctly. Export assessment provenance and RCD references with existing access controls.
- Notifications: existing revision/submission/correction messages must accurately state treatment changes and target authorized recipients. The missing dedicated change-intake job is FIX-04 and remains deferred; do not silently include it in FIX-02.

## Delivery and test plan after approval

1. Agree matrix, original-baseline selection, confirmer authority, prospective rule applicability and unresolved RCD timing. No activation.
2. Add evaluator/source metadata and dry-run first; show migration/data-impact diff before applying. Add explicit save/confirmation and submission stale-state checks.
3. Update relevant form/review/Annex F/export adapters together; integrate existing Annex I preparation without automatic signed filing.
4. Test original annual planning/calendar evidence, missing source facts, newly proposed activities and invitations both originally calendared and later received; all matrix rows and authorized/unauthorized confirmation.
5. Test Category 1 revisions after submitted CLOCA and acknowledgment; revert before/after filing; sequential/cumulative changes; stale concurrency, repeat actions and transaction rollback; no-change saves and normalization.
6. Test each Category 3 trigger independently (including vulnerable groups), combinations, gained/removed triggers, retained declaration, SAA applicability and unchanged historical July/CMO submissions/corrections/verdicts.
7. Exact deadline fixtures: two versus four working days before start with a holiday/weekend; exactly three working days; revised earlier/later start; after submission/ack; during/after activity; late intake; unresolved formal RCD anchor; Asia/Manila day boundaries.
8. Assert rendered HEI/CHED forms/actions, Annex I diffs, Annex F checks, completeness, existing notice content, private evidence access and exports all agree with the assessment. Cross-institution direct URL/action/export checks remain.
9. Run affected PHPUnit tests after each behavior fix, then full PHPUnit, Playwright with isolated authentication, PHPStan, Pint and asset build if assets change. Run PostgreSQL where available; report it separately if blocked. Capture actual totals/output and history preservation evidence. No new pass claim is made in this plan.

## CHED decisions and risks

Confirm materiality exceptions versus literal schedule/scope/design revisions; venue/count/cost/transport/PIC distinctions; class-size/long-distance/vulnerable-group definitions; legitimate downgrade/reversion; declared Category 1 versus Category 2 display; approved-but-not-submitted baseline; external invitation treatment; original/supplemental CLOCA representation; confirmation authority; evidence reuse and fresh authorization; old-policy correction versus new amendment; recurring changes/consolidated RCD; official receipt and holiday rules; exact “upon conduct” anchor and late-filing consequences.

Main risks: false downgrades, fictitious planning facts, mixing latest activity with old acknowledged snapshots, treating RCD as a substitute for Category 2 requirements, and imposing an invented materiality threshold. Publication remains unverified; candidate test behavior must not leak into production. Implement only approved interpretations and retain uncertain outcomes visibly for human resolution.

## Read-only command evidence in this task

`git branch --show-current` returned `fix/category-3-saa-verification`.

`git show --stat 0701155` returned commit `070115561849061cf3c3bd53c4b1f39361035961`, including `ViewMonitoringRecord.php` (9 added lines) and `ScanDocument.php` (2-line change), 20 files total. `git show 7f3fa52 -- app/Services/Compliance/PolicyRules.php` returned the eligibility-filter diff reproduced above.

`git status --short` was empty before writing this plan. Source searches/read operations confirmed the cited code paths and vendor limiter settings. No application/test/config/database changes or fresh test runs were made. This documentation file is the only new deliverable in this task; implementation awaits approval.
