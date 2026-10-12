# HEI & CHED Application — Complete QA Testing Walkthrough

This guide describes the inspected LAKBAY repository on 7 October 2026. It is a testing document, not CHED policy approval. Use fictional records in the isolated local demo. Record your own results; automated coverage does not mean a browser test has passed.

## Gap #1 verification follow-up

See [executed baseline comparison and all 15 failure classifications](gap1-verification-report.md) and [activation/rollback runbook](policy-activation-runbook.md). Current browser: 34/34; last committed baseline: 32 passed / 2 pre-existing failures; full PHPUnit: 394/394. Use isolated writable temp configuration for both the PHP web server and scan worker, a valid fixture PNG, and the rate-limit-aware login helper. PostgreSQL remains blocked; CMO 24 remains candidate. Existing filed branches are preservation checks, not a fresh lifecycle.

## Executed QA campaign: 8 October 2026

Campaign `QA-CAMPAIGN-20261008` ran against the isolated `database/qa-demo.sqlite` database, private QA storage and `http://127.0.0.1:8001`. The normal application database was not used. All campaign people and institutions are fictional. Mail used local logging; no external recipient was contacted. CMO 24 remains **candidate**, with no publication date or reference invented.

### Verified results

The initial clean PHPUnit baseline passed **375 tests / 1,779 assertions**. The final full run after all fixes passed **381 tests / 1,824 assertions** (288 seconds).

- Chromium: the final complete run passed **34 browser scenarios** (4.9 minutes), including draft editing, preserved submitted copies and actual scanner failure/recovery. No final browser scenario failed or was skipped.
- PHPStan: **zero errors**. Pint formatted the changed PHP files. The Vite production asset build passed; its optional `fontaine` fallback warning does not stop the build.
- Security/staging follow-up: clean npm installation/audit, quoting and concurrently smoke checks, the build, six affected fixture tests (**33 assertions**), PHPStan/Pint and one public-page Chromium smoke check passed. YAML syntax and the PostgreSQL test-group selection were checked; **375 tests are eligible for PostgreSQL**, but were not executed against PostgreSQL on this machine. Browser HTML reports show the latest run; the full campaign totals above refer to the earlier verified 34-scenario run.
- PDFs: all **12 Annex B–M outputs** downloaded through authenticated requests, contained fictional field values and were rendered and visually inspected. B/C/E/G/H/I/J/K/L used one A4 page; F/M used two. D used two pages with populated remarks, continuing without losing its final criteria or signatures. Prepared documents and uploaded signed originals remain distinct.
- Real scanning: the browser uploaded a PNG, observed pending quarantine, ran the actual queue worker with local ClamAV, then retrieved the authorized image preview after a clean scan. No database flag was manually changed to clean.
- Recovery: the pre-campaign SQLite backup was restored to a separate ignored file and passed `integrity_check`. The active QA database also passed. Original rows checked include 20 users, 4 institutions, 2 policy versions, 25 templates, 23 documents, 16 activity versions, 4 submissions and existing completion/incident reports. The broader comparison checked **522 original rows across 55 ID-bearing tables**, excluding infrastructure queues/caches/sessions. **No checked original row changed. All 48 backed-up private files retained their hashes.** This is a SQLite/database-and-file recovery check, not a production PostgreSQL or encryption-key disaster-recovery exercise.

### Defects and retests

| ID / severity | Role and reproduction | Expected / actual before fix | Fix and retest |
| --- | --- | --- | --- |
| QA-01 / High | CHED records officer opens Document library; monitor opens Technical assistance; case officer opens Case records. | Authorized role can create; Filament's generic create-policy response hid the forms despite the resource's permission check. | Shared `ScopedResource` now supplies the permission-and-panel authorization response. Mounted-form tests pass for all three roles; analyst, unverified, inactive, missing-region and non-confidential administrator cases remain denied. |
| QA-02 / High | HEI opens a calendar, chooses Submit/resubmit, selects CMO 24 and attaches the displayed evidence files. | Selections reach submission; dynamic document/retention fields were absent from Livewire state and visually selected files were dropped. | Changing policy initializes the keyed evidence schema. Browser resubmission created revision 2, superseding the correction request; initialization regression passes. |
| QA-03 / Medium | Submit an otherwise populated package without required evidence. | A visible explanation; domain errors used keys outside the mounted form and the modal silently remained open. | Domain validation is mapped into a visible submission-check message. Regression verifies the error and no partial submission. |
| QA-04 / High | Upload a fictional PNG, run a QA worker with a missing scanner executable on Windows. | Execution failure stays pending and retryable; Windows exit code 1 was mistaken for a positive infection. | An infection now requires exit code 1 **and** ClamAV's `FOUND` result. Missing/unavailable scanners and hash mismatches remain quarantined; five focused failure cases pass. The final browser run verifies pending/private state after failure and preview after a genuine successful retry. The deliberately misclassified pre-fix campaign upload remains private as diagnostic evidence. |

The pre-existing Annex M detail-page print-action change was preserved and exercised through the monitor's real creation form, saved record and two-page PDF.

### Coverage and limits

| Planned area | Recorded outcome |
| --- | --- |
| Public site / anonymous retrieval | Browser passes for home, guidelines, FAQ and anonymous protected-document redirect; template/private access regressions pass. |
| Identity and roles | Real MFA enrollment/login used for campaign accounts. Browser checks HEI preparer/submitter and all seven existing CHED roles. Visible sidebar destinations return HTTP 200. Inactive/unverified, foreign-panel and invitation rules have PHPUnit coverage; full browser invitation acceptance/password-reset journeys remain unverified. |
| Fictional scenarios / classification | Existing demo covers SUC, LUC, private and another region; campaign adds independently identified accounts and Category 1 calendar/activity/report records. Category 2/3 treatment, costs, invitations, travel and PIC boundaries pass domain tests. A complete Category 2/3 browser filing lifecycle remains a pilot task. |
| Annual / pre-activity submission | Separate stage, category matrix, retained/requested evidence, signatures, contracts and snapshot tests pass. Initial campaign intake was seeded through the shared Action; **correction, revised intake, completeness review and signed Annex K recording were exercised in the browser**. |
| Annex preparation / outputs | B–M authenticated HTML/PDF checks pass. M's real form fills all 14 checks and saves/prints. B/C/F browser edits preserve submitted preparations. D/E/J and G/H/I field validation, long-content pagination and snapshot behavior have template-specific PHPUnit coverage; not every editor interaction was repeated in Chromium. A/N are reference material. |
| Reports | Browser files a signed ROC, initial incident notice and immediate change notice, then verifies preparation editing/duplicate filing actions disappear. Signed incident filings, supplements, incident links, actual conclusion reconciliation, personnel/itinerary changes and late intake have domain regressions. The independent dummy ROC/incident examples are not an assertion that HEI narratives agree. |
| Deadlines / policy | July history, 48-hour incident boundaries, 72-hour completion, working days, publication transition/effectivity and unresolved RCD anchor regressions pass. No policy interpretation was resolved by the QA campaign. |
| Monitoring / corrective follow-up | Browser M creation/output passes. Technical assistance, corrective response-before-closure, criteria validation and regional authorization regressions pass. Full corrective follow-up browser journey remains unverified. |
| Cases / clearance | Complaint/order requirements, confidentiality, routing, advance filing and one receipt-based reconsideration regressions pass. Case navigation is checked; complete external clearance/case browser journeys remain unverified. |
| Isolation / quarantine | Other HEI is denied all campaign B–J preparation/output URLs. Ownership/jurisdiction, selectors, exports, restricted cases and unauthorized actions pass regression tests. Genuine upload/scan/preview passes; controlled worker/scanner failure and retry are tested separately. |
| Reports / exports / notifications | Frozen regional reports, no revision double-counting, dispatch/receipt, authorization on export execution and reminder deduplication regressions pass. Actual live CSV/PDF export queue and all notification failure/retry journeys remain unverified. |
| Concurrency / rollback | Duplicate/stale actions, evidence failures and no partial records pass SQLite tests. PostgreSQL concurrency/deadlocks and multi-user load are unverified. |
| Responsive / keyboard | Desktop panels and a 390-pixel public viewport with keyboard focus pass browser checks. Full mobile panel walkthrough, screen-reader/WCAG audit and multiple-browser coverage remain unverified. |
| Network / operational failures | Scanner failure exercised locally. Browser offline/interrupted uploads, expired-session recovery and database outage drills remain unverified. |
| PostgreSQL / containers | **Blocked:** `psql` and Docker are absent from PATH and checked standard installation locations. The follow-up prepares a separate loopback-only PostgreSQL 17 staging service on port 5433, independent storage and environment example; YAML syntax passes. See the staging section in `docs/deployment.md`. The six SQLite-only demo tests are excluded only from the PostgreSQL CI job. No infrastructure was installed; PostgreSQL migration, workflow, container and restore results are not claimed. |
| Dependency review | The campaign initially reported two critical **development-tooling** advisories through `concurrently` / `shell-quote` (GHSA-pqg4-j6r4-53mv). The approved follow-up pins the nested `shell-quote` to patched 1.11.0 while retaining concurrently 10. A new npm audit reports **zero vulnerabilities**; the asset build passes. `npm audit fix --force` was not used. Production dependency/host scanning remains a rollout check. |

### Repeat the campaign safely

1. Use the existing isolated `.env.local` setup from this guide, and back up `database/qa-demo.sqlite` plus its private storage before adding fixtures. Do not run fresh/reset migrations or reseed the normal database.
2. For a new isolated demo, prepare and **genuinely scan** the existing QA fixtures first. Then run `php artisan db:seed --env=local --class=QaCampaignSeeder --no-interaction`. The seeder refuses normal/production databases and does not overwrite existing account passwords. It requires the existing `QA-SUC` demo institution and clean required evidence.
3. Start `php artisan serve --env=local --host=127.0.0.1 --port=8001 --no-interaction`. Use `npm ci`, then `npx playwright install chromium` if the approved development browser is absent. Run `npm run test:browser`. On this workstation Node/PHP portable-tool directories must be on PATH, or set `QA_PHP` to the portable PHP executable.
4. Campaign passwords and enrolled authenticator secrets are in **ignored local** `storage/app/qa-demo/app/qa-campaign/manifest.json`. Use the HEI or CHED account matching the role; retain MFA. Never copy this file, auth cookies, traces, QR/secret material or uploaded evidence into Git or shared reports.
5. Manually verify QA-01: sign in as `campaign.records@example.test`, `campaign.monitor@example.test` or `campaign.case@example.test`; the permitted creation forms should open. Sign in as analyst to confirm the corresponding creation actions are absent.
6. Manually verify QA-02/03: on a **new correction request**, open the HEI calendar's Submit/resubmit form, select the same policy and request, then choose files for each requirement. Submit without a required file first: a red explanation should appear and no new revision should exist. Supply the missing file and submit: CHED must see the new revision and the original must remain superseded with unchanged preserved outputs.
7. For Annex K, reviewer marks the revised package complete; signatory prepares/prints the letter, attaches an already scanned fictional signed acknowledgment and records it. HEI sees Signed acknowledgment and the preserved preparation. This does not authorize the activity.
8. For Annex M, monitor creates a new record with 14 checks and findings, saves it, then uses Preview/print monitoring. Inspect both pages and optional blank signatures. Use the matching HEI report preparations for ROC/initial incident/immediate change filing; after filing, preparation edit/file actions disappear.

Browser reruns **reuse completed immutable campaign transitions** rather than reopening or overwriting signed history. They still create fresh monitoring records and uploads. To repeat the complete transition sequence from its initial state, use another backed-up isolated QA database/campaign; do not reset the live campaign's submitted records. Browser artifacts, PDFs, screenshots, queue output and the recovery report live under ignored `storage/app/qa-campaign-browser/`; the HTML report is `report/index.html`. Backups are under ignored `storage/app/backups/qa-campaign-20261008/`.

This is a verified development QA pass with explicit remaining pilot checks. It is **not a guarantee of every possible bug, complete regulatory coverage or production readiness**.

## 1. What you are testing

### Annex M walkthrough

Sign in as a regional CHED monitor with MFA. Open **Monitoring & support -> Monitoring visits -> New monitoring record**. Fill period/date/type, institution/optional activity and HEI respondent/position. Complete all 14 Yes/No responses, confirm/edit the template verification examples and enter remarks. Record all four findings sections and optional Prepared/Reviewed/Noted names/positions and HEI conforme name/date. Save; verify the new record's **Preview / print monitoring (Annex M)** action.

Check both normal A4 pages: monitoring/profile details, first ten criteria, final four criteria, findings, three designation blocks and HEI conforme. Long verification/findings must continue without omitted text. Blank optional signatories must remain blank; typed names/dates must not establish consent, sanctions or activity authorization. The operational overall rating must not become an automatic score.

Confirm later HEI/profile/officer/configuration changes do not alter the preserved output, duplicate/missing/unknown criteria are rejected without partial records, HEIs/other regions/missing-MFA users cannot retrieve this CHED output, and old records receive no reconstructed payload. Sign externally; retain/upload the signed Monitoring and Evaluation PDF through real private scanning and keep its reference with the monitoring record. Verify the existing corrective-action follow-up still works. PostgreSQL/browser pilot checks remain separate.

### Annex L walkthrough

As a verified HEI preparer/submitter with MFA, open **Activity plans -> activity actions -> Prepare / print consent (Annex L)**. Check the displayed activity revision, title, Manila dates, organizing office, venue and HEI. Enter fictional student/program/year/student-number/contact and guardian/relationship/emergency-contact details, plus optional dates and letterhead. Preview and download; check all student, guardian and privacy-notice blocks, with blank signature spaces. Test a second copy with every personal field blank for paper completion.

