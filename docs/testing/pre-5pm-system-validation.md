# Pre-5 PM system validation — 12 October 2026

## Conclusion

**The implemented HEI → CHED workflow can be demonstrated on the isolated validation clone.** Final results: **497 PHPUnit tests / 2,385 assertions passed; 38 Playwright cases passed; 15 additional five-record browser checks passed; 5 additional HTTP security/logout checks passed; 9 five-record shared-Action checks passed.** All Annex B–M PDFs were downloaded and their pages contain text. PHPStan, Pint, PHP lint and the asset build passed.

There is **no Program account or Program dashboard** in the current role seed. Program/course is activity/report information. Do not present a separate Program → HEI → CHED approval chain. CHED requests corrections or records completeness/acknowledgment; it does not approve or reject conduct. External HEI authorization remains separate.

No application defect requiring a P0/P1 code change was reproduced on the corrected environment. No application code, dependency, throttle rule or Fix 6 was changed. This is local SQLite validation, **not production sign-off**. A restoration request for the original fictional QA environment is pending; see the safety exception below.

## Environment and safeguards

- Application baseline: `741f29d`, branch `fix/complaint-procedural-history`.
- PHP 8.4.26 / Laravel 13.34.0; installed Playwright Chromium, Dompdf and genuine local ClamAV.
- Browser server: **http://127.0.0.1:8012**. Dedicated SQLite clone: `storage/app/pre5pm-validation/database.sqlite`; private files, sessions, cache, logs and writable PHP temporary directory are under the same ignored directory.
- Source QA: `database/qa-demo.sqlite`. A consistent SQLite backup was made before tests, and its private app directory was copied before testing. `before.sqlite` was restored to `restore-scratch.sqlite`; all seven recorded table counts and hashes matched.
- Normal development `.env` and its database were not migration or fixture targets. No production/shared database was used. No normal-database migrations ran.
- User approved **only the three existing pending migrations on the QA clone**: classification history, APIC knowledge and complaint procedural history. All succeeded. The existing classification configuration seeder was applied to the clone, adding proposed data without activation.
- The clone keeps its source QA encryption key so existing encrypted MFA secrets remain valid. Keys, passwords and session manifests stay in ignored storage; none are included here.
- Mail is `log`, queue processing is local, recipient addresses are fictional. Documents were not marked clean manually. CMO 24 remains inactive/candidate; publication was not invented.
- PHPUnit used `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, array mail/session/cache and separate test storage; real browser/queue checks used the clone.

### Safety exception and preservation evidence

The first Artisan-served browser process unexpectedly loaded the existing `.env.local` QA configuration. That invalid setup run touched existing **fictional QA drafts**, added an activity version and audit events. I stopped using it and moved to a directly launched PHP server with explicit clone variables and PHP configuration. It did not target the normal development database. The submitted snapshots, submission requirement rows, acknowledgments and users matched their pre-test hashes. **This does not mean the entire source QA database was untouched.** Restoration approval was requested separately; do not restore or overwrite it without that approval.

| Table | Source QA before | Source QA after initial setup run | Count/hash result |
|---|---:|---:|---|
| `users` | 30 | 30 | Same |
| `activities` | 10 | 10 | Same |
| `activity_versions` | 19 | 20 | Changed; restore approval pending |
| `submissions` | 6 | 6 | Same |
| `submission_requirements` | 69 | 69 | Same |
| `acknowledgments` | 2 | 2 | Same |
| `audit_events` | 148 | 160 | Changed; restore approval pending |

The count/hash comparison is recorded in `preservation-before.json` / `preservation-after.json`. A subsequent comparison of every source table found changes only in sessions/cache, appended activity versions/personnel/itinerary/classifications, one appended fictional case event, appended audit events, and one edited SAA draft and certification draft. Existing activity-version rows remained identical; none was rewritten. No source `documents` rows changed. The backup contains the complete database; `restore-scratch.sqlite` proves the recorded comparisons, not full deployment recovery. Both the before backup and all invalid-run evidence remain available for review.

## Exactly five new primary records

All are clearly marked `PRE5PM-20261012`, fictional Category 2 BA History heritage visits for the QA SUC institution. They use the permitted **July development draft baseline**, not an officially activated CMO candidate. Existing accounts and verified membership were reused; users/passwords were not overwritten. There are exactly five new primary activities; correction revisions are additional history, not additional activities.

| Record | Activity ID | Initial submission ID | Initial expected and verified state |
|---|---:|---:|---|
| TEST-001 | 11 | — | `draft` |
| TEST-002 | 12 | 7 | `submitted` |
| TEST-003 | 13 | 8 | `corrections_requested` |
| TEST-004 | 14 | 9 | `ready_for_acknowledgment` |
| TEST-005 | 15 | 10 | `acknowledged` |

TEST-002 was subsequently reviewed complete. TEST-003 was corrected and resubmitted through shared Actions; its latest new submission is **12**. Its earlier snapshot and requirement-row hashes stayed unchanged; the previous submission transitioned to `superseded` through the authorized workflow. No fabricated `approved`/`rejected` state or direct state update was used. One fixture setup intentionally hit the Category 1 calendar-only guard and rolled back; fixtures were adapted to Category 2. The shared-Action return value's database-default status was verified with `fresh()`, not mistaken for an application failure.

Removal: retire the **entire dedicated validation clone and ignored artifact folder** after review, keeping evidence/backups as required. No row-deletion cleanup was run against either existing database.

## Roles and workflow inspection

`database/seeders/DatabaseSeeder.php` defines nine roles. `User::canAccessPanel`, `Access`, Policies, shared Actions, protected controllers and job access checks enforce active/verified affiliation, institution/region, permissions and confidentiality. Authentication uses invitations, sessions and MFA. A role grant alone does not authorize a particular record.

| Actor | Implemented responsibility | Evidence/result |
|---|---|---|
| HEI preparer | Prepare calendars/activities/forms; upload evidence | Browser MFA/navigation passed; cannot file with this role alone |
| HEI submitter | Submit and respond; file reports | Browser passed; five new records visible; shared transitions/validation passed |
| CHED records | Regional record and correspondence support | Browser MFA/navigation passed |
| CHED reviewer | Completeness review and correction requests | Browser passed; fresh five-record review and duplicate-denial checks passed |
| CHED signatory | Record signed completeness acknowledgment | Browser passed; duplicate acknowledgment denied; Annex K retrieved |
| CHED monitor | Annex M, assistance and corrective follow-up | Browser form/navigation/14 checks/print passed |
| CHED case officer | Restricted proceedings and permitted incidents | Browser passed; administrator restricted-history denial passed |
| CHED analyst | Regional snapshots and exports | Browser navigation passed; detailed export expiry/deduplication tested by PHPUnit |
| Administrator | Institutions, invitations, policy/reference controls | Browser passed; no automatic restricted-case authority |
| Program account | Not implemented | Source inspection only; not a failed login feature |

Some existing browser lifecycle cases are idempotent and may verify previously filed/acknowledged fixtures rather than create a fresh transition every run. The five-record checks separately exercised new submission/review/correction/resubmission/acknowledgment records. Their browser checks verified display and access; fresh transition execution was through real shared Actions, not additional browser clicks. Do not describe all 15 checks as full fresh UI transitions.

## Test results and real output

| Check | Result | Evidence |
|---|---|---|
| Full serial PHPUnit | **497 passed; 2,385 assertions** | `final-phpunit.xml`, `phpunit-final.log` |
| Full Chromium Playwright suite | **38 passed (7.8 min)** | `browser-final.log`, `browser-verified/results` |
| Five records: HEI view / CHED view / other-HEI denial | **15/15 passed**, plus 8 submission pages HTTP 200 | `five-record-browser-results.json`, screenshots |
| Fresh shared-Action behavior | **9/9 passed** | `workflow-results.json` |
| Additional HTTP checks in authenticated browser context | **5/5 passed** | `security-browser-results.json` |
| PHPStan | Passed, zero errors | Direct installed PHPStan executable |
| Pint | Passed | Direct installed Pint executable, read-only check |
| PHP syntax lint | **362 files, zero failures** | All PHP files under app/database/config/routes/tests |
| Vite build | Passed | Existing installed Vite; optional fontaine notice only |
| Backup scratch restore | Counts/hashes match | `restore-scratch.sqlite`, preservation JSON |
| PostgreSQL/container tests | **Not tested: docker and psql unavailable** | Command availability check; no infrastructure installed |

Exact final PHPUnit output:

```json
{"tool":"phpunit","result":"passed","tests":497,"passed":497,"assertions":2385,"duration_ms":432372}
```

```text
38 passed (7.8m)
Five-record browser checks: 15/15 passed (HEI view, CHED view, other-HEI denial); 8 submission pages returned 200.
Additional HTTP browser-context security checks: 5/5 passed.
PHP lint: 362 files checked; 0 failures
{"tool":"phpstan","result":"passed","errors":0}
{"tool":"pint","result":"passed"}
vite v8.3.3 — build completed
```

The browser command's PowerShell wrapper returned exit 1 because it captured native stderr warnings; Playwright's own report has **38 expected, zero unexpected failures**. Retain the actual log and inspect its JSON stats rather than concealing this wrapper result. Early invalid setup runs (PDF temp failures, source-env mismatch, clone-key mismatch, overlapping fake storage and a missing isolated compiled-view directory) are excluded from the final passing baseline, but retained as evidence.

Commands used, with the installed local PHP/Node executables and writable `php.ini`:

```text
php -c storage/app/pre5pm-validation/php.ini vendor/phpunit/phpunit/phpunit --colors=never --log-junit storage/app/pre5pm-validation/final-phpunit.xml
node node_modules/@playwright/test/cli.js test --reporter=list,json
php -c storage/app/pre5pm-validation/php.ini vendor/phpstan/phpstan/phpstan analyse --no-progress
php -c storage/app/pre5pm-validation/php.ini vendor/laravel/pint/builds/pint --test
node node_modules/vite/bin/vite.js build
```

Browser execution explicitly supplies the clone DB/storage/key and log mail variables, `LAKBAY_QA_URL=http://127.0.0.1:8012`, the clone manifest, writable `QA_PHP_INI`, and an ignored artifact root. Launch PHP directly from `public/` with `-c ... -S 127.0.0.1:8012 .../server.php` so the serving process actually uses that INI. Do not copy the above shorthand into a normal environment and assume isolation.

