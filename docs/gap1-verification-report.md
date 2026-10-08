# Gap #1 verification and triage ? 8 October 2026

Scope: gap #1 verification only. Gap #2 was not started. No official policy was activated, no normal records were reset, and no mail was sent externally.

## Baseline and limits

At task start, HEAD was `f7a8af8` (annex A?M), on main, with earlier QA and gap #1 changes uncommitted. Therefore there was no actual gap #1 commit parent. A detached worktree at f7a8af8 was used, without stash. Installed dependency trees, generated Filament assets, the same 34-case browser harness and backed-up fictional QA fixtures were supplied separately; baseline application source was not changed. This tests the last committed application, not an exact reconstruction of the earlier uncommitted working state. Earlier QA fixes have now been separated into commit `0701155`, and gap #1 into `bf3c5d5`, on `fix/category-3-saa-verification`. These organizational commits were created during triage, not retrospectively presented as existing history.

All runs use copied SQLite databases/private storage. Current code uses the additive corrected candidate only on its copy. Browser attempts before static routing/generated assets were corrected were stopped and excluded; they do not establish app regressions. Both final comparisons use the same corrected harness. The old-temp control uses the pre-gap commit and original machine PHP configuration.

## Executed commands and output

```text
git worktree add --detach storage/app/triage-baseline f7a8af8
Preparing worktree (detached HEAD f7a8af8)
HEAD is now at f7a8af8 annex A-M (forms and printables)

php -d sys_temp_dir=storage/framework/testing -d upload_tmp_dir=storage/framework/testing vendor/bin/phpunit --colors=never
{"tool":"phpunit","result":"passed","tests":394,"passed":394,"assertions":1920,"duration_ms":518998}

php -d sys_temp_dir=storage/framework/testing vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

php -d sys_temp_dir=storage/framework/testing vendor/bin/phpstan analyse --no-progress --memory-limit=512M
{"tool":"phpstan","result":"passed","errors":0}
```

The portable PHP executable is under `%LOCALAPPDATA%/lakbay-dev-tools/php/`; Node is the existing portable Node 24 installation. Initial Pint/PHPStan commands without the temp override failed to create PHAR/process temp files; corrected commands passed. This is configuration evidence, not concealed test failures.

Browser commands: `node node_modules/@playwright/test/cli.js test`, with `LAKBAY_QA_URL`, `LAKBAY_QA_MANIFEST`, `LAKBAY_QA_ARTIFACT_ROOT`, `QA_PHP`, `QA_PHP_INI`, `QA_PNG_FIXTURE`, copied `DB_DATABASE` and `LARAVEL_STORAGE_PATH` set per instance. PHP servers use `php -c <instance.ini> -S 127.0.0.1:<port> <ignored-router.php>` from each app's public directory. No dependencies were installed or changed in this task. Configuration/test diffs were displayed before applying them.

Original gap #1 run: **19 passed, 15 failed**, 7.7 minutes. First usable new-temp comparison: current **29 passed, 5 failed**, 9.0 minutes; commit baseline **29 passed, 5 failed**, 10.1 minutes. The failing role identities varied with timing; both retained shared-IP throttling and the child worker's original temp configuration. Final identical corrected-harness comparison: current **34 passed (8.2m)**; f7a8af8 **32 passed, 2 failed (8.1m)**. Current `.last-run.json` says passed with no failed tests. PowerShell Tee wrappers returned 1 after Node warnings were treated as stderr errors; the native full-run exit was not printed. Individual runs explicitly captured native exits, all 15 were 0. This distinction does not change the recorded test totals.

Every original failing case was rerun **individually** using `test --grep <case> --reporter=json`; all 15 returned exit 0. Individual JSON, queue logs and traces are under ignored `storage/app/verification-triage/current/individual/`. A passing individual rerun is not represented as a full-suite pass.

## Each original browser failure