The preparation must not create student/guardian records, signed consent, submissions or activity authorization. Validation failures must show errors without flashing personal values into the session. Other HEIs, CHED-only accounts, unverified members and missing-MFA accounts must be blocked. A foreign/quarantined/tampered letterhead must be blocked. An older activity-version URL must retain its original planned facts; profile information is current at generation, not a historical consent snapshot.

Print/sign externally and retain/upload the signed PDF as sensitive **Sample Consent Form** evidence when required. Download before leaving; response-only entries cannot be reopened from a saved draft. Browser accessibility and real scan/upload checks require a manual walkthrough. Do not interpret optional guardian fields or typed signing dates as a policy applicability decision or electronic consent.

### Annex K walkthrough

Use a fictional calendar submission with actual clean signed CLOCA/SAA PDFs in the isolated demo. As a CHED reviewer, request corrections if needed, then mark the exact corrected revision complete. As the regional signatory, open that submission's **Prepare letter (Annex K)** action and enter the recipient, position, address, salutation, date/reference, Director and CHED letterhead text. Inspect the submitted originals before checking signing/notarization confirmation.

Save and select **Preview / print letter (Annex K)**. Verify the receipt date matches this revision's receipt in Manila, the HEI name and grouped program/planned dates are correct, counts exclude non-students, all template paragraphs print, and no signature is invented. Preparation and download must leave the submission ready for acknowledgment and activity authorization unchanged. Print/sign externally; upload the signed acknowledgment PDF, wait for a real clean scan, and record it using the saved Annex K particulars with preservation checked.

Verify **Preserved letter preparation (Annex K)** and **Signed acknowledgment** are distinct, HEI can retrieve its issued copy, another HEI/region cannot, CHED draft retrieval requires signatory permission and MFA, and quarantined/tampered originals block preparation. Later HEI/profile/plan changes must not change the issued payload. Older/manual letters have their signed original but no reconstructed preparation. Counts are participations rather than unique students; record CHED's confirmation before treating that mapping as final.

LAKBAY is one PHP 8.4 / Laravel 13 application. Filament 5 and Livewire 4 render the HEI and CHED panels; Blade/Tailwind/Vite render the public site. There is no separate backend API service or SPA server. SQLite is local; PostgreSQL 17 is the deployment target. Login uses Laravel sessions, invitation-based membership, Spatie permissions, Policies, and mandatory authenticator-app MFA. ClamAV scans private files. Database queues deliver mail and generate exports. DomPDF produces regional-report PDFs.

| Interface | Demo URL | What it does |
| --- | --- | --- |
| Public home | http://127.0.0.1:8001/ | Portal links and workflow guidance |
| Guidelines/templates | http://127.0.0.1:8001/guidelines | Policy guidance and eligible approved blank templates |
| FAQ | http://127.0.0.1:8001/faq | Expandable help |
| HEI login/workspace | http://127.0.0.1:8001/hei/login and /hei/overview | Prepare, submit/respond, report, follow up |
| CHED login/workspace | http://127.0.0.1:8001/ched/login and /ched/overview | Review, acknowledge, monitor, report, administration |

CMO24-2026 is a **candidate**, with no verified publication/effectivity. The demo permits candidate testing locally; it does not activate the policy. July-draft records use their original rules. CMO incidents use **48 elapsed hours**, ROC uses **72 hours from actual conclusion**, and the CMO formal change-report anchor is **unresolved**. Category 3 does not have a universal confirmed three-working-day deadline. Acknowledgment means receipt/completeness, never authorization.

Actual submission lifecycle: `submitted` → `corrections_requested` → a new revision (old becomes `superseded`) → `ready_for_acknowledgment` → `acknowledged`. `under_review` is recognized but there is no separate start-review button. There is no ordinary submission Approve/Reject button. Activity plans have immutable versions; Revising creates a new version even after submission. Historical evidence cannot be overwritten or deleted through the interface.

## Annex G preparation and signed filing

Use fictional records in the isolated QA environment. Ensure the additive Annex G migrations are applied there without resetting/reseeding existing records.

1. As an HEI submitter, open **3. After the activity -> Prepare ROC (Annex G) -> New ROC draft**. Choose the institution and exact activity-plan revision. Check prefilled counts/venue; enter actual student/other totals, actual conclusion in Manila time, status, summary/outcome and authorized official/position.
2. Save a partial draft without summary/signatory values. Preview must list missing fields; PDF download and signed filing must be blocked. Saving/printing must not change the activity's completion status or create a received report.
3. Complete the draft. For **Conducted with changes**, supply a brief explanation; for incidents, supply a minimized explanation and select applicable incident reports/notices for the same activity. Do not use names or medical details in the ROC.
4. Preview and download. Compare all five sections with Annex G, including HEI type, proposed dates, total actual participants, office, PIC/APIC, implementation checkbox, outcome, incidents, certification and signature block. Proposed dates must not become the actual-conclusion deadline anchor. Blank issuance dates must stay blank. Test a long summary for complete pagination.
5. Sign externally, upload the fictional signed PDF as a **Completion report**, and wait for a genuine clean scan. Use **File signed ROC** on the preparation record. Pending scans, wrong document types, unsigned files and images must not be accepted. An authorized institutional submitter is required.
6. Confirm the report appears under **Completion reports**, with the existing policy-versioned 72-hour deadline and correct late flag. Actual conclusion must match any previously recorded value. **Preserved ROC (Annex G)** must retain the filed values; the preparation's edit/file actions should now be hidden.
7. Try another institution and a CHED user outside the region: direct URLs and records must be denied/absent. Ordinary CHED reviewers can read non-incident preparations; incident-bearing records/outputs require incident-view permission. CHED cannot prepare/edit/file.
8. Confirm old manually filed reports still use their signed original and cannot produce a newly reconstructed Annex G. Printing a preserved preparation must not create a new signed document or receipt.

Automated feature/Livewire coverage and PDF inspection are separate from this browser walkthrough. Record actual browser results; do not mark these steps passed based only on automated tests.

## Annex H preparation and incident filing

Use fictional information in the isolated QA environment.

1. Open **HEI -> Prepare incident (Annex H) -> New incident draft**, select an activity revision and fill incident date/time, exact location/type, persons/roles/remarks, factual description, actions and current status. Save without official/signature/supporting information. No incident receipt should exist until filing.
2. Preview must list missing print fields; final PDF should be blocked. Use **Send initial notice / file signed report** with no signed document selected. Verify initial_notice intake, occurrence-based policy deadline, late flag and preserved data. Preparation edit/file actions must now be hidden.
3. Create a new draft selecting that incident as the supplement. It must retain the original occurrence time. Enter updated facts, supporting details, official/position; unknown issuance dates stay blank. Preview/download and compare all eight sections against Annex H, including proposed dates separately from incident occurrence.
4. Upload the signed Incident report PDF through normal private scanning. Select it in the filing dialog. Signed filing must reject pending scans, unsigned files, wrong types/images and non-sensitive originals. Signed filing requires complete print fields; visible dialog errors should explain failures.
5. Confirm the signed supplement links to the original incident and retains its due date/policy parameters. Original persons/narratives must remain unchanged. New supporting uploads must not change frozen references; obtain attachments separately through protected downloads.
6. Test long narratives/person tables for pagination. A changed/quarantined letterhead must block rendering, never bypass scanning. Supporting-file quarantine must not delay an initial notice.
7. Another HEI, a CHED reviewer without incident-view permission, a different-region CHED officer and users without MFA must not access the protected records/outputs. CHED users with jurisdiction and incident permission may read/print but cannot prepare/edit/file.
8. Verify historical manual reports remain retrievable as signed originals; a legacy report without a payload cannot be reconstructed into Annex H.

These are manual browser checks; automated results and PDF inspection do not establish completion of this walkthrough or PostgreSQL readiness.

## Annex I preparation and change reporting

Use fictional information in the isolated QA environment.

1. Revise an activity plan, changing venue, PIC/APIC, dates or counts. Open **HEI -> Prepare changes (Annex I) -> New change draft** and select original/later revisions of that same activity. Reverse revisions or another activity must be rejected.
2. Select the original electronic submission receipt. Only receipts containing that original revision are eligible, including historical receipts. If it was submitted externally, leave the electronic selector blank and supply an external original submission date/reference. A draft creation date must never appear as the original submission date.
3. Enter when the change occurred, reason, other particulars and supporting details. Save without signatories. Preview must show missing print fields; final PDF/signed filing must be blocked. Saving alone must not create a received change report.
4. Use **Send immediate notice / file signed report** with no document selected. Check immediate_notice intake, preserved data and notification time. Under CMO 24, formal due date/late status remain unset because the anchor is unresolved. Historical draft records retain their own deadline.
5. Create a signed follow-up preparation selecting that notice with the same revision pair, original submission reference and change time. Complete Prepared/Reviewed/Noted names/positions, preparer contact/email and supporting details.
6. Preview/download and compare all four sections against Annex I, including previous/revised schedule, venue, personnel, total participants, Other particulars and signature blocks. Long cells and narratives must paginate completely.
7. Upload a signed **Change report** PDF and wait for a real clean scan, then file it from the preparation. Wrong types, images, unsigned/quarantined originals or quarantined support must fail with visible dialog errors. Confidential incident evidence belongs in Annex H.
8. Confirm the signed follow-up retains original notice timing/policy, while the initial notice and earlier activity revisions remain unchanged. Filed drafts must hide edit/file actions and reject reuse. Retrieve the frozen unsigned copy from **Change reports -> Preserved change (Annex I)** and the signed original separately.
9. Verify other institutions, unverified members, CHED outside the region and users without MFA cannot access outputs. Regional CHED may read/print but cannot create/edit/file an HEI preparation. Changed/quarantined letterheads must block rendering. Legacy reports without an Annex I payload must not be reconstructed.

Automated tests and inspected PDFs do not establish a completed browser walkthrough or PostgreSQL readiness.

## Annex J preparation and detailed itinerary

Use fictional records in the isolated QA environment.

1. Open **HEI -> Activity plans -> New activity / Revise -> Detailed itinerary (Annex J)**. Add at least two stops: Manila date/time, activity, location, responsible person and optional DOT information. Enter Prepared/Reviewed/Noted signatories and preparer contact/email. Basic information comes from the HEI profile/activity plan.
2. Save and preview. Check every basic-information row and all six itinerary columns. Test an early-morning stop whose UTC storage date differs from its Manila date; print and edit must still show the Manila date/time.
3. Remove signatory details or explicitly clear rows, then save. Preview should show missing print fields and final PDF should be blocked. Missing required details within an entered row must prevent save. Blank inapplicable DOT information must not block printing.
4. Reorder/change rows and signatories using Revise. Sequence should follow the displayed row order; previous versions must retain their original rows/times. Reopen the editor to confirm saved rows are populated rather than silently cleared.
5. Download a complete PDF and compare Annex J's two sections, six columns and three signatory blocks. Normal output should fit on A4; long rows/many stops must paginate completely with repeated headings. Printing must not create signed evidence or a submission.
6. Sign externally, upload the signed **Detailed Itinerary** PDF through genuine private scanning and attach it to the applicable evidence checklist. Submit the activity/calendar package. Use **Preserved itineraries (Annex J)** to retrieve its exact submitted preparation.
7. Request corrections as CHED, revise itinerary/profile information as HEI and resubmit. The original preserved itinerary must retain its original header, rows, signatories and template reference; the corrected submission should contain the revised version.
8. Test another institution, unverified membership, different-region CHED and missing MFA. Protected outputs must be denied/absent. Regional CHED may read/print but cannot prepare/edit itineraries. A requested version absent from the submission must return not found.
9. Quarantined/tampered letterheads must block output. Legacy submissions without an Annex J payload must retain original uploaded evidence rather than reconstructing current data.

Record actual browser outcomes; automated/PDF checks do not prove browser or PostgreSQL readiness.

## 2. Prepare the workstation

### Existing Windows workstation: one-time terminal setup

Open PowerShell in the LAKBAY folder. Run this block in **each new terminal**:

```powershell
Set-Location -LiteralPath 'C:\Users\LocalUser.LAPTOP-MGEP8F46\OneDrive - COMMISSION ON HIGHER EDUCATION\Desktop\LAKBAY'
$env:Path = "$env:LOCALAPPDATA\lakbay-dev-tools\php;$env:LOCALAPPDATA\lakbay-dev-tools\node-v24.21.0-win-x64;" + $env:Path
php --version
node --version
```

Expected: PHP 8.4 and Node 24. If unavailable, check the portable directories or install those versions and add them to PATH. Dependency commands below use the existing portable Composer file. On a different machine with Composer on PATH, use `composer` instead of `php "$env:LOCALAPPDATA\lakbay-dev-tools\composer.phar"`.

### Dependency and fresh-install commands

Do not replace the current `.env`, rotate its key, or reset `database/database.sqlite`. These commands distinguish a genuinely fresh clone from the separate demo.

