# Gap #2 — Slice 2 verification

8 October 2026. Backend slice only; UI integration remains Slice 3. No policy activation, login/MFA changes, normal database migration or production data seeding was performed.

## Change

An explicit, immutable evaluation rule-set successor supplies materiality paths, contextual outcomes, eligibility facts, revision handling and timing parameters. It is seeded only through the explicit QA/configuration seeder; repeat execution does not replace existing rules. No new migration is needed in this slice.

`SaveActivity` invokes the evaluator and shared assessment Action in its transaction. The evaluator compares both the original filed/authorized baseline and the preceding version, preserves declared Category 3, and recommends Category 2 treatment for configured triggers. Confirmation requires verified submission authority and sufficient facts. Once assessed, an activity cannot bypass assessment by omitting the context; stale version writes are rejected. Legacy activity editing remains unchanged until explicitly enrolled. New submissions freeze their exact assessment and require confirmed, consistent treatment.

Category 1 revision treatment follows CMO No. 24 §13.B, PDF p.6; Category 3 escalation follows §13.C.5, PDF p.7. Category definitions and applicability follow §§12–14, PDF pp.5–12. The three-working-day calculation retains holiday-calendar references. During/after-conduct changes preserve prior treatment and record change-report needs. Immediate notification follows §16, PDF p.12; the formal 72-hour anchor under §24, PDF p.13 and Section 14 row 22, PDF p.12 remains unresolved. Annex I is PDF p.29. There is no numeric/minor-change exemption. Materiality defaults and reversion/removal handling remain configurable interpretations, not confirmed CHED rules; see decisions and open questions.

## Verification evidence

Artifacts are ignored under `storage/app/gap2-slice2/`. The final focused suite reports 42 tests and 117 assertions passed. The final, non-overlapping full run (`phpunit-serial.log`, JUnit `phpunit-serial.xml`) reports:

```text
PHPUNIT_EXIT=0
{"tool":"phpunit","result":"passed","tests":446,"passed":446,"assertions":2162,"duration_ms":247402}
{"tool":"pint","result":"passed"}
{"tool":"phpstan","result":"passed","errors":0}
PHP_LINT_FILES=378 ERRORS=0
```

Commands: portable PHP with `-c storage/app/verification-triage/current.ini`, `vendor/bin/phpunit --log-junit storage/app/gap2-slice2/phpunit-serial.xml`, `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress --error-format=raw`, and PHP `-l` for every tracked/new PHP source file. `git diff --check` passed (line-ending normalization warnings only). Browser command: portable Node `node_modules/@playwright/test/cli.js test` against the disposable QA server/database, with the existing test configuration unchanged.

The initial browser run passed all 34 scenarios. The final run reports **33 passed, 1 failed (7.7 minutes)**. The Director scenario timed out at `campaign.spec.js:32` waiting for a Livewire POST during MFA, after login rate-limit retries. The trace and error context remain in `browser-release-artifacts/`. No login/test-isolation fix was mixed into this slice. This is an unresolved verification blocker, not a claimed successful final browser run. Browser scenarios exercise existing UI; the newly staged assessment input has PHPUnit coverage and is not yet exposed in the UI.

Overlapping intermediate PHPUnit runs caused shared fake-file storage interference (missing original evidence and export metadata). Those runs are superseded by the final single run; no application file-storage workaround was introduced. Earlier fixture mistakes were corrected in the new tests.

New tests cover all five escalation triggers, ordinary Category 3, original and July receipts, invitations, planning eligibility, missing facts/reasons, confirmation permissions, contextual materiality, one-second changes, sequential changes/reversions, configurable trigger removal, immutable requirements, late intake, actual-conduct evidence, stale versions, candidate safeguards, working-day boundaries/holidays and holiday-version protection.

## Preservation

Read-only comparison: original QA source versus disposable final browser database. Counts below are before → after; existing row equality was checked by ID and all column values.

| Table | Counts | Existing rows changed |
|---|---|---|
| submissions | 6 → 6 | 0 |
| submission_requirements | 69 → 69 | 0 |
| acknowledgments | 2 → 2 | 0 |
| submission_reviews | 5 → 5 | 0 |
| activity_classifications | 19 → 19 | 0 |
| certification_drafts | 2 → 2 | 1 |
| audit_events | 205 → 227 | 0 |
| policy_versions | 3 → 3 | 0 |

The changed certification is an unsigned draft deliberately edited by an existing browser scenario. Audit entries are appended. This does not claim that editable drafts never change. Submitted snapshots are not recalculated; before-requirement sets use the filed receipt, and new assessments/versions are appended. Holiday calendars referenced by assessments are protected against mutation. Normal development records were not used as test targets.

## Limits and next slice

PostgreSQL is unverified: neither `docker` nor `psql` is available on PATH. No infrastructure installation was attempted. No new real-world policy activation, publication, signature validity or production-readiness claim is made. The browser suite's genuine scanning scenarios are distinct from PHPUnit's simulated document fixtures.

CHED screens, notifications, Annex F, exports and report presentation remain for Slice 3. The overall gap stays PARTIAL. Stop after Slice 2 and await authorization.