| # | Original failure | Classification | Root cause and evidence | Individual retest |
|---|---|---|---|---|
| 1 | Annex B PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-01.json |
| 2 | Annex C PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-02.json |
| 3 | Annex D PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-03.json |
| 4 | Annex E PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-04.json |
| 5 | Annex F PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-05.json |
| 6 | Annex G PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-06.json |
| 7 | Annex H PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-07.json |
| 8 | Annex I PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-08.json |
| 9 | Annex J PDF download | ENVIRONMENTAL | HTTP 500, DomPDF temporary font file creation; reproduced on f7a8af8 with old temp config; passes with writable temp directory | PASS, result-09.json |
| 10 | Annex L PDF download | ENVIRONMENTAL | Same DomPDF/temp failure; old-temp baseline reproduces it, corrected-temp baseline passes | PASS, result-10.json |
| 11 | Reviewer MFA/navigation | ENVIRONMENTAL | HTTP 200 login response sends ?Too many login attempts?; original trace says retry in 17 seconds. No bad-code/credential error in that response. Shared-IP auth throttling, corrected by waiting, not disabling MFA | PASS, result-11.json |
| 12 | Records MFA/navigation | ENVIRONMENTAL | Original records trace explicitly says retry in 3 seconds. Shared-IP login throttle; role timing varies across runs. Trace evidence and individual retry establish configuration/test pacing rather than SAA resolver behavior | PASS, result-12.json |
| 13 | Annex M print | ENVIRONMENTAL | Original failure is PDF HTTP 500 (environmental). f7a8af8 additionally lacks the view-page print action (PRE-EXISTING); direct old-temp PDF request returns the same 500/font error | PASS, result-13.json |
| 14 | PNG upload/scan/preview | ENVIRONMENTAL | Original upload-file HTTP 200, _finishUpload HTTP 500 because tmpfile failed. After server fix, worker fails creating Symfony output temp file; configured worker genuinely scans and preview succeeds | PASS, result-14.json |
| 15 | Scanner failure/quarantine/retry | ENVIRONMENTAL | Original _finishUpload error is environmental. f7a8af8 also incorrectly treats a missing-scanner exit 1 as infected (PRE-EXISTING). Earlier QA fix requires actual FOUND output; current quarantine and genuine retry pass | PASS, result-15.json |

Classification describes the cause of the **original** 15 failures. A defect in older committed code is separately reported if exposed by the clean-commit comparison. No original failure was established as a gap #1 application regression. A separate production-selector regression was found and fixed below.

### Logs and stack traces

PDF, original trace and old-temp baseline:

```text
HTTP 500: ValueError: Path must not be empty
vendor/dompdf/php-font-lib/src/FontLib/BinaryStream.php:68
FontLib\BinaryStream->open(false, 'rb+')
vendor/dompdf/dompdf/src/Adapter/CPDF.php / lib/Cpdf.php:1267
$tmp_name = @tempnam($this->tmp, 'cpdf_subset_');
```

The engine is DomPDF; false is the temp-file path passed to its font-subsetting engine. Default machine temp directory reports `temp_writable=false`, `temp_created=false`; isolated temp reports both true. This reproduces on the earlier commit. No evidence indicates disk exhaustion or a missing PDF engine. Paths with spaces passed through `-d` were previously misquoted, and stale native PHP processes were found on port 8002, so those retries did not reliably replace the original runtime. Dedicated ports and explicit ini files avoid that ambiguity.

Upload, original trace:

```text
POST /livewire-4ad4a091/upload-file -> 200
POST /livewire-4ad4a091/update [_finishUpload] -> 500
TypeError: stream_get_meta_data(): Argument #1 ($stream) must be of type resource, false given
vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php
$tmpFile = tmpfile();
parent::__construct(stream_get_meta_data($tmpFile)['uri'], $this->path);
```

Worker after correcting only the web server:

```text
Symfony\Component\Process\Exception\RuntimeException
A temporary file could not be opened to write the process output:
fopen(.../php/upload-tmp\sf_proc_04.out.lock): Permission denied
vendor/symfony/process/Pipes/WindowsPipes.php:62
App\Jobs\ScanDocument ... FAIL
```

Worker with its own isolated ini:

```text
App\Jobs\ScanDocument .. RUNNING
App\Jobs\ScanDocument .. 13s DONE
Worker STOPPED Queue empty
```

The deliberate missing-scanner worker returned FAIL; the document remained pending and preview/download absent until a genuine successful retry. No scan status was manually changed. The old PNG also produced browser `InvalidStateError: The source image could not be decoded`; the isolated replacement is a valid PNG. Original fixtures remain unchanged. Network-denied external avatar requests were also observed, but did not cause the workflow assertions.