| Exact command | Purpose | Expected result | If it fails |
| --- | --- | --- | --- |
| `php "$env:LOCALAPPDATA\lakbay-dev-tools\composer.phar" install --no-interaction --prefer-dist` | Install locked PHP dependencies | Vendor installation/package discovery completes | Check PHP extensions, network, Composer file; do not run update |
| `npm ci --ignore-scripts` | Install locked frontend dependencies | node_modules installed | Check Node 24, network and lockfile |
| `if (-not (Test-Path .env)) { Copy-Item .env.example .env; php artisan key:generate }` | Initialize only a fresh clone | New .env/key only when absent | Never overwrite an existing key |
| `php artisan lakbay:demo --reset --scanner="$env:LOCALAPPDATA\lakbay-dev-tools\clamav-1.4.6\clamav-1.4.6.win.x64\clamscan.exe" --scanner-database="$env:LOCALAPPDATA\lakbay-dev-tools\clamav-1.4.6\clamav-1.4.6.win.x64\database"` | Create the isolated environment, migrate/reset its DB, seed fictional accounts/plans, upload quarantined fixtures | 20 accounts, 9 plans; normal DB untouched | Check scanner paths and local/uncached configuration. Never relax isolation guards |
| `php "$env:LOCALAPPDATA\lakbay-dev-tools\composer.phar" demo:reset` | One bootstrap/reset command after initial setup | Same isolated baseline; demo DB records are discarded | Stop demo processes first; only use for disposable QA records |
| `php artisan lakbay:demo` | Non-destructive rerun of idempotent demo seed | Existing demo passwords/history remain | Uses existing .env.local; not the normal .env |
| `php artisan migrate --env=local --no-interaction` | Apply pending migrations to the demo | Nothing to migrate, or migration success | Confirm .env.local contains QA database/storage paths |
| `php artisan db:seed --env=local --class=QaDemoSeeder --no-interaction` | Seed accounts/plans/files idempotently | No duplicate fictional records | Missing migration, wrong DB, or delivering mail is refused |
| `php artisan migrate:fresh --env=local --seed --seeder=QaDemoSeeder --force --no-interaction` | Explicit disposable QA reset | QA tables rebuilt | Prefer guarded demo:reset; never omit --env=local here |
| `php artisan filament:assets` | Publish installed Filament browser assets | Assets published | Verify dependencies installed |
| `npm run build` | Compile public frontend assets | Vite succeeds; public/build/manifest.json exists | Check Node PATH. Optional fontaine warning alone does not fail the build |
| `npm run dev` | Optional frontend hot reload | Vite prints its development URL | Keep Laravel server running too; Ctrl+C stops Vite |