## Forms, uploads, security and history

Browser checked actual Annex B–J/L previews and PDF downloads; Annex M's 14 answers/save/print; existing correction/acknowledgment UI; ROC/incident/change filing visibility; saved preparations versus frozen filed output; all nine role menus; narrow public layout and keyboard navigation. Annex K's preserved issued preparation was separately downloaded with a signatory browser session and verified as a PDF. Rendered first pages are retained for review; extraction proves text/pages, **not pixel-perfect conformance or a human sign-off on every long-content continuation**.

| PDF | Pages | Inspection |
|---|---:|---|
| annex-B.pdf | 1 | Text present on every page |
| annex-C.pdf | 1 | Text present on every page |
| annex-D.pdf | 2 | Text present on every page |
| annex-E.pdf | 1 | Text present on every page |
| annex-F.pdf | 2 | Text present on every page |
| annex-G.pdf | 1 | Text present on every page |
| annex-H.pdf | 1 | Text present on every page |
| annex-I.pdf | 1 | Text present on every page |
| annex-J.pdf | 1 | Text present on every page |
| annex-K.pdf | 1 | Text present on every page |
| annex-L.pdf | 1 | Text present on every page |
| annex-M.pdf | 2 | Text present on every page |

Upload browser tests used a real PNG and genuine scan, with pending quarantine before authorized preview. Scanner-failure test used a nonexistent executable, verified quarantine, then retried using genuine local ClamAV. No bypass or mocked clean status was used for these browser checks. Signed fictional PDFs remained distinct from generated unsigned preparations. PHPUnit covers invalid uploads, tampered bytes, quarantine, ownership/jurisdiction, inactive/unverified access, permissions, deadline and transition boundaries, audit/notification behavior, duplicate acknowledgment and report revision counting. These are automated tests, not a claim that every failure mode was reproduced manually.