Login trace:

```text
POST /livewire-4ad4a091/update authenticate -> 200
notificationsSent
{"title":"Too many login attempts","body":"Please try again in 17 seconds."}
```

These are application rate-limit responses, not server exceptions. The browser helper waits the stated interval, regenerates TOTP and retries at most three times. Sessions/passwords/MFA secrets remain private in ignored storage. No rate-limit store was cleared and no MFA bypass was introduced.

## Separately verified gap #1 regression

`forDraft` originally followed candidate successors unconditionally. A production draft based on an eligible confirmed source therefore selected an ineligible candidate, and the new stale-baseline guard blocked submitting the source. This contradicted the activation gate.

```text
Before fix: PHPUnit tests=1 passed=0 assertions=1
Failed asserting that 3 is identical to 2.
After fix: {"tool":"phpunit","result":"passed","tests":1,"passed":1,"assertions":3,"duration_ms":1801}
```

Only this app behavior was changed: successor selection reuses `authorizeSubmission`. Production follows confirmed/effective successors; local/testing can follow candidates only under its explicit flag. The regression test covers candidate, future-confirmed and effective-confirmed successors. It uses fictional publication dates in an isolated test database; the real databases remain candidate/unpublished. No other backlog application logic was changed.

## Policy visibility, activation and rollback

Both normal/QA databases have July draft id 1, CMO24-2026 candidate id 2, corrected CMO24-2026-SAA-R1 candidate id 3; publication/effective dates are null. There is **no active CMO policy**. Local QA deliberately exposes candidates and supports experimental submissions; production cannot officially submit using them. HEI draft submission dialogs recompute on policy selection/reopening through the resolver; CHED review reads the frozen submitted version. Optional Annex C preparation remains available without making SAA mandatory for ordinary Category 3.

The original candidate-following behavior was consistent only with isolated experimentation; it was not consistent for production. The regression fix resolves that distinction. Activation requires an authorized CHED `policy.manage` user, verified publication/reference and derived +15-day effectivity. No automatic submitted-record re-evaluation occurs. The existing app has no one-click policy rollback/demotion or draft-pinning interface; prospective correction and an authorized operational suspension are required. See [activation/rollback runbook](policy-activation-runbook.md), including exact limits of policy copying and successor linkage.

## Preservation and tested restore

Checksums below hash deterministic `repr()` of SQLite tuples ordered by id; the after hash is calculated over the same original IDs. Full values are provided rather than treating counts alone as proof. Existing rows match byte/value comparisons. Submissions contain their stored status; submission_reviews/review_findings and audit_events provide review/transition history. There is no separate status_histories table. Case events/classification history and document rows were also compared.

| Database | Table | Before | After | Existing-row SHA-256 (same before/after) |
|---|---|---:|---:|---|
| database.sqlite | submissions | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | submission_requirements | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | submission_activities | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | certification_drafts | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | acknowledgments | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | submission_reviews | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | review_findings | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | audit_events | 13 | 13 | `7a10414b2403dd676f1fb743fba06c60206d4477085d174780ef58c18deba26f` |
| qa-demo.sqlite | submissions | 6 | 6 | `d0f6fea6a626346a8d1e8f616352d05a9d27417ae4228c531eddd6e5f4650728` |
| qa-demo.sqlite | submission_requirements | 69 | 69 | `dab64b8f8613d9f2dac7a79c0f0ce43f185c95c44716753e7867e68531698a58` |
| qa-demo.sqlite | submission_activities | 6 | 6 | `1ba58d0a94d481af3f6a976ab7d0e583dd9a6f48c919831c50dc69c28ce722a7` |
| qa-demo.sqlite | certification_drafts | 2 | 2 | `4bb869af5cc5326373d5aa98f62c9fa996b042a00c6aa582ff8690143402f2f6` |
| qa-demo.sqlite | acknowledgments | 2 | 2 | `b1374a269a3c7b35cb19745506f6863c668ceb955082b19039cc79af00927465` |
| qa-demo.sqlite | submission_reviews | 5 | 5 | `05b63536f51fc5d14e9e0851cf5e05a187352e5c568ec474abbdebac572773c9` |
| qa-demo.sqlite | review_findings | 2 | 2 | `17e544dda4223e783d2adc4fe586f74f85a6633403f45adb65f55e4acc5dc280` |
| qa-demo.sqlite | audit_events | 147 | 147 | `994248f90e45513ba05024e6384a5c9f2a9e9f869c31836cdb22d647029ddb03` |
| database.sqlite | case_events | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| database.sqlite | documents | 1 | 1 | `8b7ec9bd949471328c10235657c4fa191285e40536d9deea60f1f45142c88b6d` |
| database.sqlite | activity_classifications | 2 | 2 | `9618e6e7eb8b8d4171a08d3c6db096d19be4a46b2851e4cfd39be0a07bfc32c1` |
| qa-demo.sqlite | case_events | 0 | 0 | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| qa-demo.sqlite | documents | 30 | 30 | `da8ac16d7aea2b5f01f476ca78f0c9e11ce0cf7fa5a6ec9502f3fbf170f8c21c` |
| qa-demo.sqlite | activity_classifications | 19 | 19 | `2a1c3f3beb3774f6047ece0e93d2e1e524ef272a5eca13665e988de5ae374ae4` |

