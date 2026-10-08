# Gap #2 Slice 3 — classification interfaces and frozen outputs

8 October 2026. Authorized Slice 3 only; no policy activation, publication assumption, throttle/MFA change, new dependency or normal-database migration/seeding.

## Delivered behavior

- HEI activity forms collect explicit eligible rule-set, planning/supervision, phase, baseline references and bounded applicability context. Configured rule parameters remain data. Saving produces a recommendation. Authorized confirmation creates another immutable version through the shared `SaveActivity` transition with stale-version protection.
- The activity history action shows declared versus requirement treatment, actor/reason, cumulative and sequential differences, requirement sets before/after, sources and deadline uncertainty. After another edit, confirmation is required again before a rules-based filing.
- CHED's submitted-classification action reads the receipt snapshot. Later unsent edits do not change what the reviewer sees. Existing completeness checks use the confirmed treatment, not UI labels.
- New selected Annex F preparations carry a clearly labelled application-only classification continuation. Category 1/2 treatment cannot certify SAA supporting evidence as absent. Ordinary Category 3 remains exempt. Existing preparations and uploaded signed originals remain untouched; no checkboxes are automatically attested.
- Existing intake notifications include the frozen category/assessment reference. Regional snapshots and CSV/PDF reports retain filed assessment context without consulting current activity state. Historical rows without metadata have an explicit fallback, not an inferred classification. Revision counting and authorization checks are unchanged.

Sources: **CMO No. 24, s. 2026**, final 36-page supplied source, issued 28 September 2026; §§12–13, PDF pp.5–7; §14, PDF pp.7–12; §16, PDF p.12; §24, PDF p.13; Annex F, PDF pp.25–26; Annex I, PDF p.29. Source hash and archived July draft are recorded in `policy-source-register.md`.

## Verification

Execution artifacts are ignored under `storage/app/gap2-slice3/`. Tests use fictional accounts and an isolated SQLite/storage copy; email goes to logging. Browser uploads use the genuine scanner and preserve quarantine on scanner failure.

The first full PHPUnit run found five revision-form failures: `classification.phase` was absent when hydrating legacy activity records. Adding the neutral before-conduct form default corrected all five; the affected tests plus seven new interface tests passed **12/12, 105 assertions**. This fixes input hydration without enrolling historical receipts or selecting a policy.

The first full browser run passed **34/35**; the added case failed at an exact label selector because Filament includes the required asterisk (`Eligible classification rules*`). The test selector was corrected. One standalone retry was aborted because the existing QA router was launched from the wrong working directory; the corrected retry uses `public` as the server working directory. Neither issue prompted application authentication changes.

Final PHPUnit command (portable PHP 8.4, writable QA temp configuration):

```text
php -c storage/app/verification-triage/current.ini vendor/bin/phpunit --log-junit storage/app/gap2-slice3/phpunit-complete.xml
PHPUNIT_EXIT=0
{"tool":"phpunit","result":"passed","tests":462,"passed":462,"assertions":2229,"duration_ms":435135}

php -c storage/app/verification-triage/current.ini vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

php -c storage/app/verification-triage/current.ini vendor/bin/phpstan analyse --no-progress --error-format=table
PHPSTAN_EXIT=0
{"tool":"phpstan","result":"passed","errors":0}

node node_modules/vite/bin/vite.js build
vite v8.3.3 building client environment for production...
✓ 3 modules transformed.
✓ built in 11.28s
```

Vite emitted the existing optional `fontaine` optimization warning; build succeeded without adding dependencies. The seven new interface tests passed with 37 assertions. Broader targeted coverage passed 149 tests/533 assertions before the final full run. Unit submission fixtures simulate evidence rows only; actual malware scanning is verified separately in the browser campaign.

Final syntax/diff checks:

```text
PHP_LINT_FILES=382 ERRORS=0
node --check tests/Browser/campaign.spec.js
JS_SYNTAX_EXIT=0
git diff --check
[no whitespace errors; Git emitted CRLF-to-LF normalization warnings]
```

The standalone browser retest passed **1/1 in 26.9s**. It uses accessible roles for Filament's `aria-labelledby` fields rather than assuming HTML label elements. Both the exact selector and an intermediate `getByLabel` retry failed before the accessible-role selector passed. The seven interface tests were then extended to render/download the new Annex F and regional PDF content: **7/7, 42 assertions**. The final full suite passed **462/462, 2,229 assertions** after these additions.

Final complete browser command, against a fresh disposable copy on port 8007 with private storage, local mail logging and genuine scanner configuration:

```text
node node_modules/@playwright/test/cli.js test
BROWSER_COMPLETE_EXIT=0
ok 35 ... classification: HEI saves a recommendation and confirms the saved revision (25.6s)
35 passed (7.7m)
```

