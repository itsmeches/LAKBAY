# LAKBAY implementation decisions

## Gap #2 Slice 2 — staged backend, 8 October 2026

CMO No. 24 §§12–13, PDF pp.5–7; §16, PDF p.12; §§22/24, PDF p.13; Annex I, PDF p.29: shared backend evaluation compares original filed/HEI-authorized facts and immediate prior facts separately, recommends treatment and records an authorized confirmation. It preserves the declared category and old versions/receipts/verdicts. A revised Category 1 uses Category 2 requirements when configured changes warrant it; Category 3 uses the configured §13.C.5 triggers without changing declaration.

The explicit `ClassificationEvaluationRuleSeeder` appends evaluation V2 to proposed V1, adding bounded field bindings, contextual choices and the original-calendar invitation predicate. No activation or rewrite occurs. Original calendar receipts include historical July `legacy` stages; an added activity cannot acquire Category 1 merely by sharing their calendar ID. An invitation alone for an unchanged originally listed activity does not automatically reclassify it (CMO No. 24 §13.B.1–3, PDF p.6).

Slice 2 is intentionally staged: `SaveActivity` accepts an explicit `classification` context/rule-set selection, and continues requiring assessment for later revisions of an assessed activity. Legacy versions are not silently opted in. Slice 3 will add the form controls. Assessed saves require the exact expected version; authorized verified HEI membership and configured confirmation permission are checked server-side. The old classification row remains a compatibility record; the assessment's explicit recommended/confirmed state is authoritative for the new path, and submission rejects unconfirmed/stale-policy assessments.

RCD timing remains `unresolved` with null formal due date: no `changed_at +72h` interpretation is introduced (CMO No. 24 §14 row 22/§16, PDF p.12; §24, PDF p.13). Actual recorded execution or an explicit conduct date/time plus evidence reference distinguishes during/after changes; a planned date alone does not establish conduct (§22, PDF p.13). During/after assessments describe changes without retrospectively upgrading the original approval. Insufficient three-working-day lead is flagged using the versioned holiday calendar; late intake remains accepted, never permission to proceed (§14, PDF pp.7–12).

Historic corrections retain their original policy. A new assessment under another policy cannot silently replace the correction baseline; it requires the appropriate separately recorded filing/clarification. No login, MFA, notification, export or template changes are bundled with this slice.

## Gap #2 Slice 1 — 8 October 2026

Owner approved the materiality defaults in [the plan](gap2-implementation-plan.md), pending CHED decisions. This approval is an operational implementation source, not CHED confirmation. Each persisted materiality row carries decision/source references; no numeric or “minor change” exemption is added. See [source register](policy-source-register.md): CMO No. 24 §§12–13, PDF pp.5–7; §16, PDF p.12; §§22/24, PDF p.13; Annex I, PDF p.29.

Slice 1 adds independent immutable rule sets and assessment chains. No existing classification, policy, receipt, submission requirement or verdict is updated/backfilled. Default data is loaded only through the explicit `ClassificationRuleSetSeeder`, not normal seeding; status is `proposed`, and no resolver/UI consumes it yet. CHED configuration changes can append a data-only successor via `ProposeClassificationRuleSet` with existing verified `policy.manage` authorization and an audit event; the later administrative interface is outside this slice. It does not activate anything.

RCD interpretation: immediate notice remains applicable, but `rcd_anchor=unresolved` and 72-hour formal arithmetic is not selected (CMO No. 24 §14 row 22/§16, PDF p.12; §24, PDF p.13). [Open question](open-questions.md) remains. Migration rollback deliberately refuses to drop these history tables; roll back application code while retaining additive data.

Login/shared-IP changes are [plan only](login-throttle-proposal.md), independent from classification work. No production throttle or test-isolation change is included.

## SAA applicability — 8 October 2026, gap #1 only

- Section 14 row 10, PDF p.9: SAA applies to Categories 1 and 2. Ordinary Category 3 must not require it.
- Section 13.C.5: a declared Category 3 activity involving strenuous activity, accommodation, long-distance travel, vulnerable participants or class-size participation receives Category 2 requirements. Its declared category stays 3; its requirement category is 2, including SAA.
- Working interpretation supplied by the project owner: one valid SAA per HEI per term is sufficient; an escalated activity can reference that existing signed evidence. Annex C supplies semester/academic-year coverage. Scope/frequency and the cited Section 39 interpretation remain pending authoritative CHED confirmation; Section 39 itself is a repealing/incorporation provision, not an independently verified per-term mandate.
- Existing clean, signed institutional SAA uploads may be selected again for the relevant term; no new affidavit/upload per activity is required. Term coverage/signing/notarization must be checked by the responsible HEI/reviewer. This change does not add an automatic legal-validity or term-coverage verification service.