Additional HTTP browser-context checks: nonexistent ID returned 404; logout without CSRF returned 419 and retained authentication; valid-token logout redirected; dashboard afterward redirected to login. No aggressive penetration testing was performed. Notification mail stayed local; no real delivery/SMTP claim is made.

## Issues and triage

| ID | Module | Issue | Severity | Reproduction steps | Recommended fix / outcome |
|---|---|---|---|---|---|
| QA-ENV-01 | MFA / cloned configuration | Source QA encrypted MFA data cannot decrypt with `.env.qa`'s different key | P1 environment | Clone DB, start with the other QA key, sign in: `DecryptException: The MAC is invalid`, Encrypter.php:191 | Preserve source QA key in clone config; corrected, real MFA/browser passed |
| QA-ENV-02 | PDF / server process | Artisan-launched child did not use the explicit writable-temp INI | P0 environment | Download Annex B: HTTP 500, `ValueError: Path must not be empty`, FontLib BinaryStream.php:68, Cpdf.php:1267 | Launch direct PHP server with explicit INI and clone env; B–M downloads passed |
| QA-ENV-03 | PHPUnit execution | Concurrent suites shared fake storage and removed each other's files | P2 test harness | Overlap suites: missing frozen originals, missing export size/quarantine files | One serial suite with isolated test storage; 497 passed; no production code change |
| QA-ENV-04 | Isolated test bootstrap | New storage root lacked `framework/views` | P2 test harness | Start tests with unprepared root: `Please provide a valid cache path` | Create normal runtime subdirectories; final serial run passed |
| QA-SAFE-01 | QA environment preservation | First server targeted existing fictional QA instead of intended clone | P1 safety | Compare original QA hashes to pre-test backup | Source submitted records unchanged; draft/history drift recorded. Restore approval pending; do not silently overwrite |
| QA-SCOPE-01 | Accounts | Requested Program account does not exist | Scope decision | Inspect role seed and panels | Demonstrate actual HEI/CHED roles; do not invent permissions/accounts |
| QA-DEP-01 | Database deployment | Normal development DB has three pending existing migrations; PostgreSQL unverified | Deployment blocker | Compare migration status / tooling availability | Use migrated clone for demo; obtain approval and backup before any normal upgrade; validate PostgreSQL separately |