The initial command creates **.env.local**, **database/qa-demo.sqlite**, and **storage/app/qa-demo/**. They are ignored by Git. The generated local environment uses a new application key, local-only candidate testing, log mail, database sessions/queues/cache, a distinct session cookie, and isolated private storage. Low password hashing cost is a demo setting, not a production recommendation. `.env.qa` is not used by these commands.

If your ClamAV installation differs, supply its real executable and signature directory on first bootstrap. Existing .env.local is preserved; adjust only its scanner settings if necessary. Empty/missing signatures leave files quarantined. Do not set scan_status to clean yourself.

### Start the services, in order

1. **Terminal A:** `php artisan queue:work --env=local --stop-when-empty --tries=1 --timeout=180`. Wait until the queue is empty. Each fixture must show a successful ScanDocument job. This uses the real scanner, not a fake.
2. `php artisan lakbay:demo --prepare`. This creates sample states through the existing business Actions and refuses unscanned documents.
3. Run the same stop-when-empty worker again to write fictional workflow emails to the log.
4. **Terminal A, ongoing worker:** `php artisan queue:work --env=local --tries=3 --timeout=180`. Keep it running for browser uploads, emails, and exports.
5. **Terminal B, web server:** `php artisan serve --env=local --host=127.0.0.1 --port=8001`. Open http://127.0.0.1:8001. If the port is occupied by the existing demo server, use that server; do not start a second one.
6. **Terminal C, scheduler:** `php artisan schedule:work --env=local`. Keep it running. `php artisan schedule:list --env=local` shows reminders and failed-job pruning. Daily reminders run at 08:00 Manila; schedule:list may display their UTC equivalent.

There is no separate backend-start command. Built frontend assets need no separate frontend process. A public storage symlink is **not required**: evidence is private and served by protected controllers. Do not put evidence under public/storage. No Mailpit installation is bundled; log mail is already configured. Invitation links/emails appear in **storage/app/qa-demo/logs/laravel.log**, not in a real mailbox.

| Command | Purpose / expected result | Failure check |
| --- | --- | --- |
| `Get-Content storage/app/qa-demo/logs/laravel.log -Tail 80 -Wait` | Follow QA logs/emails | Start worker and trigger an action first; Ctrl+C stops tailing |
| `php artisan queue:failed --env=local` | List QA failed jobs | No failures after successful processing |
| `php artisan queue:retry all --env=local` | Retry QA failures after correcting their cause | Never use against the normal queue accidentally |
| `php artisan lakbay:reminders --env=local` | Exercise QA reminders without waiting for cron | No output is possible when no reminder is due; inspect jobs/logs |
| `php artisan test --compact` | Full isolated in-memory PHPUnit suite | Real counts printed; no normal DB reset |
| `php vendor/bin/phpstan analyse --no-progress --memory-limit=512M` | PHP static analysis | Zero errors |
| `php vendor/bin/pint --dirty --format agent` | Format changed PHP | Passed or formatted files; this changes formatting |
| `php artisan route:list --except-vendor` | Actual app route inventory | Resource and private-controller routes |
| `php artisan route:list --path=api` | Check API assumption | No dedicated application API routes |

Use Ctrl+C in each service terminal to stop it. After source changes, stop/restart server and worker using the same commands. `php artisan queue:restart --env=local` asks workers to exit after the current job; manually start the worker again. To reset the demo, stop server/worker/scheduler, run demo:reset, rescan, run --prepare, and restart. Do not use process-wide kill commands or reset the normal installation.

## 3. Fictional accounts and data

All seeded accounts use **Lakbay-QA-Only!2026**, in the isolated demo only. Passwords are not reset by an idempotent seed rerun. First login requires normal MFA enrollment. Use a separate browser profile for each role, or sign out before changing accounts; both panels share the web authentication guard.

| Email | Actual role | Scope / expected access |
| --- | --- | --- |
| suc.official@example.test | hei_submitter | QA SUC: prepare, upload, submit, reports, follow-up |
| suc.staff@example.test | hei_preparer | QA SUC: prepare/upload/read; no submit or report action |
| suc.faculty@example.test | hei_preparer | Same preparer permissions; PIC is a personnel designation, not a separate account role |
| luc.official@example.test | hei_submitter | QA LUC only |
| luc.staff@example.test; luc.faculty@example.test | hei_preparer | QA LUC only |
| private.official@example.test | hei_submitter | QA Private only |
| private.staff@example.test; private.faculty@example.test | hei_preparer | QA Private only |
| outside.official@example.test | hei_submitter | QA Outside, Region III only |
| outside.staff@example.test; outside.faculty@example.test | hei_preparer | QA Outside only |
| ched.reviewer@example.test | ched_reviewer | Region IV-A intake, review, evidence requests; no signed-acknowledgment authority |
| ched.monitor@example.test | ched_monitor | Region IV-A monitoring, assistance, corrective actions |
| ched.director@example.test | ched_signatory | Record signed acknowledgment after complete review |
| ched.records@example.test | ched_records | Ordinary regional records and uploads |
| ched.analyst@example.test | ched_analyst | Regional reporting, CSV/PDF, snapshots, OSDS correspondence records |
| ched.case@example.test | ched_case_officer | Region IV-A sensitive incidents, cases, restrictions and clearance |
| ched.admin@example.test | system_administrator | Regional institutions/campuses, invitations, policy; **no confidential-case bypass** |
| other.reviewer@example.test | ched_reviewer | Region III; cannot review IV-A submissions |

No auditor or national OSDS user is implemented. Do not rename a regional analyst “OSDS” and assume national visibility.

Seeded memberships/email are already verified solely to start QA. Test actual onboarding separately with an invitation to **qa.new.submitter@example.test**. Acceptance verifies email and membership immediately based on the authorized invitation; there is no separate wait-for-CHED verification screen.

### MFA steps

Sign in → **Set up two-factor authentication** → Authenticator app **Set up** → scan QR/enter its secret in an authenticator → enter its current six-digit code and your current password → **Next** → save recovery codes privately → **Enable authenticator app** → **Continue**. Later logins require password plus TOTP. Wrong codes must fail. MFA secrets are encrypted in the DB. Never paste real QR secrets/recovery codes into bug reports. Browser automation may enroll fictional accounts; reset the demo if you want fresh first-login enrollment for all accounts.

### Data map after --prepare

| Record | Starting state | Use |
| --- | --- | --- |
| QA AY 2026-2027 | 2026-09-01 to 2027-08-31 | Annual deadline demonstration, not a real academic calendar |
| QA annual CLOCA / QA annual | Category 1, annual intake submitted/late | Annual review, correction and later pre-activity package |
| QA category2 | Category 2, corrections_requested | Primary correction/resubmission walkthrough |
| QA category3 | Category 3, student-initiated, no escalation flags, unsubmitted | No ordinary Category 3 SAA; other unresolved timing remains |
| QA escalated3 | Declared Category 3, strenuous=true, requirement_category=2, unsubmitted | Preserve declared category while requiring Category 2 evidence |
| QA completed | Category 2, acknowledged, completed; ROC exists | Signed letter and 72-hour completion checks |
| QA incident | Category 2 submission, initial notice at 49 hours, late | Initial notice, supplement, 48-hour deadline |
| QA isolation | One plan per LUC/Private/Outside HEI | Ownership/jurisdiction checks |
| QA-CASE-001 | Fictional SUC investigation, investigating | Restricted visibility and activity-specific clearance checks |

The pending SUC investigation deliberately appears in readiness checks. It does not create a signed suspension. Choose QA Private for tests that need no investigation, or use the case/clearance walkthrough to record an activity-specific external decision. Sample plan schedules use the bootstrap date: future plans at today+21 days, completed/incident plans yesterday. Inspect actual UI dates instead of assuming a fixed run date.

Fixture files are in **storage/app/qa-demo/app/private/fixtures/**: qa-cloca.pdf, qa-saa.pdf, qa-certification.pdf, qa-padp.pdf, qa-pic_designation.pdf, qa-first_aid.pdf, qa-itinerary.pdf, qa-parent_notice.pdf, qa-orientation.pdf, qa-lgu_coordination.pdf, qa-acknowledgment.pdf, qa-completion.pdf, qa-incident.pdf, qa-diagnostic.pdf, qa-racp.pdf, qa-official_order.pdf, qa-complaint.pdf, qa-clearance_application.pdf, qa-clearance_correspondence.pdf, qa-ceb_decision.pdf, qa-reconsideration.pdf, qa-monitoring.pdf, qa-image.png, qa-invalid.txt, qa-oversize.pdf. Their text clearly says fictional; “signed” is simulated registry metadata, not a legally valid signature. The oversized file is 21 MiB. Single-document upload fields do not support uploading several files at once; a submission can link several individually uploaded documents.

## 4. How to record a test

For every case below, fill **Actual / Status / Notes** immediately. Status is PASS, FAIL, BLOCKED, or NOT TESTED. A blocked scanner or missing role is not a passing case. Capture URL, record reference/ID, role, and screenshot; use the database checks in section 10 for independent confirmation.

Most record creation is a **New …** modal on the list page, not a /create route. Table rows have **View** and **More actions**. View pages also expose workflow header actions. Field labels usually expand model names, for example “Academic period id”. Required fields have an asterisk. There is no generic Delete/Edit action; **Revise** is available for HEI activity plans. Do not mistake hidden buttons for proof that the underlying endpoint is secure.

## 5. Authentication, profile and workspace cases

### AUTH-01 — HEI login, MFA, refresh and direct link

Role: suc.official. Objective: obtain a genuine authenticated HEI session. Precondition: demo server, seeded account; no active session. Steps: open /hei/login; enter the seeded email/password; enroll MFA using section 3 if first login, otherwise enter TOTP; open Start here; refresh; paste /hei/activities into the address bar. Expected: HEI navigation and own records load; MFA cannot be skipped; session survives refresh. Verify the displayed account under the top-right user menu. Actual: ____ Status: ____ Notes: ____

### AUTH-02 — Bad and missing credentials

Role: logged-out HEI and CHED. Steps: on each login page submit empty fields; then qa.unknown@example.test / Lakbay-QA-Only!2026; then a valid seeded email / Wrong-password!2026. Objective: validation without authentication. Expected: required/credential errors, no workspace/session. Use a fresh profile if rate-limited; do not spam retries. Actual: ____ Status: ____ Notes: ____

### AUTH-03 — CHED login and role-specific navigation

Role: ched.reviewer, then ched.director, ched.monitor, ched.analyst, ched.admin, ched.case. Steps: sign out between accounts; /ched/login → password/MFA → Start here; compare the menu with the accounts table. Expected: reviewer sees review actions; director acknowledgment; monitor follow-up; analyst Reporting; admin Invitations/Policy configuration; case officer Restricted cases. Missing authority cannot be obtained by changing the URL. Actual: ____ Status: ____ Notes: ____

### AUTH-04 — Wrong TOTP and recovery

Role: any enrolled fictional account. Steps: sign out; sign in with valid password; enter a six-digit code different from the current authenticator code; expect failure; then use the current code. In a separate run use one saved recovery code, sign out and try reusing it. Expected: wrong TOTP blocked; valid code succeeds; used recovery code cannot be reused. Store remaining codes privately. Automated TOTP tests exist; manually confirm recovery UI. Actual: ____ Status: ____ Notes: ____

### AUTH-05 — Logout and session lifetime

Role: HEI and CHED. Steps: top-right user menu → Sign out; revisit a protected list and use browser Back/Refresh. Expected: no protected data after refresh. For idle expiry, leave a signed-in tab idle more than the configured 120 minutes, then request protected data. Do not infer expiry from a cached screen. Expected: new authentication required. Test Remember me separately because it can restore authentication; it changes the expectation. Actual: ____ Status: ____ Notes: ____

### AUTH-06 — Invitation acceptance and password reset

Role: ched.admin, then new submitter. /ched/invitations → Invite; Name QA New Submitter, Email qa.new.submitter@example.test, Position QA official, Role HEI authorized submitter, Institution QA Private. Process QA queue; copy its link from QA log; open in a logged-out profile; enter Lakbay-New-Only!2026 twice and submit. Expected: account created with invited role/membership; normal MFA enrollment follows. Reopen the accepted link: unavailable. Repeat with a mismatched confirmation and an 11-character password first: validation blocks creation. On /hei/login → Forgot password? request reset for that account; process worker, use QA logged link. Expected: reset link works once within its expiry. No public registration/approval-wait step exists. Actual: ____ Status: ____ Notes: ____

### AUTH-07 — Profile and password persistence

Role: HEI and CHED. User menu → Profile (/hei/profile or /ched/profile). Change display name to QA Renamed Official, save, refresh; leave name/email blank and try saving; restore email after testing; change password using current seeded password and matching Lakbay-Changed-Only!2026; sign out and verify new login. Expected: own profile updates persist, invalid input rejected; no institution/region/role fields are editable here. Cancel by leaving before saving. MFA remains required. Actual: ____ Status: ____ Notes: ____

### HEI-01 — Workspace counts and links

Role: suc.official. /hei/overview → inspect Activity plans, Current submissions, Corrections requested and Recent submissions; open every workflow shortcut and Report an incident. Expected at prepared baseline: 6 own plans, 4 current submissions and 1 correction; refresh after seeding before comparing. Compare SQL Q1/Q2; superseded revisions do not count as current submissions. This is guidance, not an automatic completion tracker. Actual: ____ Status: ____ Notes: ____

## 6. HEI preparation, evidence and submissions

### HEI-02 — Academic period and annual calendar

Role: private.official. Objective: create own planning references. /hei/academic-periods → New academic period; Institution QA Private, Label QA AY 2027-2028, Starts on 2027-09-01, Ends on 2028-08-31, Term First semester → Create → refresh. Then /hei/calendars → New calendar; Institution QA Private, Academic period the new label, Title QA UAT CLOCA 2027, Responsible office QA OSAS, Effective on 2027-08-01 → Create. Expected: own list rows persist; SQL Q1/Q3 confirms IDs. Before saving, try Ends on 2027-08-31: reject end before start. Cancel a second modal: no new row. No academic-period/calendar edit/delete screen exists. Actual: ____ Status: ____ Notes: ____

### HEI-03 — Create Category 1 plan

Role: private.official. /hei/activities → New activity. Use the new period/calendar from HEI-02. Objective: create an immutable plan version. Enter exactly:

| Form section / field | Input |
| --- | --- |
| Institution and calendar | QA Private; QA AY 2027-2028; QA UAT CLOCA 2027; campus optional |
| Title / category / academic nature | QA UAT museum visit / Category 1 / Curricular |
| Activity type / initiator / program | Learning visit / HEI / BS QA Studies |
| Description / learning objectives / responsible office | Fictional museum visit / Identify three exhibits safely / QA OSAS |
| Starts at / ends at / venue | 2027-10-15 09:00 / 2027-10-15 17:00 / QA Museum |
| Student count / other count | 20 / 1 |
| Conditions | Explicitly keep Requires physical activity and Requires travel false for this controlled ratio test; other flags false |
| Personnel row | Role PIC; Name QA Faculty Two; Position Faculty; Contact faculty.two@example.test; Qualifications QA relevant training |
| PIC checkboxes / reference | First aid trained, HEI employed, Accompanies activity all ON; First aid reference QA-TRAIN-002 |
| Preparation references | inclusivity QA-INCL; parallel learning QA-PADP; grievance QA-GRIEVANCE; student participation QA-CONSULT; emergency preparedness QA-RACP |
| HEI authorization | Official QA HEI Head; Date 2026-10-07 |

Create → View. Expected: planned activity and version 1; personnel and classification rows exist. Saving is a plan revision, not a submission or CHED approval. Note the known UTC list-display defect in section 15 when checking 09:00. Actual: ____ Status: ____ Notes: ____

### HEI-04 — Activity validation boundaries

Role: private.official, new modal; same valid data as HEI-03 but cancel after each negative run. Try blank title; 256-character title; 101-character Activity type; Student count 0, -1 and 1.5; Ends at equal to or earlier than Starts at; no personnel; only APIC and no PIC; Category 3 with Initiator HEI. Expected: validation, no partial activity/version/personnel rows. Student count 1 and equal-day end later than start are valid. Do not assume an undocumented legal maximum student count; large values need a defined business limit, not a guessed one. Title 255 is within its limit. Other count allows zero in the form. Actual: ____ Status: ____ Notes: ____

### FILE-01 — PDF/PNG upload, scanning and retrieval

Role: suc.official. /hei/documents → New document → Institution QA SUC; Is signed OFF; Document type Supporting Evidence; File qa-image.png → Create. Expected: Pending initially; worker → Clean; View → preview, Open image, Download work. Repeat with qa-saa.pdf, Document type Sworn Affidavit of Accountability, Is signed ON. Confirm content is the selected fictional PDF; PDF uses Download, not image preview. Q4 confirms hashes/mime/scan status/owner. Arbitrary filenames must not determine document type. Actual: ____ Status: ____ Notes: ____

### FILE-02 — Rejected upload inputs and signature mismatch

Role: suc.official. New document: leave File empty and Create; choose qa-invalid.txt; choose qa-oversize.pdf (21 MiB); choose qa-image.png with Is signed ON. Expected: required/type/20-MB/signed-PDF rejection; no usable document or successful scan for rejected input. Check Network response and Q4 if upload reaches 100% then turns red. Test one 20-MB valid document at the boundary only if PHP post_max_size/upload_max_filesize support it; successful transfer still requires server validation and scanning. Do not upload actual executables or sensitive records. Actual: ____ Status: ____ Notes: ____

### FILE-03 — Quarantine and worker failure

Role: suc.official. Stop only the QA worker; upload a valid supporting PNG; save its ID/public_id. View and request its download/preview while pending. Expected: no preview/download action; direct authorized endpoint returns 409. Restart worker and refresh after successful ClamAV processing; retrieval becomes available. Missing scanner/signatures must keep the file quarantined and create a failed job, never make it Clean. Scanner error/hash/infected branches have automated coverage; live malware testing is not required. Actual: ____ Status: ____ Notes: ____

### FILE-04 — Form version/coverage and replacement history

Role: suc.official. Upload a new signed SAA; expand Form references and coverage; select CMO24-2026 Annex C, reference QA-SAA-002, semester First, academic year 2026-2027, coverage 2026-09-01 to 2027-01-31, signatory QA HEI Head, notarial reference QA-NOTARY. Expected: versioned template metadata retained. Try Annex B CLOCA with an SAA document type or coverage end before start: rejected. A replacement is a **new upload and new submission revision**; there is no delete/overwrite button or comprehensive supersession UI. Old file/hash/snapshot must remain. Actual: ____ Status: ____ Notes: ____

### HEI-05 — Candidate checklist and annual-stage separation

Role: suc.official. /hei/calendars → View QA annual CLOCA → Submit / resubmit → choose CMO 24 candidate. Objective: inspect actual annual checklist without resubmitting the already submitted version. Expect annual rows for responsibility/policies/PIC/calendar/PADP/SAA and applicable costs, not the later itinerary/parent-notice/requested-assessment stage. Cancel. Open QA annual in Activity plans → Submit / resubmit, same policy: expect its separate pre-activity checklist and retained-assessment references. Both contexts use the same candidate but different stages. Existing annual intake satisfies the prerequisite; acknowledgment is not required to claim HEI authorization. Actual: ____ Status: ____ Notes: ____

### HEI-06 — Submit a new annual package

Role: private.official. Preconditions: HEI-03 and **own** clean signed evidence. Upload fixtures again selecting QA Private, so documents belong to that institution. Annual calendars → View QA UAT CLOCA 2027 → Submit / resubmit → CMO 24 candidate; for each applicable requirement choose Submitted and its evidence. Use the matrix below. Submit. Expected: frozen annual_calendar submission with receipt/reference; CHED reviewer can see it; current versions and document hashes are captured. Missing later pre-activity evidence must not block annual intake. Q2/Q5 confirm stage and ownership. Actual: ____ Status: ____ Notes: ____

### Evidence selection matrix

The modal supplies the applicable rows. Several rows legitimately reuse qa-certification.pdf. For a supporting-type requirement select both primary and supporting evidence, not just the certification. Retained rows use **Certified as retained by HEI** with reason **QA-CERT-001; signed record retained at QA OSAS**. Do not attach restricted documents from another institution.

| Section 14 row | Primary fixture | Additional fixture / applicability |
| --- | --- | --- |
| 1, 2, 3 | qa-certification.pdf | Row 3 Category 1 only |
| 4 CLOCA | qa-cloca.pdf | Also qa-certification.pdf; Categories 1–2 |
| 5 parallel activity | qa-padp.pdf | Categories 1–2 |
| 6 costs/consultation | qa-certification.pdf | Also consultation evidence typed consultation; only when Has student costs ON. Create a fictional PDF copy named qa-consultation.pdf and upload under that type |
| 7 invitation | invitation copy | Only External invitation ON; upload fictional PDF as External Invitation; need not mark signed |
| 8 coordination | qa-lgu_coordination.pdf | Categories 2–3; copy allowed |
| 9 DOT | accommodation/venue evidence | Only accommodation or venue accreditation flag; upload under DOT Accreditation Evidence |
| 10 SAA | qa-saa.pdf | Categories 1–2, including Category 3 treated as Category 2; not ordinary Category 3 |
| 11 authorization | qa-certification.pdf | Categories 1–2 |
| 12 PIC | qa-pic_designation.pdf | Also qa-first_aid.pdf; Category 1–2, plus personnel qualifications |
| 13 RACP / 14 diagnostic certification | qa-certification.pdf | All categories; accomplished assessments retained/requested separately |
| 15 risk permissions | qa-certification.pdf | Applicable relevant risk only; retained consent/medical/insurance references separate |
| 16 itinerary | qa-itinerary.pdf | All categories, pre-activity |
| 17 parent notification | qa-parent_notice.pdf | All categories, permitted copy |
| 18 orientation | qa-certification.pdf | Also qa-orientation.pdf; Categories 2–3 |
| 19 supervision | qa-certification.pdf | Category 3 |
| 20–22 | Completion / incident / change forms | Separate reporting workflows, not pre-activity submission rows |

### HEI-07 — Missing evidence, duplicate submission and locked history

Role: private.official. On a fresh Category 2 or escalated Category 3 plan/package, omit SAA or the supporting first-aid file and submit. Expected: specific completeness rejection; no submission. Ordinary Category 3 does not require SAA. Complete evidence and submit; immediately submit the same version/stage again without a correction request. Expected: duplicate blocked. View Submissions & letters → Historical submission snapshot; note original venue and version. Revise the activity's venue, save and revisit old submission: original snapshot remains unchanged. Existing plan may be revised; submitted historical records cannot be edited. Use calendar/activity Submit / resubmit; do not use the misleading New submission button (known defect). Actual: ____ Status: ____ Notes: ____

### HEI-08 — Category 2 and ordinary Category 3 requirements

Role: suc.official. Activity plans → View QA category3 → Submit / resubmit → corrected candidate. Expected: declared/requirement category 3, no SAA requirement, Category 3 timing on other relevant rows unresolved, no Category 1 annual-calendar block. Retained diagnostic/RACP references present. Inspect a Category 2 plan similarly: CLOCA/SAA/PADP/PIC, applicable invitation/DOT, itinerary/notification/orientation are present. Cancel inspection or submit with all corresponding evidence. For a fresh Category 2 plan choose HEI or External initiator appropriately; an invitation alone is not automatic classification. Actual: ____ Status: ____ Notes: ____

### HEI-09 — Category 3 treatment and explicit reclassification

Role: suc.official. View QA escalated3: declared 3, treatment 2. On QA category3 → Revise, toggle Strenuous ON; Save changes; reopen submission modal. Expected: declared category remains 3, requirement_category=2, new evidence checklist. Repeat on a fresh version using accommodation and long-distance separately. Explicitly changing Category from 3 to 2 without Classification reason must fail; with reason **QA scope expanded to institution-led visit** save succeeds and creates classification history. Original submitted versions stay intact. Vulnerable participants/class-size also trigger treatment; thresholds remain provisional. Actual: ____ Status: ____ Notes: ____

### HEI-10 — PIC ratios, qualifications and contracts

Role: private.official, fresh plan. With physical activity/accommodation/travel all false, 50 students/1 qualified PIC passes the ratio; 51 requires 2. With Requires travel ON, 30/1 passes, 31 requires 2. Add an APIC instead of a second PIC: insufficient. Turn off HEI employed, Accompanies activity, First aid trained, or clear qualification/reference: submission rejected. For Has third party services ON, upload own signed Third-party service contract PDF, add contract provider QA Transport, service Bus, effective date before activity. Missing/expired/future-effective contract must block pre-activity readiness. Test boundaries through submission, not just saving a draft. Actual: ____ Status: ____ Notes: ____

### HEI-11 — Late intake, working days and unresolved timing

Role: suc.official. View seeded annual submission: late against ordinary academic-year-start minus 30 days, with publication-transition cutoff unresolved until verified publication. Inspect QA completed/incident late pre-activity submissions; they were received after the three-working-day pre-start cutoff. Expected: late accepted/flagged, not dropped. On ordinary Category 3, unresolved due_at is not proof of on-time compliance. Deadline calculations exclude weekends and applicable versioned holidays, not arbitrary local suspension guesses. Exact at/before/after boundaries are automated with time travel (section 11); never change your workstation clock. Actual: ____ Status: ____ Notes: ____

### HEI-12 — Requested evidence response

Role: ched.reviewer then suc.official. CHED Submission inbox → View a SUC submission → Request evidence → Document type Diagnostic Tool, reason **QA request accomplished assessment**, Due at now (the CMO requested rule overrides it to immediate). HEI Evidence requests → View → Respond; choose clean signed qa-diagnostic.pdf. Expected: request records receipt/deadline and supplied document; frozen submission unchanged. Wrong document type or unsigned diagnostic must fail. Process queue and inspect QA request email. Actual: ____ Status: ____ Notes: ____

### HEI-13 — Readiness is independent of acknowledgment

Role: suc.official. Activity plans → View QA completed → Authorization / restrictions; then QA annual. Expected: readiness identifies institutional investigation/need for activity-specific clearance, missing current pre-activity package or preparation where applicable; signed acknowledgment does not remove restrictions. No generic “CHED approved” status appears. An ongoing complaint alone does not count as an authoritative investigation or suspension. Q8/Q9 verify the exact legal records. Actual: ____ Status: ____ Notes: ____

## 7. Reports and HEI follow-up

### HEI-14 — Record actual conclusion and completion report

Role: private.official. Create a fresh past-dated QA report practice plan (yesterday 09:00–17:00), submit candidate pre-activity evidence to establish its policy. Activity View → Record actual conclusion → yesterday 17:00 Manila. Completion reports → New completion report: Institution QA Private; Activity that plan; clean signed Completion document; Ended at same actual conclusion; Venue QA Museum; Student count 20; Implementation As authorized by HEI; Summary **QA activity concluded safely**; Incidents encountered OFF → Create. Expected: completed state, 72-hour due_at, not late if received within 72 hours; stored deadline policy/reference. Different ended_at from an already recorded conclusion must fail. Future conclusion must fail. Actual: ____ Status: ____ Notes: ____

### HEI-15 — Changed implementation and linked incidents

Role: suc.official. On a fresh completion for an activity with a recorded incident, choose With changes; leave Change explanation blank or omit known incident links/flag. Expected: reconciliation validation. Supply **QA venue changed after transport delay**, Incidents encountered ON, select the applicable incident ID and correct signed completion PDF; save. Expected: report preserves explanation/link and actual conclusion. Use another activity if a completion already exists; completed historical report replacement is not a generic Edit action. Actual: ____ Status: ____ Notes: ____

### HEI-16 — Incident initial notice and signed supplement

Role: suc.official. /hei/incident-reports → New incident report; Institution QA SUC; Activity QA incident; document optional; Occurred at use the seeded incident's EXACT occurrence; Location QA Learning Venue; Type QA incident supplement; Description **QA additional verified information**; Immediate actions **QA PIC contacted OSAS**; Current status **Resolved**; Supplements report id choose original notice; signed qa-incident.pdf for a signed supplement → Create. Expected: same root occurrence/deadline, supplement linked, no replacement/double count. For a fresh initial notice leave Document empty: accepted even late. A time tomorrow is rejected. Details visible to own HEI and case officer, not ordinary reviewer. Actual: ____ Status: ____ Notes: ____

### HEI-17 — Incident and ROC elapsed-hour boundaries

Role: HEI; primarily automated clock-controlled cases. Current candidate initial notice at now−47h59m, now−48h, now−48h01m should be not late, not late, late respectively when receipt is frozen. ROC equivalents use 72h. Real browser clicks drift in time, so record precise seconds and do not call exact-boundary browser input conclusive. Seeded QA incident is deliberately 49h late. Verify deadline_parameters and due_at−occurred_at through Q7: 48h under CMO, retained July baseline 24h where applicable. No 72h incident rule should be guessed. Actual: ____ Status: ____ Notes: ____

### HEI-18 — Immediate change notice and original/revised comparison

Role: suc.official. Activity plans → QA category2 → Revise; change venue to QA Revised Venue and PIC contact to updated@example.test, leave identity/calendar unchanged; Save changes. Change reports → New change report: Institution QA SUC; Activity QA category2; Original version revision 1; Revised version revision 2; Changed at now minus 5 minutes; Reason **QA venue/PIC contact updated**; Document empty for initial notice → Create. Expected: immediate-notice record, comparison preserves venue and personnel differences; original version unchanged; CMO formal due_at/is_late remain null/unresolved, not fabricated at changed_at+72h. Same original/revised version or another activity's version must fail. Actual: ____ Status: ____ Notes: ____

### HEI-19 — Corrective response then CHED closure

Role: ched.monitor → suc.official → ched.monitor. Create monitoring visit per CHED-06; its View → Corrective action; Action **QA provide emergency preparation reference**, Responsible party QA OSAS, Due on today+7 days. HEI /hei/corrective-actions → View/Respond → Response **QA-RACP-UPDATED supplied**. Expected: open → responded; monitor then Close, confirm → closed. Close before response must be unavailable/rejected; HEI cannot close; closed action cannot be overwritten. Q10/audit verify both actors. Actual: ____ Status: ____ Notes: ____

### HEI-20 — Workflow mail and absence of notification inbox

Role: HEI plus reviewer. Trigger review/evidence/acknowledgment, run QA worker, inspect QA log. Expected: correct fictional recipient, subject/reference and HEI portal link; no sensitive incident details in email. Retrying an already delivered workflow job must not duplicate delivery; notification_deliveries stores deduplication. Browser success toasts are temporary feedback. Persistent notification bell/read-unread inbox functionality is not currently implemented in the inspected codebase. Actual: ____ Status: ____ Notes: ____

## 8. CHED review, monitoring, reporting and administration

### CHED-01 — Regional workspace, lists, search and filters

Role: ched.reviewer. /ched/overview → Submission inbox (/ched/submissions). Search a seeded reference; open the filter control and choose Corrections requested; clear it. /ched/institutions: QA SUC, LUC and Private should be present; QA Outside absent. Activity records /ched/activities includes 8 IV-A plans at baseline, not the outside plan. Search an impossible title: clean empty state. Switch document list Per page 10/25/50 and sort a column. Verify Q1/Q2. No assumption of special filters on lists lacking them. Actual: ____ Status: ____ Notes: ____

### CHED-02 — Request corrections and inspect the timeline

Role: ched.reviewer. Submission inbox → View seeded annual submission → Review; Outcome Corrections requested; Remarks **QA update PIC contact and clarify destination** → submit modal. Expected: corrections_requested; review_findings/review/audit records; assignment to reviewer; HEI sees correction after refreshing and gets queued mail. Blank remarks must fail. Timeline shows the review. A second review of that revision must fail. No separate deficiency PDF is generated automatically. Actual: ____ Status: ____ Notes: ____

### CHED-03 — Mark complete and record signed Annex K letter

Role: reviewer then director. On a new/resubmitted current revision, reviewer → Review → Complete for acknowledgment; Remarks **QA reviewed evidence complete**. Expected ready_for_acknowledgment, not authorized. Sign out; director → same submission → Record signed acknowledgment; choose own clean signed qa-acknowledgment.pdf; Reference QA-ACK-UAT-002; Recipient QA HEI Head; Signatory QA Director; Letter date today → save. Expected acknowledged, exact revision linked, one letter maximum. HEI Signed acknowledgment downloads supplied PDF. Q6 shows program/course, period, counts and disclaimer in recorded summary. The uploaded external PDF's own content is not automatically regenerated or legally checked; inspect it separately. Review-before-letter and role checks must hold. Actual: ____ Status: ____ Notes: ____

### CHED-04 — Institution/campus creation and unavailable editing

Role: ched.admin. /ched/institutions → New institution; Ched code QA-UAT-NEW, Name QA New Private HEI, Institution type Private, Address Fictional address, Email new.hei@example.test → Create. Expected region assigned IV-A server-side; duplicate code rejected. /ched/campuses → New campus; Institution new HEI; Name QA South Campus; Locality QA Santa Rosa → Create. View/refresh and Q1/Q3. Existing institution edit, activation/deactivation, and a separate membership-verification screen are not currently implemented in the inspected codebase. Do not invent an Edit button. Actual: ____ Status: ____ Notes: ____

### CHED-05 — Candidate configuration and activation gate

Role: ched.admin. /ched/policy-configuration → inspect CMO 24 candidate parameters, all 22 rows and final annex versions B–N. Test copying to a new candidate QA-POLICY-UAT without changing the original; inspect copied signature/stage/parameters. Attempt confirmation without publication/reference: blocked. **Do not invent publication information to make this pass.** Confirmed policies/templates remain immutable; July references remain historical. Public templates are unavailable until genuinely confirmed/effective policy and approved clean blank PDF registration. See Q11. Actual: ____ Status: ____ Notes: ____

### CHED-06 — Monitoring visit, fourteen criteria and sign-offs

Role: ched.monitor. /ched/monitoring-records → New monitoring record; Institution QA SUC; Activity QA annual optional; Period QA First semester; Monitored on today; Type Desk; Respondent QA OSAS Officer; Overall rating Non-compliant; Findings **QA emergency reference requires follow-up**; Good practices QA retrievable records; Suggestions QA update training; respondent position QA officer; prepared/reviewed/noted/conforme names QA Staff/QA Supervisor/QA Director/QA HEI Head. For all 14 fixed items choose Yes except criterion 9 No; Verification **QA evidence reviewed**; remarks optional. Create/View. Expected exactly 14 rows, Annex **M** criterion version and metadata preserved; incomplete/duplicate criteria rejected atomically. Monitoring is not Annex I (which is changes). Actual: ____ Status: ____ Notes: ____

### CHED-07 — Technical assistance

Role: ched.monitor. /ched/technical-assistance-logs → New technical assistance log; Institution QA SUC; Activity QA annual optional; Type QA document orientation; Provided on today; Summary **QA explained retained-evidence response**; Follow up **QA confirm reference next week** → Create/View/refresh. Expected institution/activity-scoped log with staff member/date; no legal approval or suspension inferred. SQL Q10 verifies. Actual: ____ Status: ____ Notes: ____

### CHED-08 — Regional snapshot and CSV/PDF exports

Role: ched.analyst. /ched/reporting → Snapshot; Period QA UAT regional, Cutoff at current past minute, Report type Regional snapshot → save. Then Export: Format CSV, Cutoff same minute, Frozen snapshot the new report → queue; refresh after worker; Your exports → Download CSV. Repeat PDF. Expected correct regional totals; one activity per identity, root incidents once; formula-like spreadsheet text escaped; no detailed incident/case contents. PDF opens. Freeze, then create a later activity/submission and export the **old** snapshot again: earlier totals unchanged. No native XLSX export is implemented. Q12 verifies status/expiry/snapshot. Actual: ____ Status: ____ Notes: ____

### CHED-09 — Annual M&E and external OSDS correspondence

Role: ched.analyst. Reporting → Snapshot; Period QA annual M&E 2026, Report type Annual M&E report to OSDS, Reporting year 2026, Period from 2026-01-01 00:00, Cutoff at now−5 min. Missing year/start, future cutoff, or start after cutoff must fail. Save valid snapshot; expected Last week of August window, no invented exact legal deadline. Record OSDS dispatch: choose snapshot, Sent at now−3 min, Reference QA-OSDS-OUT-001, signed clean qa-monitoring.pdf. Record OSDS receipt: Received at now−1 min, Reference QA-OSDS-IN-001. Expected append-only correspondence, snapshot unchanged; receipt before dispatch/second overwrite rejected. These records do **not** send OSDS email or provide a national OSDS login. Actual: ____ Status: ____ Notes: ____

### CHED-10 — Signed complaint and recorded procedural history

Role: ched.case. /ched/case-records → New case record; Institution QA SUC; source clean signed qa-complaint.pdf; Reference QA-COMPLAINT-UAT; Type Complaint; Summary **FICTIONAL QA written complaint**; Status Received; Opened at now−1h → Create. View → Record case event → Fact-finding evidence; Notes **QA fact-finding record received**; signed supporting document. Expected procedural event without conversion to investigating or automatic suspension. Complaint with status Investigating or wrong/unsigned source must fail. Investigator case needs authoritative signed official-order evidence. Restricted records unavailable to ordinary reviewer/admin. Actual: ____ Status: ____ Notes: ____

### CHED-11 — Exceptional clearance filing and CHEDRO/OSDS routing

Role: ched.case. /ched/clearance-requests → New clearance request; Institution QA SUC; Application document qa-clearance_application.pdf; Activity version QA annual/current; Case QA-CASE-001; Justification **QA activity-specific application**; Application received at now−1h; endorsement/OSDS optional initially. Expected server-calculated advance filing at start−90 days, explicitly provisional calendar unit; late accepted/flagged. View → Record CHEDRO / OSDS routing: CHEDRO endorsement, QA-END-001, occurred now−45m, qa-clearance_correspondence.pdf; repeat OSDS receipt/review/Routed to CEB with later past times and distinct references. Expected append-only routing; not permission to conduct. Q9 verifies version-specific coverage. Actual: ____ Status: ____ Notes: ____

### CHED-12 — External decision, receipt and one reconsideration

Role: ched.case. Clearance View → Record external CEB decision; Decision Denied; Document qa-ceb_decision.pdf; Decided at now−30m; Received at now−20m; Receipt reference QA-DENIAL-RECEIPT → save. Expected denied, HEI readiness still requires clearance. Reconsideration: Filed at now−10m; Grounds **QA request reconsideration**; qa-reconsideration.pdf → save. One request only; within 15 calendar days of denial receipt; a late recorded filing is not accepted legal reconsideration by default. Record final external decision Granted with appropriate clean signed CEB evidence; original denial preserved and only that activity version cleared. No in-app CEB decision-making is claimed. Actual: ____ Status: ____ Notes: ____

### CHED-13 — Signed restriction and case closure

Role: ched.case. /ched/institutional-restrictions → New institutional restriction; Institution QA SUC; Type Suspension; Effective from today; Effective until tomorrow; Issuing authority QA authorized authority; clean signed Official Order; activity scope optional. Expected recorded signed restriction appears in readiness; no complaint-derived suspension. Case View → Record case event → Closed with signed official order; unsigned/missing order rejected. This does not automatically erase an independent signed restriction. Publication of sanctions is not implemented. Actual: ____ Status: ____ Notes: ____

## 9. Primary HEI → CHED → HEI walkthrough

### E2E-01 — Correct, resubmit, acknowledge and verify both sides

Objective: complete the supported lifecycle using two browser profiles and three real role accounts. Precondition: prepared QA category2 correction, scanned own documents, worker running. Roles: suc.official, ched.reviewer, ched.director. Keep the initial reference and revision ID in Notes.

1. HEI: /hei/submissions → open its Corrections requested revision; Timeline/read remarks and preserved snapshot. Confirm original venue **QA Learning Venue**.
2. HEI: /hei/activities → QA category2 → Revise. Change Venue to **QA Revised Venue**, PIC Contact to **updated@example.test**; retain its institutional/academic/calendar identity; Save changes. Original version remains.
3. HEI: same Activity → Submit / resubmit. Choose **CMO24-2026 candidate** and **Previous correction request** matching that exact activity/reference. Supply complete evidence using section 6; include diagnostic/RACP retained references. Submit and note new reference. Do not switch policy baselines.
4. HEI: Submissions & letters shows old Superseded, new Submitted. Inspect both snapshots: old venue original, new venue revised. Sign out.
5. CHED reviewer: sign in/MFA → Submission inbox → locate new reference → View → inspect documents and timeline. Review → Complete for acknowledgment; Remarks **QA corrected destination and contact reviewed**.
6. Reviewer: status Ready for acknowledgment. Sign out. CHED director: sign in/MFA → same current revision → Record signed acknowledgment with clean own acknowledgment PDF, reference **QA-E2E-ACK-001**, recipient **QA HEI Head**, signatory **QA Director**, date today.
7. CHED director: status Acknowledged; sign out. HEI: sign in/MFA → same revision → Signed acknowledgment → inspect downloaded PDF; Timeline and old revision intact. QA emails mention review/letter availability.
8. HEI: Authorization / restrictions still reports the independent institutional investigation unless activity-specific clearance is recorded. An acknowledgment is not a conduct authorization.
9. Verify Q2/Q5/Q6/Q13 and attempt viewing the new submission as luc.official: denied/not found, no data leak. Attempt duplicate letter/resubmission: rejected.

Expected: actors, revisions, snapshots, review findings, letter and audit history match each step; no overwrites or false approval. Actual: ____ Status: ____ Notes: ____

## 10. Database verification without changing records

Use **database/qa-demo.sqlite**, never the normal DB. If you have a SQLite viewer, open that file read-only and paste the queries. The following PowerShell command uses the installed PHP PDO SQLite and works without installing sqlite3. Replace only the SQL text with a query below. Do not issue UPDATE/DELETE during verification.

```powershell
@'
<?php
$database = new PDO('sqlite:database/qa-demo.sqlite');
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('PRAGMA query_only=ON');
$sql = "SELECT id,reference,status,stage,is_late FROM submissions ORDER BY id";
foreach ($database->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES).PHP_EOL;
}
'@ | php
```

Tables/columns below were checked against migrations/models. Timestamps are normally stored UTC; input/UI requirements are Manila. Never dump users.password or encrypted MFA secrets. For filtered IDs use the actual ID observed in your demo, rather than a guessed production record.

```sql
-- Q1: region/HEI/account ownership (all accounts should be fictional)
SELECT i.id,i.ched_code,i.name,i.institution_type,r.code AS region
FROM institutions i JOIN regions r ON r.id=i.region_id ORDER BY i.id;
SELECT u.email,iu.institution_id,iu.position,iu.is_authorized,iu.verified_at
FROM users u JOIN institution_user iu ON iu.user_id=u.id ORDER BY u.email;

-- Q2: current versus superseded revisions; status/late/stage
SELECT id,institution_id,reference,status,stage,revision,supersedes_submission_id,
       policy_version_id,submitted_at,received_at,due_at,is_late FROM submissions ORDER BY id;

-- Q3: references and plan history
SELECT id,institution_id,label,starts_on,ends_on,term FROM academic_periods;
SELECT id,institution_id,academic_period_id,title FROM calendars;
SELECT a.id,a.title,a.institution_id,a.execution_status,a.actual_ended_at,
       v.id AS version_id,v.version,v.category,v.requirement_category,v.venue,v.starts_at,v.ends_at
FROM activities a JOIN activity_versions v ON v.activity_id=a.id ORDER BY a.id,v.version;
SELECT activity_version_id,role,name,hei_employed,accompanies_activity,first_aid_trained,
       first_aid_reference FROM activity_personnel;
SELECT activity_version_id,declared_category,requirement_category,reason,confirmed_by
FROM activity_classifications;

-- Q4: private files and security state, never change scan_status manually
SELECT id,institution_id,public_id,original_name,mime,size,scan_status,is_signed,
       confidentiality,sha256,metadata FROM documents ORDER BY id;

-- Q5: exact frozen versions/rules; old snapshot must retain old values
SELECT submission_id,activity_version_id FROM submission_activities;
SELECT submission_id,policy_requirement_id,activity_version_id,evidence_status,
       reason,document_ids,due_at,rule_snapshot FROM submission_requirements;
SELECT id,reference,snapshot FROM submissions;
SELECT submission_id,outcome,remarks,reviewed_by FROM submission_reviews;
SELECT submission_review_id,deficiency,requested_action FROM review_findings;

-- Q6: exact revision's acknowledgment summary and signed document
SELECT submission_id,reference,document_id,issued_by,summary FROM acknowledgments;

-- Q7: report anchors, policy snapshots and supplements
SELECT id,activity_id,ended_at,due_at,is_late,implementation,change_explanation,
       incident_report_ids,deadline_parameters FROM completion_reports;
SELECT id,activity_id,supplements_report_id,occurred_at,due_at,is_late,
       intake_kind,deadline_parameters FROM incident_reports;
SELECT id,activity_id,original_version_id,revised_version_id,changed_at,
       intake_kind,notified_at,due_at,is_late,deadline_parameters FROM change_reports;
SELECT change_report_id,field,previous_value,revised_value FROM change_report_items;

-- Q8: case/order evidence is separate from complaint allegations
SELECT id,institution_id,reference,type,status,source_document_id FROM case_records;
SELECT case_record_id,type,document_id FROM case_events;
SELECT institution_id,activity_id,type,effective_from,effective_until,document_id
FROM institutional_restrictions;

-- Q9: clearance filing/routing/receipt; late does not imply permission
SELECT id,activity_version_id,status,application_received_at,application_due_at,
       application_is_late,routing_history,decision,decision_received_at,
       decision_receipt_reference FROM clearance_requests;
SELECT clearance_request_id,filed_at,due_at,is_late,filing_status,decision FROM reconsiderations;

-- Q10: monitoring and assistance / corrective transitions
SELECT id,institution_id,monitored_on,type,overall_rating,criteria_version,annex_metadata
FROM monitoring_records;
SELECT monitoring_record_id,criterion_code,compliance,verification FROM monitoring_items;
SELECT institution_id,type,provided_on,summary,provided_by FROM technical_assistance_logs;
SELECT id,institution_id,action,status,response,due_on FROM corrective_actions;

-- Q11: CMO candidate must remain candidate with NULL publication/effectivity
SELECT code,status,issued_on,publication_on,effective_on,publication_reference,parameters
FROM policy_versions;
SELECT section_row,category,stage,predicate,evidence_mode,signature_mode,
       deadline_type,deadline_anchor,deadline_amount,deadline_status FROM policy_requirements
WHERE policy_version_id=(SELECT id FROM policy_versions WHERE code='CMO24-2026')
ORDER BY section_row,category,stage;
SELECT code,annex,version,source_page,is_public,document_id FROM form_templates;

-- Q12: frozen regional totals and private export lifetime
SELECT id,region_id,period,report_type,reporting_year,cutoff_at,totals,due_window,
       sent_to_osds_at,dispatch_reference,osds_received_at,receipt_reference FROM regional_reports;
SELECT id,requested_by,status,filters,expires_at FROM exports;

-- Q13: business action audit trail; actor/record/action/correlation IDs
SELECT id,actor_id,institution_id,action,record_type,record_id,correlation_id,created_at
FROM audit_events ORDER BY id DESC LIMIT 40;
SELECT user_id,event_key,channel,delivered_at FROM notification_deliveries;

-- Q14: schema's actual holiday table spelling (not "holidays")
SELECT date,name,scope,locality,calendar_version FROM holidaies;
```

Raw database audit access is for this fictional local QA environment. There is no dedicated auditor role or audit-log browser module. Profile/login/logout are framework actions and are not all recorded in audit_events; do not claim “every status change” has been proven audited.

## 11. Authorization, endpoints and deadline tests

### SEC-01 — Logged-out access and private document URLs

Role: logged out/incognito. Paste /hei/activities, /ched/submissions and a downloaded /documents/{public_id} URL. Expected redirect to login for HTML; no data/image/PDF. With Accept: application/json, unauthenticated private retrieval should return 401. A known file path under /storage/quarantine/... must not be public. Verify Network status/response, not only a hidden menu. Actual: ____ Status: ____ Notes: ____

### SEC-02 — HEI cross-institution URL and selector isolation

Role: suc.official then luc.official. Note SUC activity/submission/document View URLs. Sign in as LUC and paste each exact URL, including image preview and download. Expected denied/not found, no SUC data. Open LUC create/revise/submit modals: related selectors must list only LUC periods, versions and clean files. Try a forged SUC relation via a controlled automated request, not by assuming the dropdown proves protection. Existing ownership tests cover backend submissions and evidence. Actual: ____ Status: ____ Notes: ____

### SEC-03 — Regional isolation

Role: ched.reviewer, then other.reviewer. IV-A reviewer cannot see QA Outside list records or open its actual View/private-file URL. Region III reviewer cannot see/review a SUC submission by pasting its URL. Lists, selectors, Actions and export jobs enforce region. Expected 403/404 with no cross-region contents; Q1/Q2 identify region ownership. Actual: ____ Status: ____ Notes: ____

### SEC-04 — Panel and action permissions

Role: HEI official, HEI preparer, CHED reviewer/director. HEI /ched/submissions denied; CHED /hei/activities denied without HEI membership/permission. Preparer sees own plans/uploads but no legitimate submit/report authority. Reviewer cannot record signed acknowledgment; director cannot review. Direct action requests must fail server-side even if a button is accidentally rendered. Use profiles with no combined roles; region/role can never be supplied by profile fields. Actual: ____ Status: ____ Notes: ____

### SEC-05 — Restricted and sensitive records

Role: ched.admin/reviewer then ched.case. Paste /ched/case-records and actual restriction/clearance/official-order URL into each role. Expected ordinary admin/reviewer denied, case officer permitted only in own region. Sensitive incident details require incidents.view for CHED; own HEI sees its incident. Admin is not a superuser. Public guidelines must never list signed personal records as blank templates. Actual: ____ Status: ____ Notes: ____

### SEC-06 — Private export ownership, expiry and revoked authority

Role: ched.analyst, then other CHED account. Generate CSV; note /exports/{id}; another account tries it: denied. Expired export returns unavailable and cannot download. Revoked/disabled/unverified exporting user must not generate/download with a retained session; queue job rechecks authority. Real revocation UI is incomplete, so use the existing automated tests for that branch, not SQL edits to a real account. Actual: ____ Status: ____ Notes: ____

### SEC-07 — Business audits, hashes and immutable snapshots

Role: QA tester with read-only demo DB. After E2E-01, inspect Q4/Q5/Q13. Expected activity.revised, submission/review/acknowledgment and applicable evidence/report/case/clearance/corrective actions record the correct actor/record; document hashes remain unchanged; previous snapshot/revisions remain. Failed actions must not leave partial business records. UI denial alone is insufficient. Browser login/profile history is not a complete audit module. Actual: ____ Status: ____ Notes: ____

### Deadline simulation in automated tests

Never change system time or edit production timestamps. Existing Cmo24AlignmentTest freezes time with `$this->travelTo(CarbonImmutable::parse('2026-10-07 10:00', 'Asia/Manila'))`; fake queues/storage isolate test artifacts. Its incident data provider verifies before/exact/after 48 hours. LakbayWorkflowTest verifies working days, matching holiday version/locality, and timezone preservation. Run:

```powershell
php artisan test --compact tests/Feature/Cmo24AlignmentTest.php --filter=incident
php artisan test --compact tests/Feature/LakbayWorkflowTest.php --filter=working_day
php artisan test --compact tests/Feature/LakbayWorkflowTest.php --filter=holidays
php artisan test --compact tests/Feature/LakbayWorkflowTest.php --filter=deadline
```

Expected: filtered tests genuinely pass. Failure: read the specific assertion and baseline rather than swapping 24/48/72-hour values arbitrarily. Test fake scanner responses prove branches, not a real malware scan; the demo worker provides the separate native-scanner check.

### API-01 — Actual endpoint inventory and unauthenticated requests

Role: logged out. This application has **no dedicated REST/Bearer-token API**. `routes/web.php` supplies the endpoints below, and Filament/Livewire handle panel forms with sessions, CSRF and signed component state. Do not call invented /api/applications endpoints or install Sanctum to test them.

| Method/path | Authentication / input | Expected response |
| --- | --- | --- |
| GET /, /guidelines, /faq | Public | HTML 200 |
| GET /hei/login, /ched/login | Public | Login HTML 200 |
| GET /hei/activities, /ched/submissions etc. | Session, panel access, scoped permission | Authorized HTML; guest login redirect; wrong role 403 |
| GET /invitations/{token} | Valid unaccepted/unexpired token | Acceptance HTML; invalid/used/expired token 404 |
| POST /invitations/{token} | CSRF, valid token, password + password_confirmation ≥12 | Redirect/account created; invalid fields return validation, invalid consumption rejected |
| GET /documents/{public_id} | Session + enabled MFA + document authorization, clean scan | Private download 200; guest 302/401; denied 403; quarantine 409; missing 404 |
| GET /documents/{public_id}/preview | Same; PNG/JPG only | Image 200; PDF preview 415 |
| GET /exports/{id} | Session + enabled MFA + exporting owner/authority, valid lifetime | Private CSV/PDF or denial/expiry |
| GET /templates/{id} | Public, but approved/effective clean blank template only | Download or 404; throttled |
| POST /livewire-…/update and upload-file | Session/CSRF/Livewire signed state | Framework responses; obtain actual installed route from Network/route:list |

Exact public/guest smoke commands:

```powershell
curl.exe -I http://127.0.0.1:8001/
curl.exe -i http://127.0.0.1:8001/hei/activities
curl.exe -i -H "Accept: application/json" http://127.0.0.1:8001/documents/does-not-exist
curl.exe -i http://127.0.0.1:8001/templates/999999
php artisan route:list --path=api
```

Use browser F12 → Network for authenticated requests. A cookie-free curl command cannot prove an authenticated authorization boundary. Do not fabricate Livewire JSON; automated Livewire tests exercise its real Actions. Actual: ____ Status: ____ Notes: ____

### API-02 — CSRF, forbidden and missing records

Role: test browser, logged out and authenticated. Cookie-free POST to an invitation acceptance URL with no CSRF must be rejected; capture status (normally 419). Authenticated request to another institution must be 403/404, not 200 with private contents. Missing /hei/activities/999999 returns unavailable, not another record. Record actual HTTP response. Do not claim 422 for every browser form error: Filament/Livewire often returns a successful transport with validation in component state, while upload failures may be 422. Actual: ____ Status: ____ Notes: ____

## 12. Deliberate failure and browser checks

### ERR-01 — Server unavailable and restart

Role: local tester, only demo server. Stop its terminal with Ctrl+C; refresh /hei/overview: browser connection error, not success. Restart same --env=local server and reload. Expected session/data remain if unchanged; no duplicate submission due to refresh. Never stop a normal/production service. Actual: ____ Status: ____ Notes: ____

### ERR-02 — Network interruption during upload/save

Role: HEI in disposable demo. F12 Network → Offline after starting a dummy upload; restore Online and retry. Expected no success claim for unfinished transfer; no usable file before server save/scan; refresh and Q4 identify whether a record actually exists before retrying. Use DevTools throttling to observe loading states. Record any infinite spinner or ambiguous partial save as a defect; do not assume network recovery UX is implemented perfectly. Actual: ____ Status: ____ Notes: ____

### ERR-03 — Database unavailable (isolated copy only)

Role: technical tester; blocked for an ordinary non-developer. Stop demo services; use a disposable clone/environment with an intentionally nonexistent/unwritable DB path, not this populated QA file or the normal DB. Request a page that queries the DB. Expected controlled failure/log evidence; never false success. Development debug may expose diagnostics; production must use APP_DEBUG=false. Restore the clone configuration and verify records after restart. This destructive-environment fault was not executed on the shared workstation. Actual: ____ Status: ____ Notes: ____

### ERR-04 — Expired session during a form action

Role: HEI. Open a form in tab A; sign out in tab B; submit tab A. Expected authentication/session-expiry handling, no record saved under an old account; log in, inspect persisted state, retry once. Watch for 419/redirect and verify Network. A stale visible page is not active authorization. Actual: ____ Status: ____ Notes: ____

### ERR-05 — Duplicate/stale concurrent actions

Role: HEI/reviewer/director. Open the same unsubmitted/review-ready record in two tabs; submit/review/acknowledge in the first and repeat in the second. Expected exactly one current submission/review/letter and clear rejection of stale action. SQLite tests check guard behavior, but multi-process PostgreSQL locking is still unverified. Q2/Q5/Q6 confirm no duplicates. Actual: ____ Status: ____ Notes: ____

### ERR-06 — Reproduce misleading New submission defect

Role: suc.official. /hei/submissions → New submission → choose QA SUC → Create. Expected desired UX: button absent or supported guided flow; actual observed: Livewire 500, **LogicException: Creation is not available**. Close error overlay and reload; no new submission. Supported route remains Activity/Calendar → Submit / resubmit. Log/report QA-BUG-001, not a failed setup. Do not fix by inserting unversioned submissions directly. Actual: ____ Status: ____ Notes: ____

### UI-01 — Desktop and phone-width navigation/forms

Role: HEI and CHED. Test Chrome and Edge, viewport 1440×900 and 390×844. Start here → sidebar sections → long Activity form and document review; collapse sidebar, open modals, scroll footer buttons, inspect tables/menus. Expected readable labels, reachable actions, no page-wide overflow or obscured validation. CHED workspace 390px received a basic no-overflow smoke check; other screens need manual results. Actual: ____ Status: ____ Notes: ____

### UI-02 — Keyboard and accessibility

Role: tester. Without mouse, Tab/Shift+Tab through login, sidebar, modal fields, file input and footer actions; Enter/Space activate; Esc closes cancellable modal; focus returns sensibly. Check visible focus, labels, errors announced/associated, readable contrast, 200% zoom, dark/light modes. Verify both public FAQ expansion and panel dropdowns. This is a checklist, not an accessibility certification. Actual: ____ Status: ____ Notes: ____

### UI-03 — Refresh/back, pagination and empty/loading states

Role: HEI and CHED. Search qa-no-such-record; clear; switch document page size; Next/previous; open View then Back; refresh after action; inspect two tabs with different records. Expected scoped results, no accidental re-submit, no stuck loading indicators; success corresponds to persisted DB state. There is no delete confirmation to invent; corrective closure has a confirmation. Actual: ____ Status: ____ Notes: ____

### UI-04 — Manila timestamps and meaningful table labels

Role: HEI and CHED. Compare seeded future plan's 09:00 Manila input with Activity list/raw details; compare submission list receipt with Start here Recent submissions. Desired: consistent labeled Manila times. Observed defect: table prints UTC 01:00 for planned 09:00 and receipt 02:34 versus workspace 10:34; headers repeat **Latest version** for category/date. Log QA-BUG-002/003. DB UTC storage is correct; do not “fix” it by shifting records. Actual: ____ Status: ____ Notes: ____

## 13. Execution order and coverage

Run bootstrap → genuine scans → --prepare → ongoing worker/server → public/login/MFA → HEI references/plans/files → category/late/retained-evidence checks → E2E-01 → reports → monitoring/corrective → cases/clearance → reporting/exports → security → fault/browser checks → automated regression → defects/results. Use separate records for negative tests and note IDs. Restore a fresh baseline with demo:reset only when you deliberately want to discard all QA work.

The ten most important checks first:

1. AUTH-01/AUTH-03: real HEI/CHED login and MFA.
2. SEC-02/SEC-03: institution and regional isolation.
3. FILE-01/FILE-03: upload, genuine scan, quarantine, retrieval.
4. HEI-05/HEI-06: annual versus pre-activity submission stages.
5. HEI-07: missing/duplicate evidence and immutable snapshots.
6. E2E-01: correction → revision → complete → signed acknowledgment.
7. HEI-08/HEI-09: no ordinary Category 3 SAA; Category 2 treatment retains SAA.
8. HEI-10: PIC ratios/qualifications/contracts.
9. HEI-16/HEI-17: late incident intake, supplements, 48/72-hour anchors.
10. SEC-05/CHED-11: confidential access and exact-version external clearance.

| Feature | Automated coverage inspected/run | Manual IDs | Current evidence |
| --- | --- | --- | --- |
| Credential validation/MFA/profile/logout | PanelQaTest; LakbayWorkflowTest MFA/invitations | AUTH-01–07 | Tests; real HEI/reviewer enrollment/login |
| Scoped lists/workspaces | LakbayWorkflowTest workspace/panel/isolation tests | HEI-01, CHED-01, SEC-02–05 | Tests; real regional lists/panel denials |
| Reference creation/activity validation/history | LakbayWorkflowTest activity/identity/reclassification | HEI-02–04, 07, 09 | Automated; full manual entry pending |
| Private uploads/real scanning/previews | LakbayWorkflowTest uploads/scanner/private access; QaDemoTest | FILE-01–04, SEC-01/05 | 23 genuine scans; real PDF/image retrieval and restricted 403 |
| CMO rows/stages/signatures/PIC/contracts | Cmo24AlignmentTest matrix/applicability/readiness | HEI-05–11 | Automated; checklist walkthrough supplied |
| Corrections/review/acknowledgment | LakbayWorkflowTest full Filament cycle; Cmo24AlignmentTest | CHED-02–03, E2E-01 | Automated; seeded via Actions, full browser lifecycle pending |
| Evidence requests | Both workflow/alignment suites | HEI-12 | Automated; manual pending |
| Conclusion/ROC/incident/change | Both suites, time-travel boundaries | HEI-14–18 | Automated; genuine demo state preparation |
| Monitoring/assistance/corrective | LakbayWorkflowTest operational/corrective tests | CHED-06–07, HEI-19 | Automated; manual sign-off pending |
| Cases/clearance/reconsideration | Both suites, jurisdiction/order/timing tests | CHED-10–13, SEC-05 | Automated; seed investigation, reviewer access blocked |
| Regional/annual snapshots/CSV/PDF/dispatch | Both suites, export ownership/deadlines | CHED-08–09, SEC-06 | Automated; browser analyst walkthrough pending |
| Email/reminders/deduplication | Both suites, fake notifications | HEI-20 | Real fictional log-mail jobs completed; no real SMTP delivery |
| Candidate activation/templates/history | Both suites, publication/public-template guards | CHED-05 | Automated; candidate intentionally inactive |
| Mobile/accessibility/timeout/faults | Limited framework/permission tests | AUTH-05, ERR-01–06, UI-01–04 | Mobile smoke only; broader manual QA pending |
| Misleading create action / UTC labels | Existing tests did not catch these | ERR-06, UI-04 | Reproduced browser defects, not fixed |

## 14. Defect template and final QA report template

Copy for each defect:

```text
Bug ID:
Date/time/timezone:
Environment and app commit/working-tree version:
Role/account (fictional):
Feature and test ID:
Preconditions and record ID/reference:
Steps to reproduce:
Expected:
Actual:
Severity / priority:
Screenshot or trimmed Network/log evidence:
Error message / HTTP status:
Browser and viewport:
Related endpoint and database record:
Suspected source file:
Status / owner / retest result:
```

Severity: **Critical** = unauthorized private data access, widespread data loss or legal-record corruption; stop rollout. **High** = core submission/review/upload blocked or misleading legally significant timing; address before pilot. **Medium** = feature unreliable with a safe workaround. **Low** = wording/layout issue with limited workflow effect. Differentiate confirmed failure from unsupported feature or unresolved CHED policy.

Final report:

```text
Environment / date / commit and uncommitted changes:
Frontend / backend / database versions:
Browser / OS / viewport:
Candidate policy and publication/effectivity status:
Total manual cases: 64
Passed:        Failed:        Blocked:        Not tested:
Automated tests / assertions / failures:
Critical issues:
High-priority issues:
Medium/low issues:
Scanner / mail / queue / scheduler evidence:
Database/backup/restore/PostgreSQL validation:
Assessment: development testing / internal QA / UAT / production
Reasons and remaining rollout gates:
Tester / reviewer / sign-off date:
```

## 15. Discovered defects, incomplete features and readiness

### Confirmed application defects (not fixed in this QA task)

| ID | Severity | Reproduction / observed result | Suspected files | Workaround |
| --- | --- | --- | --- | --- |
| QA-BUG-001 | High | HEI Submissions & letters → New submission → choose institution → Create: POST Livewire update returns 500, LogicException “Creation is not available”; no submission created | app/Filament/Hei/Resources/Pages/ListSubmission.php:17; corresponding CHED header uses the same pattern; app/Filament/Support/Definitions.php:551 | Submit only through an Activity/Calendar's Submit / resubmit action |
| QA-BUG-002 | High for timing interpretation | Seeded 09:00 Manila activity prints 01:00 UTC in lists; submission receipt prints 02:34 while workspace displays 10:34 Manila | app/Filament/Support/ScopedResource.php table/infolist datetime formatting | Consult explicit Manila input, workspace timestamp and preserved UTC DB value; do not shift stored timestamps |
| QA-BUG-003 | Low | Activity table category/date columns both labeled “Latest version”; booleans appear as blank/1 | app/Filament/Support/ScopedResource.php table columns | Use View/snapshot to identify fields |

No confirmed critical data breach was found in the checks performed; that is not proof of complete security. No business logic or frontend fixes were applied. QA setup defects discovered while writing the new fixtures were corrected and regression-tested (first-run scenario state refresh and environment-name isolation); they are not claims that the application was fixed.

### Working under executed automated/limited browser checks

Two panels, invitations/MFA, scoped records, private scanned uploads, versioned activities/submissions, category/signature/deadline rules, review/correction/acknowledgment Actions, report intake, monitoring/assistance/corrective Actions, restricted case/clearance records, and regional snapshots/exports have meaningful passing automated coverage. Real Chrome login/MFA/scoped records/private PDF/image retrieval and 23 ClamAV scans were separately exercised. Individual manual cases remain unsigned until a tester completes them.

### Partially implemented or unverified

- CMO candidate exists; official activation requires verified publication/reference/+15-day effectivity, not issuance date.
- Category 3 timing, SAA coverage, change-report formal anchor, exact August cutoff, threshold/holiday/receipt/retention interpretations remain CHED decisions.
- Signed document/notarial declarations are registry metadata, not cryptographic/legal verification. Annex K is an uploaded signed original plus stored summary, not an automatically generated legal letter.
- Requested-evidence/clearance workflows record external correspondence; they do not integrate directly with OSDS/CEB.
- Detailed itinerary rows exist in the domain/schema but the general activity form has no dedicated structured itinerary editor; upload the signed Annex J in this release.
- Some supplementary form/signatory fields are optional metadata, not full internal HEI form editors.
- Session idle expiry/recovery flows, accessibility, large datasets, interrupted networking, real SMTP, production scanner service, deployment/backup restoration and PostgreSQL concurrency have not all been executed on this workstation.
- Source contains deliberate unconfirmed legacy settings (config/lakbay.php incident_hours=24, unconfirmed holiday default). They are not the new candidate's 48-hour parameter; compare report snapshots/policy selection before calling them a bug.

### Missing functionality

For each item below: **This functionality is not currently implemented in the inspected codebase.**

- Public self-registration, standalone email-verification/CHED approval-wait onboarding screen.
- Dedicated PIC/faculty, auditor or national OSDS account/panel; national consolidation dashboard.
- Institutional profile edit/deactivate controls; a full user/membership management interface beyond invitations/profile/bootstrap.
- Excel CLOCA import and native XLSX export (CSV/PDF exports exist).
- Persistent notification inbox/bell/read-unread management.
- Full diagnostic/RACP editors, master student/guardian/medical-history records.
- Comprehensive signed-document replacement/supersession UI or record deletion.
- In-app HEI signing/notarization, electronic signature verification or automated CEB decision making.
- Public complaint intake, public sanctions publication, national dispatch integration.
- Dedicated application REST/Bearer-token API/mobile client.

Current assessment: suitable for **development testing and internal QA**, with known defects. The guide supports UAT preparation; it does not grant UAT sign-off or production readiness. Fix confirmed defects with authorization, finish manual walkthroughs, validate PostgreSQL/deployment/recovery and policy activation before rollout.

## 16. Executed validation and master results log

Execution details and the complete blank checklist follow below. Treat every blank manual result as NOT TESTED, even if its underlying automated tests passed.

### What was actually run on 7 October 2026

Windows / PHP 8.4.26 / Laravel 13.34.0 / Filament 5 / Livewire 4 / SQLite demo / Node 24 / Vite 8.3.3 / isolated headless Chrome. Existing uncommitted CMO work was preserved; this QA task added only demo setup/fixtures, tests, documentation and a Composer script, plus required formatting. No policy activation, real email, dependency update, deployment or Git push occurred.

Trimmed real command output:

```text
php artisan test --compact
{"tool":"phpunit","result":"passed","tests":184,"passed":184,"assertions":617,"duration_ms":64176}

php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
{"tool":"phpstan","result":"passed","errors":0}

php vendor/bin/pint --dirty --format agent
{"tool":"pint","result":"passed"}

npm run build
vite v8.3.3 building client environment for production...
✓ built in 1.56s

composer validate --no-check-publish
./composer.json is valid

php artisan lakbay:demo --reset [installed scanner paths supplied]
20 fictional accounts and 9 activity plans seeded.
Uploads remain quarantined until genuine scans succeed.
QA database: database/qa-demo.sqlite; normal .env and database are untouched.

php artisan queue:work --env=local --stop-when-empty --tries=1 --timeout=180
App\Jobs\ScanDocument ... DONE  (23 genuine scans)
Worker STOPPED Queue empty

php artisan lakbay:demo --prepare
QA states prepared: late annual intake, correction request,
acknowledged/completed activity, late incident, restricted investigation.

Local HTTP checks
Home HTTP 200
Guest HEI HTTP 302; redirect http://127.0.0.1:8001/hei/login
Guest private JSON HTTP 401
Missing template HTTP 404

php artisan route:list --path=api
ERROR Your application doesn't have any routes matching the given criteria.
```

The no-API route message is expected, not an application test failure. Vite emitted an optional-fontaine optimization warning but built successfully. Existing dependencies were inspected/validated, not reinstalled or upgraded unnecessarily. Four baseline demo-guard tests passed initially; the final fixture suite has five tests, 25 assertions; added panel tests have seven cases, 44 assertions. The full 184-case run includes them.

Browser evidence: public home/guidelines/FAQ and login screens rendered; HEI and reviewer signed in with normal authenticator enrollment. HEI saw six own plans and four current submissions; reviewer saw eight IV-A plans and three IV-A institutions, not the Region III HEI. Cross-panel and reviewer restricted-case requests produced 403. Authorized SAA PDF retrieval returned 200/application-pdf, image preview returned 200/image-png, both private/no-store; restricted official-order retrieval returned 403. CHED workspace at width 390 had scrollWidth 390. The misleading submission Create action produced the observed 500 and logged LogicException. These are limited browser checks, not completion of all 64 manual cases.

The two fictional browser-tested MFA enrollments were cleared afterward **only in qa-demo.sqlite**, so manual testers can enroll their own authenticators at first login. No MFA checks were disabled. Original .env and database/database.sqlite SHA-256 hashes were unchanged across this task, and the QA database passed SQLite integrity_check. The QA Chrome instance was closed. The local demo server was left running at port 8001; its hidden parent process is PID 40384 on this workstation for this run. To stop that specific server and its children, run `taskkill /PID 40384 /T` (first confirm that PID still belongs to this demo server; IDs can be reused). Otherwise use Ctrl+C when starting services in your own terminals. Worker scan/mail runs finished; start the ongoing QA worker from section 2 before new uploads/exports.

All SQL verification queries were executed read-only against the demo schema. Local production-target PostgreSQL, full multi-role browser lifecycle, live dependency/security audits, real SMTP, timeout duration, fault-injected database outage, load and accessibility testing, and backup restoration were not executed. Do not report them as passed. Default accounts/passwords and scanner bypasses were not added to normal seeding.

### Master checklist / blank results log

Actual = concise observed result; Status = PASS / FAIL / BLOCKED / NOT TESTED. Record date, browser and fixture revision at the top of your copied log.

| ID | Test | Role | Actual | Status | Notes / defect ID |
| --- | --- | --- | --- | --- | --- |
| AUTH-01 | HEI login, MFA, refresh and direct link | See case role | ____ | ____ | ____ |
| AUTH-02 | Bad and missing credentials | See case role | ____ | ____ | ____ |
| AUTH-03 | CHED login and role-specific navigation | See case role | ____ | ____ | ____ |
| AUTH-04 | Wrong TOTP and recovery | See case role | ____ | ____ | ____ |
| AUTH-05 | Logout and session lifetime | See case role | ____ | ____ | ____ |
| AUTH-06 | Invitation acceptance and password reset | See case role | ____ | ____ | ____ |
| AUTH-07 | Profile and password persistence | See case role | ____ | ____ | ____ |
| HEI-01 | Workspace counts and links | HEI | ____ | ____ | ____ |
| HEI-02 | Academic period and annual calendar | HEI | ____ | ____ | ____ |
| HEI-03 | Create Category 1 plan | HEI | ____ | ____ | ____ |
| HEI-04 | Activity validation boundaries | HEI | ____ | ____ | ____ |
| FILE-01 | PDF/PNG upload, scanning and retrieval | HEI | ____ | ____ | ____ |
| FILE-02 | Rejected upload inputs and signature mismatch | HEI | ____ | ____ | ____ |
| FILE-03 | Quarantine and worker failure | HEI | ____ | ____ | ____ |
| FILE-04 | Form version/coverage and replacement history | HEI | ____ | ____ | ____ |
| HEI-05 | Candidate checklist and annual-stage separation | HEI | ____ | ____ | ____ |
| HEI-06 | Submit a new annual package | HEI | ____ | ____ | ____ |
| HEI-07 | Missing evidence, duplicate submission and locked history | HEI | ____ | ____ | ____ |
| HEI-08 | Category 2 and ordinary Category 3 requirements | HEI | ____ | ____ | ____ |
| HEI-09 | Category 3 treatment and explicit reclassification | HEI | ____ | ____ | ____ |
| HEI-10 | PIC ratios, qualifications and contracts | HEI | ____ | ____ | ____ |
| HEI-11 | Late intake, working days and unresolved timing | HEI | ____ | ____ | ____ |
| HEI-12 | Requested evidence response | HEI | ____ | ____ | ____ |
| HEI-13 | Readiness is independent of acknowledgment | HEI | ____ | ____ | ____ |
| HEI-14 | Record actual conclusion and completion report | HEI | ____ | ____ | ____ |
| HEI-15 | Changed implementation and linked incidents | HEI | ____ | ____ | ____ |
| HEI-16 | Incident initial notice and signed supplement | HEI | ____ | ____ | ____ |
| HEI-17 | Incident and ROC elapsed-hour boundaries | HEI | ____ | ____ | ____ |
| HEI-18 | Immediate change notice and original/revised comparison | HEI | ____ | ____ | ____ |
| HEI-19 | Corrective response then CHED closure | HEI | ____ | ____ | ____ |
| HEI-20 | Workflow mail and absence of notification inbox | HEI | ____ | ____ | ____ |
| CHED-01 | Regional workspace, lists, search and filters | CHED | ____ | ____ | ____ |
| CHED-02 | Request corrections and inspect the timeline | CHED | ____ | ____ | ____ |
| CHED-03 | Mark complete and record signed Annex K letter | CHED | ____ | ____ | ____ |
| CHED-04 | Institution/campus creation and unavailable editing | CHED | ____ | ____ | ____ |
| CHED-05 | Candidate configuration and activation gate | CHED | ____ | ____ | ____ |
| CHED-06 | Monitoring visit, fourteen criteria and sign-offs | CHED | ____ | ____ | ____ |
| CHED-07 | Technical assistance | CHED | ____ | ____ | ____ |
| CHED-08 | Regional snapshot and CSV/PDF exports | CHED | ____ | ____ | ____ |
| CHED-09 | Annual M&E and external OSDS correspondence | CHED | ____ | ____ | ____ |
| CHED-10 | Signed complaint and recorded procedural history | CHED | ____ | ____ | ____ |
| CHED-11 | Exceptional clearance filing and CHEDRO/OSDS routing | CHED | ____ | ____ | ____ |
| CHED-12 | External decision, receipt and one reconsideration | CHED | ____ | ____ | ____ |
| CHED-13 | Signed restriction and case closure | CHED | ____ | ____ | ____ |
| E2E-01 | Correct, resubmit, acknowledge and verify both sides | See case role | ____ | ____ | ____ |
| SEC-01 | Logged-out access and private document URLs | See case role | ____ | ____ | ____ |
| SEC-02 | HEI cross-institution URL and selector isolation | See case role | ____ | ____ | ____ |
| SEC-03 | Regional isolation | See case role | ____ | ____ | ____ |
| SEC-04 | Panel and action permissions | See case role | ____ | ____ | ____ |
| SEC-05 | Restricted and sensitive records | See case role | ____ | ____ | ____ |
| SEC-06 | Private export ownership, expiry and revoked authority | See case role | ____ | ____ | ____ |
| SEC-07 | Business audits, hashes and immutable snapshots | See case role | ____ | ____ | ____ |
| API-01 | Actual endpoint inventory and unauthenticated requests | See case role | ____ | ____ | ____ |
| API-02 | CSRF, forbidden and missing records | See case role | ____ | ____ | ____ |
| ERR-01 | Server unavailable and restart | See case role | ____ | ____ | ____ |
| ERR-02 | Network interruption during upload/save | See case role | ____ | ____ | ____ |
| ERR-03 | Database unavailable (isolated copy only) | See case role | ____ | ____ | ____ |
| ERR-04 | Expired session during a form action | See case role | ____ | ____ | ____ |
| ERR-05 | Duplicate/stale concurrent actions | See case role | ____ | ____ | ____ |
| ERR-06 | Reproduce misleading New submission defect | See case role | ____ | ____ | ____ |
| UI-01 | Desktop and phone-width navigation/forms | See case role | ____ | ____ | ____ |
| UI-02 | Keyboard and accessibility | See case role | ____ | ____ | ____ |
| UI-03 | Refresh/back, pagination and empty/loading states | See case role | ____ | ____ | ____ |
| UI-04 | Manila timestamps and meaningful table labels | See case role | ____ | ____ | ____ |


Additional run log:

| Run/date | Environment / commit | Tester | Test ID / record reference | Actual | Status | Evidence / bug |
| --- | --- | --- | --- | --- | --- | --- |
| ____ | ____ | ____ | ____ | ____ | ____ | ____ |
| ____ | ____ | ____ | ____ | ____ | ____ | ____ |

### FIX-04 manual retest

Use fictional records and local mail logging/capture only. In CHED → Submission inbox, enable **Open review / outstanding corrections** and sort **First filed activity (Asia/Manila)** ascending. Open a pending package, request corrections, and confirm its attention text becomes **Corrections outstanding**. Check a before-start and reached-start package; the latter is an operational cue, not a conduct approval. Revise an HEI plan without submitting: the original receipt's schedule and snapshot must remain unchanged. A superseded/closed package must disappear from the attention filter.

In HEI → Prepare changes (Annex I), save a draft: no reviewer email. Send immediate notice or file the signed report: one minimal CHED notice should be queued. Process the local worker and inspect captured mail. Only active, email-verified regional users with records.view and submissions.review receive it; record-only staff, HEIs, unverified/inactive users and other regions do not. Repeat the same job: no second completed delivery. Revoke reviewer access before processing and verify no delivery. Signed follow-ups are separate intakes; late notices remain accepted and unresolved formal RCD timing remains displayed.