## History and draft recomputation

Fresh CMO candidate seeding uses row 10 for Categories 1–2 only. Existing populated policy versions are not edited by reseeding.

`php artisan lakbay:correct-saa` is a read-only dry-run. It reports candidate/rule/template additions, ordinary Category 3 unsubmitted activities whose checklists will change on opening, historical submissions preserved and zero updates/deletes. `--apply` copies the existing CMO policy and templates to a new candidate, excluding only row 10/category 3. Repeated runs return the same successor. No publication/effectivity/confirmation is inherited or invented.

The successor records `parameters.supersedes_policy_version_id`. New draft submission forms follow that link and recompute applicable evidence from the latest activity version's requirement category, conditions and submission stage. No stored activity, certification draft or historical checklist is rewritten. Unsaved browser evidence selections must be refreshed/reselected because the successor has new rule IDs. A stale direct new-submission request against the old version is rejected with a refresh message.

Choosing an existing correction request selects its original policy. Its new correction revision retains that baseline; the prior submission, requirements, evidence verdicts, certifications, acknowledgments and audit records remain unchanged. Historical CHED review/export screens continue reading stored submission snapshots.

Category 1's previously received annual package remains a valid prerequisite under a successor only when the stored Category 1 annual rules are identical (excluding database IDs/timestamps). A changed annual rule breaks compatibility. This prevents a SAA-only Category 3 correction from forcing Category 1 annual resubmission.

Annex F keeps its official template text and all rows. Ordinary Category 3 may mark the authorized-approval/SAA row explained N/A and leave its SAA availability box unchecked; faculty/HEI authorization remains separately recorded. This is human-entered applicability, not an automatic rewrite of a certification or an approval exemption.

CMO 24 remains a candidate until verified publication/reference and publication +15-day effectivity. No other backlog item is authorized by this decision.

## Actual additive application and preservation

Normal database dry-run: 0 ordinary Category 3 unsubmitted activities and 0 historical CMO submissions. Isolated QA dry-run: 1 ordinary Category 3 unsubmitted activity and 6 historical CMO submissions. Each application added exactly 1 candidate policy, 64 policy requirements and 13 form templates; no migration was needed. Repeat dry-run reported zero further additions.

Both SQLite databases were backed up using SQLite's online backup API to ignored `storage/app/gap1-backups/20261008-132544`. Before/after row-hash comparisons across all 60 ID-bearing tables in each database found zero existing rows updated or deleted. Accounts, passwords, uploads and all historical records were preserved. Private files were not rewritten.

Regression coverage in Cmo24AlignmentTest includes ordinary completeness without SAA; each of strenuous/accommodation/long-distance/class-size/vulnerable-participant escalation; unchanged Category 1/2 SAA; shared signed-evidence reuse; historical submissions/requirements/reviews/certifications/acknowledgments/audit preservation; old draft recomputation and old correction baselines; CHED review; Annex F explained N/A; stale policy rejection; public guidance; and compatible Category 1 annual intake.

Notification and export implementations contain no independent Category 3 SAA assumption; they continue using received records/snapshots. Historical forms/templates are not reconstructed. Current public guidance, README, UAT and clarification notes were corrected. QA fixture helpers follow candidate successors, and browser test environment overrides allow a disposable database/storage copy instead of changing existing QA records.

## Verification results — gap #1

Final full PHPUnit run (SQLite in memory, local array mail/fake test queues):

```json
{"tool":"phpunit","result":"passed","tests":393,"passed":393,"assertions":1917,"duration_ms":331037}
```

Focused CMO alignment suite:

```json
{"tool":"phpunit","result":"passed","tests":73,"passed":73,"assertions":229,"duration_ms":23637}
```

Final formatting, project-configured static analysis and full PHP syntax lint:

```json
{"tool":"pint","result":"passed"}
{"tool":"phpstan","result":"passed","errors":0}
{"tool":"php-lint","files":340,"errors":[]}
```

Actual commands used the portable PHP executable with `-d sys_temp_dir=storage/framework/testing -d upload_tmp_dir=storage/framework/testing` for PHPUnit; `vendor/bin/phpunit --colors=never` and `vendor/bin/phpunit tests/Feature/Cmo24AlignmentTest.php --colors=never`. Formatting: `vendor/bin/pint --dirty --format agent`. Static analysis: `vendor/bin/phpstan analyse --no-progress --memory-limit=512M`, using the existing app/level-1 configuration. Syntax lint checked every PHP file under app, config, database, routes and tests. `node --check` passed for campaign.spec.js and playwright.config.js. `git diff --check` passed. No dependency changes were made.