Backups: ignored `storage/app/gap1-backups/20261008-132544/`. Restoration used SQLite's backup API into `storage/app/verification-triage/restored-qa.sqlite`, not a normal database. Actual output: `integrity=ok`, `tables_compared=66`, `mismatches=[]`. The check compares table contents, not only totals. Original-row comparison covers all 60 id-bearing tables in both databases: zero existing rows changed/deleted. Gap #1 added one policy, 64 requirement rules and 13 templates per database. QA also has one additional runtime session since backup; historical compliance rows are unchanged. All triage browser writes went to disposable copies.

## PostgreSQL result

**BLOCKED ? not run.** `Get-Command docker,psql,pg_ctl,gh` found none; no PostgreSQL/Docker service or port 5432 listener was found. PHP has pdo_pgsql, but that extension is not a database server. No infrastructure was installed and nothing was pushed to trigger remote CI.

The existing `.github/workflows/ci.yml` already provides PostgreSQL 17 and SQLite jobs. Its PostgreSQL command excludes the six `sqlite-demo` fixture tests, which intentionally assert SQLite-only demo provisioning; SQLite retains them. Therefore this is not a verified full PostgreSQL result, and this task does not claim it is. A PostgreSQL-capable runner is needed to execute the application suite, and the SQLite-specific fixture group must be reported separately. PostgreSQL migrations, concurrency, browser integration and restore remain unverified. Merely adding a duplicate matrix would not establish success.

## Reproduction and artifacts

1. Restore the saved pre-gap QA backup to a scratch SQLite database. Copy private fixture storage and manifest; do not reseed/reset normal records. Retain real fixture MFA and scan state.
2. Create the f7a8af8 worktree, supply the unchanged installed dependencies and published Filament assets, and copy the same browser harness. Record source diff as empty. Use separate ports/databases/storage for baseline/current.
3. Copy the portable PHP ini into ignored QA storage; append absolute quoted sys_temp_dir/upload_tmp_dir pointing to a writable instance temp directory. Launch from public with `-c`. Set QA_PHP_INI for every spawned worker too.
4. Run the full browser command above. To reproduce old temp failures, launch only the disposable baseline server with its original machine ini. Do not change machine permissions or normal servers. The `/__qa_runtime` endpoint exists only in the ignored test router and exposes no credentials.
5. Reproduce reviewer/records separately with `--grep 'reviewer: MFA'` / `'records: MFA'`. Trace original failures to the server retry notice; preserve rate limiting and MFA. Reproduce each annex with `--grep 'Annex B: actual'` etc.; PNG and missing-scanner cases each have their own grep string.
6. Inspect ignored reports in `storage/app/verification-triage/{current,baseline}/final/report/index.html`, individual result JSON and old-temp control traces. They contain private fixture session information: do not commit/share raw traces or manifests publicly.

## Final results and recommendation

Actual final browser output:

```text
Current: 34 passed (8.2m)
Baseline: 2 failed; 32 passed (8.1m)
Old-temp pre-gap control: 13 failed; 2 passed (5.2m)
Individual original cases: 15 runs, each exit=0
```

