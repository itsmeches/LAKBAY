# Ordinary Category 3 PIC designation fix

8 October 2026. Narrow fix authorized after the final-CMO delta check; stop before Slice 3.

Source: final 36-page CMO No. 24, s.2026, issued 28 September 2026; §14 row 12, PDF p.10 marks Categories 1–2 for PIC designation. §13.C.5, PDF p.7 gives qualifying Category 3 activities Category 2 requirements. Student supervision remains required under §13.C and row 19, PDF pp.7/11. Source identity is recorded in policy-source-register.md.

`SaveActivity` previously required a PIC before determining requirement treatment. The same guard now runs inside its transaction after the resolver/optional classification evaluator determines that treatment, and applies to requirement categories 1–2. Ordinary Category 3 can use the already supported Supervisor role without a PIC. Personnel minimum/identity fields and HEI authorization remain unchanged. Escalated Category 3 still needs a PIC; a failed escalation revision rolls back without changing the previous version or receipt. No new classifier, configurable threshold, APIC first-aid proof, migration, seeding, activation or UI change was introduced.

## Regression tests and verification

Nine new test cases cover:

- Ordinary Category 3 supervisor-only preparation, compliance and submission, with no row-12 requirement.
- Missing PIC rejected for Categories 1–2 and each of strenuous activity, accommodation, long-distance travel, vulnerable participants and class-size participation; rejected creation leaves no activity behind.
- Assessment-enabled confirmed Category 3 with supervisor only, followed by rejected strenuous escalation; receipt snapshot and original version remain identical.

Targeted command: portable PHP with `-c storage/app/verification-triage/current.ini`, `vendor/bin/phpunit tests/Feature/Cmo24AlignmentTest.php tests/Feature/ClassificationEvaluationTest.php`.

```text
{"tool":"phpunit","result":"passed","tests":125,"passed":125,"assertions":374,"duration_ms":37909}
{"tool":"pint","result":"passed"}
{"tool":"phpstan","result":"passed","errors":0}
No syntax errors detected in app/Actions/Activities/SaveActivity.php
No syntax errors detected in tests/Feature/Cmo24AlignmentTest.php
No syntax errors detected in tests/Feature/ClassificationEvaluationTest.php
```

An initial additional history assertion compared a loaded personnel relationship against an unloaded model; it was corrected to compare persisted attributes. No application workaround was added. The initial full run was cancelled before starting the final single run; no overlapping full test processes were used.

Final full-suite command: the same PHP/ini with `vendor/bin/phpunit --log-junit storage/app/gap2-slice2/pic-fix-final.xml`. Real output:

```text
PHPUNIT_EXIT=0
{"tool":"phpunit","result":"passed","tests":455,"passed":455,"assertions":2187,"duration_ms":215498}
```

`git diff --check` passed, with line-ending normalization warnings only. Artifacts are ignored at `storage/app/gap2-slice2/pic-fix-final.log` and `pic-fix-final.xml`.

## Preservation and limits

Manual check in HEI → Activity plans: create a student-initiated Category 3 activity without any escalation trigger, retain its required authorization and supervision references, and select Supervisor for the personnel row. Save without adding a PIC. The ordinary Category 3 policy checklist should omit PIC designation and SAA; other applicable evidence remains required. Attempt a revision adding a strenuous/accommodation/long-distance/vulnerable/class-size trigger with only that supervisor: saving should show the PIC-treatment error and retain the previous version. Add a properly designated PIC to save the escalated plan; qualification/training/ratio completeness checks still apply at submission. Candidate-policy testing remains restricted to the existing explicitly enabled local/testing configuration.

Only new save/revise operations use the corrected guard. No historical activity/submission/certification/acknowledgment/audit records are rewritten, deleted or re-evaluated. Tests use isolated test data; no normal development records or policy rows were changed. Historic July data remains historical; no candidate activation occurred.

No fresh browser, genuine scanner, PostgreSQL or deployment validation was performed for this narrow backend change. Prior browser Director MFA timeout remains unresolved and was not fixed by loosening limits. Existing Supervisor selector remains available; no new Slice 3 interface is introduced. Other delta findings and personnel qualifications remain separately scoped.