Direct read-only Chromium checklist check against the disposable copy:

```json
{"tool":"gap1-browser-checklists","results":[{"activity":3,"selectedPolicy":"3","saaRequirements":0,"expected":0,"passed":true},{"activity":4,"selectedPolicy":"3","saaRequirements":1,"expected":1,"passed":true}]}
```

Selecting original policy 2 automatically switched to successor 3. Ordinary Category 3 showed no SAA evidence row; escalated Category 3 showed one. Nothing was submitted by this check.

### Broader browser results and remaining verification limits

The complete 34-case Playwright campaign was executed on copied QA data at port 8002: **19 passed, 15 failed**, exit code 1, 7.7 minutes. Original report/screenshots/traces are ignored at `storage/app/gap1-browser/attempt-1/report/index.html` and `attempt-1/results`.

Failures: PDF downloads for B–J and L; Annex M printing; reviewer/records login checks; PNG upload; scanner-failure upload. PDFs returned HTTP 500 with DomPDF `Path must not be empty` during temporary font-file creation. Uploads did not progress beyond 100%, so those scanner cases did not establish successful scanning. Later reviewer/correction/acknowledgment checks and preserved B/C/F HTML checks passed; already-filed fixture branches do not establish a fresh lifecycle.

The first temporary-server arguments were incorrectly quoted for paths containing spaces. A relative-temp-path retry was interrupted when PDF failures persisted. An absolute-temp-directory retry also retained PDF failures and added login/upload timeouts; it was interrupted rather than treated as a successful full rerun. These runtime failures remain unresolved; no unrelated application changes were made to force a pass. Retest artifacts remain ignored under `storage/app/gap1-browser/artifacts`. The disposable server and retry processes were stopped. Normal servers/databases/uploads were not reset.

Therefore gap #1 has passing domain/Livewire/HTML and direct browser-checklist evidence, but the whole browser suite is **not green**. Fresh browser PDF/scanner verification remains blocked. PostgreSQL was not available and was not verified. Automatic term coverage/notarization remains outside this change pending CHED confirmation. The implementation does not constitute production or policy-activation approval.

Final before/after comparison, repeated after browser testing, again found zero existing rows changed/deleted in either original database. Only 1 policy, 64 rules and 13 template copies were added per database. Gap #2 and all other backlog items remain untouched.


## Gap #1 verification follow-up ? 8 October 2026

The candidate successor selector originally followed candidates in production. A new regression test failed before the fix (3 selected instead of confirmed 2); selection now reuses submission eligibility. Production keeps its eligible source until a successor is confirmed and effective. Local/testing candidate experiments remain explicit. Actual databases remain unpublished candidates; test publication fixtures do not activate them.

Full PHPUnit now passes 394 tests / 1,920 assertions. Current corrected-harness browser suite passes 34/34; the last committed pre-gap app f7a8af8 passes 32 and fails two earlier QA defects (missing monitoring print action and scanner-exit classification). All original 15 failures pass individually; old-temp controls reproduce environmental failures. History hashes and scratch restore pass. PostgreSQL remains unexecuted, with precise prerequisites in [verification report](gap1-verification-report.md). See [activation/rollback runbook](policy-activation-runbook.md); no policy was activated and gap #2 remains untouched.

## Gap #2 Slice 3 — explicit recommendation and confirmation

8 October 2026. Implement the approved Slice 3 using a two-step HEI workflow: save a recommendation, inspect the stored assessment, then confirm with an authorized submitter and reason. Confirmation creates a new immutable version; subsequent editing requires reconfirmation. The same shared Action enforces ownership, membership, stale state and policy eligibility. No production candidate activation or automatic historical enrollment occurs.

Filed CHED views, receipt notices and new report/export metadata read the submitted snapshot. Newly selected Annex F preparations include a clearly labelled application-only continuation and validate SAA claims against confirmed Category 1/2 treatment; historical forms are untouched. Preserve the earlier interpretation that deviations need immediate reporting while the formal 72-hour RCD anchor remains unresolved. No new change-notification dispatch mechanism or throttle adjustment is included.

Sources: CMO No. 24 §§12–13, PDF pp.5–7; §14, PDF pp.7–12; §16, PDF p.12; §24, PDF p.13; Annex F, PDF pp.25–26; Annex I, PDF p.29. Configured materiality defaults remain provisional CHED decisions, not new legal thresholds.