No app-level P0/P1 repair was required on the corrected test environment. Existing unresolved CMO questions, candidate activation and FIX-06 remain open, not silently “fixed” by QA. Shared-IP login throttling can cause legitimate tests to wait; one existing bounded retry waited 8 seconds and succeeded. Production limits were not loosened.

## Demonstration instructions

1. Use **http://127.0.0.1:8012**, the isolated clone; keep the directly configured PHP server running. Credentials remain in the ignored clone manifest; do not paste them into slides or Git.
2. Sign in as HEI preparer/submitter with the existing authenticator MFA. Open **Activities**, search `PRE5PM-20261012`, and inspect TEST-001 through TEST-005. View submitted requirements and feedback in the corresponding submission records.
3. Sign in as CHED reviewer. View TEST-002's completed review and TEST-003's correction history/latest submission **12**. If demonstrating another review, use supported actions and remarks; never overwrite a filed revision.
4. Sign in as CHED signatory. TEST-004 is ready for acknowledgment, and TEST-005 is acknowledged. Use the existing calendar campaign record for an Annex K example; Category 2 activity acknowledgments are not the CLOCA/SAA Annex K preparation context.
5. Sign in as monitor; inspect the saved Annex M record and print. Use `browser-verified/annex-B.pdf` through `annex-M.pdf` as backup illustrations.
6. Use another HEI: the five records must be inaccessible. Administrator access must not expose restricted case history.

The additional logout check ended the stored HEI browser session; a subsequent browser run must sign in again. It did not disable MFA or reset passwords.

## Remaining readiness work

- Resolve the pending original-QA restoration request; verify full database comparison afterward if approved.
- Validate PostgreSQL migrations, workflow and restore in approved staging; local SQLite success is insufficient.
- Confirm publication/effectivity, CHED interpretations, retention, hosting/TLS, SMTP, scanner signature updates, continuous workers/scheduler and access review. No production deployment was attempted.
- Conduct stakeholder UAT and human visual inspection of all long-content annex outputs. Current PDF extraction/render checks do not replace that review.
- Normal development may still need the existing additive migrations; this task did not apply them there.
- Stop here; do not start Fix 6 without the user's next instruction.