The two remaining clean-commit failures are **PRE-EXISTING**:

| Failure | Evidence | Current status |
|---|---|---|
| Annex M missing view-page print action | expect Preview / print monitoring link visible fails; f7a8af8 ViewMonitoringRecord lacks header actions | Earlier QA fix, 0701155; current full + individual pass |
| Missing scanner incorrectly marked infected | Missing command worker says DONE instead of FAIL; scratch document scan_status=infected. Old ScanDocument treats every exit 1 as detection | Earlier QA fix, 0701155 requires FOUND output; current full + individual retain quarantine then genuine retry |

No new application fixes were made for either older-commit bug. Direct baseline Annex M PDF control: HTTP 500, tempFontError=true under old temp configuration. Baseline app/source diff against f7a8af8 was empty. Harness/dependency/asset additions were disposable setup only.

Build: Vite 8.3.3 succeeded, 3 modules transformed, 15.54 seconds; optional fontaine fallback warning remains informational and no dependency was added. PHP syntax lint: 340 files, errors=[]; Node syntax checks and diff whitespace checks passed.

Recommendation: **gap #2 is safe to start as a separately scoped development task**, with candidate activation still disabled. This is not production readiness: PostgreSQL and its recovery/concurrency gates remain blocked, policy publication is still unknown, and already-filed fixture branches do not prove a fresh lifecycle. No additional backlog work was started. Production approval remains blocked by publication information, PostgreSQL execution and operational policy decisions. Gap #2 remains untouched.


## Changed files by commit scope

Earlier QA work was preserved separately; no unrelated application changes were newly implemented during triage. No dependencies were changed by gap #1 or this follow-up.

Gap #1 commit `bf3c5d5`:

```text
README.md
app/Actions/Submissions/CorrectSaaPolicy.php
app/Actions/Submissions/SubmitActivity.php
app/Console/Commands/CorrectSaaPolicyCommand.php
app/Filament/Support/CertificationForms.php
app/Filament/Support/WorkflowUi.php
app/Models/PolicyVersion.php
app/Services/Compliance/PolicyRules.php
database/seeders/Cmo24PolicySeeder.php
database/seeders/QaCampaignSeeder.php
database/seeders/QaDemoScenarioSeeder.php
docs/CHED-clarifications.md
docs/CMO-conformance-report.md
docs/UAT-guide.md
docs/architecture.md
docs/decisions.md
docs/fix-backlog.md
docs/open-questions.md
docs/traceability-matrix.csv
resources/views/guidelines.blade.php
tests/Feature/Cmo24AlignmentTest.php
```

This triage changes `app/Services/Compliance/PolicyRules.php`, `tests/Feature/Cmo24AlignmentTest.php`, `tests/Browser/campaign.spec.js`, `docs/decisions.md`, `docs/UAT-guide.md`, `docs/fix-backlog.md`, and adds `docs/gap1-verification-report.md` and `docs/policy-activation-runbook.md`. The application change is limited to the reproduced gap #1 successor-selection regression.


## Final individual baseline retests and cleanup

Both remaining baseline defects were reproduced individually with the corrected environment:

```text
BASELINE_INDIVIDUAL_monitor exit=1
BASELINE_INDIVIDUAL_scanner exit=1
```

Their separate JSON/logs are `baseline/individual-monitor.json` and `baseline/individual-scanner.json`. These fail for missing print action and incorrect scanner-exit handling respectively; the current equivalents each returned exit 0. The baseline data shows the failed-scanner document as infected; current genuine retry shows clean. No production scan states were changed.

All six verified disposable PHP processes on ports 8002/8003/8004/8006 were explicitly stopped. Normal servers on 8000/8001 were left running. The detached worktree registration was removed; Git reported a Windows long-filename cleanup error, so ignored residual dependency files may remain under storage/app/triage-baseline. Reports/backups/scratch databases remain ignored and retained for review. The comparison source had no tracked application diff.

Final branch: `fix/category-3-saa-verification`; reviewed commits separate prior QA, gap #1 and triage. No push was performed. Root working-tree cleanliness is checked after the final commit rather than inferred from test success.