The existing 34 cases include real MFA/account roles, Annex B–M outputs, ownership rejection, correction/resubmission/Annex K, reporting, genuine PNG scan, and scanner-failure quarantine/retry. Existing shared-IP login limits required waits of 6s and 15s; production limits and the login helper are unchanged. Source fixtures already contain filed lifecycle receipts, so repeat scenarios exercise their existing idempotent paths; this browser run does not claim to create all six historical receipts from scratch.

## Preservation evidence

Read-only full-row comparisons by ID between the original QA copy and final disposable browser database are recorded in `storage/app/gap2-slice3/preservation-complete.json`. Checksums of original rows match after the run for every immutable/history group below.

| Table | Before → after | Original rows changed / deleted |
|---|---|---|
| submissions | 6 → 6 | 0 / 0 |
| submission_requirements | 69 → 69 | 0 / 0 |
| acknowledgments | 2 → 2 | 0 / 0 |
| submission_reviews | 5 → 5 | 0 / 0 |
| activity_classifications | 19 → 21 | 0 / 0 |
| certification_drafts | 2 → 2 | 1 / 0 |
| audit_events | 205 → 231 | 0 / 0 |
| policy_versions | 3 → 3 | 0 / 0 |

The changed certification is the unsigned editable draft intentionally changed by the existing Annex F browser scenario, not a filed certification snapshot. Two activity versions/classification records and two assessments (recommended/confirmed) were appended by the new scenario. Audit events were appended. No existing policy changed state. No normal development database or uploads were targeted, and no backup-restoration/production recovery claim is made by this slice.

Interrupted development runs are not counted as successful campaigns: an early browser run was stopped to move the new scenario after existing lifecycle fixtures, and an early PHPUnit run was stopped before final timezone/access refinements. The completed failure/retest results above remain recorded; final totals do not combine partial runs.

## Manual walkthrough

1. Use the isolated local/testing environment with the already-delivered classification schema/rules and explicitly enabled candidate evaluation. Production does not offer an unactivated candidate. No normal database setup was performed by this slice.
2. Sign in as a verified, authorized HEI submitter. Open **Activity plans → Revise**. Select the eligible rules, provide original planning/supervision and baseline facts, and explain changes. For actual conduct, record its date/time and evidence reference; do not guess it.
3. Save. Open the activity and **Classification history and requirements**. Inspect the recommendation, differences, evidence requirements and any missing facts/lead-time warning.
4. Choose **Confirm saved classification**, enter a reason and submit. Reopen history to verify the new confirmed version; revising again creates an unconfirmed recommendation.
5. File the eligible package. In CHED's submission view choose **Submitted classification and requirements**. Check it against the filed assessment, then make an unsent HEI edit and verify the CHED snapshot stays unchanged.
6. Preview the newly selected Annex F preparation and generate a new regional export. Classification context must match the receipt. Older signed originals remain separate.

## Limits and remaining gates

- CMO 24 stays a candidate. Verified publication/reference and publication +15-day effectivity remain mandatory before official activation (§41, PDF p.18).
- Materiality defaults and thresholds remain pending CHED decisions. Formal RCD timing remains explicitly unresolved (§24, PDF p.13); immediate reporting remains separate (§16, PDF p.12). No new deviation dispatch job or other backlog item was implemented.
- Legacy unconfigured plans are not silently enrolled on GET. Classification forms require explicit eligible-rule selection when such rules are configured. Existing corrections preserve their submitted policy.
- Docker and `psql` are unavailable on this workstation, so PostgreSQL runtime/concurrency and production recovery remain unverified. SQLite/browser success is not production readiness.
- Browser coverage checks existing Annex B–M flows and the new recommendation/confirmation interaction. It does not establish every unresolved CMO interpretation, accessibility criterion or load limit.

Stop after this slice; no further backlog item is authorized here.

## Diff and delivery

Separate branch: `fix/gap2-classification-slice3`, based on `290344d`. Nineteen source/test/documentation files change: three new shared Action/form/presentation classes, one new PHPUnit feature file, the existing activity forms/actions and filing notice/Annex F check, regional output metadata, one appended browser scenario, README/architecture/decisions/backlog/traceability and this report. No migrations, seeders, authentication code, limiter settings, package files or scanner code changed. QA databases, storage, credentials, logs, downloads and backups remain ignored.

Overall FIX-02 stays **PARTIAL** because CHED interpretations and official activation/production verification remain outstanding; the authorized Slice 3 software delivery and local test scope are complete. Existing disputed policy questions are not converted into verified legal rules.
