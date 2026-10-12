# LAKBAY — CHED Region IV-A (CALABARZON)

Regional off-campus activity compliance and records software. HEIs prepare plans and signed evidence; CHED reviews submissions, requests corrections, records acknowledgments, monitors activities, and prepares regional reports.

**Status:** working development baseline, pending policy confirmation and production validation. CHED acknowledgment records receipt/completeness and **does not authorize an activity**.

## Interfaces and implemented scope

| Interface | Workflow |
| --- | --- |
| / | Public guidelines, FAQs, approved blank templates, and portal links |
| /hei | Prepare → Submit & respond → After the activity → Follow up |
| /ched | Review → Acknowledge → Monitor & support → Report |

Home-page stages provide guidance; they are not automatic per-activity completion checklists.

Implemented: invitations/MFA, institution-scoped access, academic periods, CLOCA calendars, activity revisions, private scanned documents, immutable submissions, corrections/resubmissions, evidence requests, signed acknowledgments, completion/incident/change reports, monitoring, assistance, corrective actions, restricted cases, external clearance/reconsideration records, approved public templates, audit history, regional snapshots, and queued CSV/PDF exports.

## Annex B: printable CLOCA

In the HEI panel, open **Annual calendars (CLOCA)** and use **Edit CLOCA header / signatories** under More actions or on the record page. Set calendar effectivity, academic-year/semester labels, printed address and Prepared/Reviewed/Noted-by names and positions. Institution name/type remain institution-scoped. Optional letterhead images must be clean institutional PNG/JPG files up to 2 MB, selected from the private Document library.

For each linked activity, use **Revise** and complete **CLOCA printed row (Annex B)**: CHED type code, brief description, confirmed duration, itinerary locations with within/outside-region indicators, agency coordination and requested assistance. Existing schedule, personnel and total participant counts populate the remaining columns.

Choose **Preview / print CLOCA (Annex B)** on the calendar. The preview lists missing print fields; once complete, download the portrait A4 PDF with repeated table headers and page numbers. Sign externally, upload the signed PDF, then attach it through the usual **Submit / resubmit** workflow. Generated copies do not become signed evidence automatically.

New calendar submissions preserve a versioned **Preserved CLOCA (Annex B)** output. Later calendar, institution and activity edits do not change that submission's printable data. Older submissions without a frozen Annex B payload continue to use their original uploaded signed CLOCA; the app does not reconstruct them from current information. Printable completeness does not change policy submission rules or activate the CMO candidate. PDF generation currently supports up to 500 activity rows, with a 1200-character printable-cell limit.

Existing installations: run `php artisan migrate --no-interaction` for the additive Annex B columns. The development workstation's normal and isolated QA databases were backed up and migrated without reseeding.

## Annex K: CHEDRO acknowledgment letter

In **CHED -> Submissions**, review a calendar package as complete, then choose **Prepare letter (Annex K)**. Enter addressee/position, address, salutation, letter date/reference, CHEDRO Director name and CHED letterhead text. Inspect the exact submitted signed CLOCA and notarized SAA, then confirm the originals check. The app checks private scans/hashes; the checkbox records human inspection, not automatic notarization verification.

Choose **Preview / print letter (Annex K)** for the A4 PDF based on PDF page 31. Receipt date and program/course summaries come from that exact submission revision. Periods currently mean each program's earliest/latest planned activity dates; student counts are summed participations, not unique individuals. Sign externally, upload the signed acknowledgment PDF and wait for a clean scan, then choose **Record signed acknowledgment**. Keep **Preserve this submission's saved Annex K preparation** selected and use its matching particulars.

Issuance freezes the prepared letter in the acknowledgment; **Preserved letter preparation (Annex K)** is an unsigned reproduction, while **Signed acknowledgment** retrieves the official uploaded original. Preparing/printing does not acknowledge or authorize an activity. Generic/historical acknowledgment filing remains supported without inventing Annex K data. CHED drafts require signatory permission and jurisdiction; HEIs can view issued preparations for their institution. CMO activation remains pending publication. Existing installations need the additive acknowledgment-drafts migration.

## Annex C: printable sworn affidavit (SAA)

In the HEI panel, open **1. Prepare -> Sworn affidavits (SAA) -> New SAA draft**. Select the institution and academic period, then enter the HEI-head affiant's complete name, position, office address, citizenship and semester/academic-year coverage. Each semester can have its own draft. Optional letterhead uses the same private, clean-image rules as Annex B.

Choose **Preview / print SAA (Annex C)** and download the A4 PDF once the core fields are complete. The full Annex C undertakings and signature/notarial blocks are included. Unknown execution/notarial particulars remain blank; optional entered particulars are printable information, not verification that signing or notarization occurred. Do not enter government ID numbers or copies in the preparation form.

Print, sign and notarize externally, then upload the signed PDF as SAA evidence. In a calendar/activity **Submit / resubmit** dialog, optionally select the matching **SAA preparation draft** to preserve its exact printable data with the submission. This selection does not replace signed evidence. **Preserved SAA draft (Annex C)** opens that frozen copy; later draft edits cannot alter it. A correction keeps the previous copy unless another draft is selected. CHED users can view drafts within their jurisdiction but cannot prepare/edit them. Historical submissions without an Annex C payload retain their original signed upload.

Existing installations must run `php artisan migrate --no-interaction` for the additive `accountability_affidavits` table. Preparing Annex C does not activate the CMO candidate or establish official affidavit coverage rules.

## Annex D: diagnostic tool of HEI

In **HEI -> Activity plans -> New activity / Revise**, expand **Diagnostic tool of HEI (Annex D)**. Complete the 20 Yes/No criteria in five groups, remarks, an explicit HEI recommendation, and Prepared/Reviewed/Noted-by details (including the preparer's contact/email). Basic information comes from the activity and HEI profile: dates, venue, office, category, participant totals and PIC/APIC. Optional private letterhead follows the clean-image rules used by Annex B/C.

Save the plan, then choose **Preview / print diagnostic (Annex D)**. Incomplete assessment drafts can be saved and previewed; printable completion requires all answers and signatory fields. The normal A4 form follows the Annex D sample; longer remarks paginate with repeated table headings. No automatic scoring, signature, HEI approval or CHED authorization is inferred. Where applicability needs explanation, use remarks; the sample offers Yes/No rather than a separate N/A answer.

Sign externally and retain/upload the accomplished diagnostic evidence under the applicable checklist or requested-evidence workflow. Generating a PDF does not satisfy an evidence request automatically. Activity revisions preserve previous answers. New submissions freeze each prepared diagnostic's institution details, activity version, criteria, answers and template metadata; **Preserved diagnostics (Annex D)** opens those exact copies. Historical submissions without this payload are not reconstructed from current records. Run `php artisan migrate --no-interaction` for the additive diagnostic column on existing installations.

## Annex E: risk assessment and contingency plan (RACP)

In **HEI -> Activity plans -> New activity / Revise**, expand **Risk assessment and contingency plan (Annex E)**. Enter Low/Medium/High ratings and contingency measures for all six named risk areas, optional other risks, the five emergency-contact rows and optional other agencies. Complete Prepared/Reviewed/Noted-by details and the preparer's contact/email. Basic information comes from the activity and HEI profile; optional clean private letterhead follows Annex B/C/D rules.

Save, then choose **Preview / print RACP (Annex E)**. Partial plans can be saved and previewed; printable completion requires the named risk ratings/measures, emergency contact persons/details, signatories and any added rows. The issuance date can remain blank for external signing. The PDF includes the sample's certification and empty signature spaces. Ratings are entered by the HEI; the app does not calculate an overall score, verify completion before conduct, or create signed certification/evidence automatically. Enter activity-level measures rather than individual medical histories.

Sign externally and retain/upload the accomplished RACP through the applicable evidence/request workflow. Activity revisions preserve earlier plans. New submissions freeze the exact RACP information, risk/contact labels, certification wording and template references; use **Preserved RACPs (Annex E)** to retrieve them. Older submissions without this payload use original evidence instead of current-data reconstruction. Run `php artisan migrate --no-interaction` for the additive RACP column on existing installations.

## Annex F: certification

In **HEI -> 1. Prepare -> Certifications (Annex F) -> New certification draft**, select the institution/academic period and enter the authorized HEI official and designation. Complete all **14 requirement rows and 16 supporting-document checks** from both template pages. Attest only confirmed compliance; pending rows remain draft-only. Every N/A decision needs a reason, with supporting checks cleared. The academic period organizes selection and does not establish legal certification coverage.

Use **Preview / print certification (Annex F)**. PDF completion requires every row attested with supporting availability confirmed, or N/A with an explanation, plus official/position and HEI profile address. The normal output retains both template pages, including the on-request instruction and final attestation. N/A explanations print on an additional applicability-notes page. Unknown issuance date/place remain blank. Preparing the form does not verify compliance, authenticity, signing authority or official execution.

Sign externally and upload the signed certification as evidence. Calendar/activity **Submit / resubmit** dialogs optionally select a matching certification draft; this preserves its exact printable information without replacing the policy checklist or signed evidence. **Preserved certification (Annex F)** opens the frozen copy. Corrections without another draft selected keep the previous payload; explicitly selecting a revised draft freezes the replacement on the new revision. Historical submissions are not reconstructed. CHED users can read scoped drafts but cannot prepare/edit them. Existing installations require `php artisan migrate --no-interaction` for the additive `certification_drafts` table.

## Annex G: report of completion (ROC)

In **HEI -> 3. After the activity -> Prepare ROC (Annex G) -> New ROC draft**, choose the exact activity-plan revision being reported. Proposed dates, category, responsible office and PIC/APIC are preserved from that revision, alongside the HEI profile at draft creation. Check the prefilled venue/counts and enter actual values, actual conclusion, implementation status, outcome summary, incident declaration and certifying official/position. Explain changes or incidents where applicable; link existing incident reports/initial notices without copying personal or medical details into the ROC.

Use **Preview / print ROC (Annex G)**. Partial drafts can be saved and previewed; complete print fields are required to download the A4 PDF. Unknown issuance dates remain blank. The proposed dates on the printed template remain separate from actual conclusion, which anchors the existing 72-hour reporting deadline. Saving or printing a draft does not record completion, file a report, or verify the official's authority.

Print and sign externally, upload the original as a signed **Completion report** PDF and wait for a clean malware scan. Return to the draft and use **File signed ROC** to select that original. Filing uses the draft's data, preserves it on the immutable report, locks the filed draft, and records actual completion without overwriting a previously recorded conclusion. Late reports remain accepted and flagged. Retrieve **Preserved ROC (Annex G)** from **Completion reports**; generated copies remain unsigned preparation copies, distinct from the uploaded signed original.

CHED preparation records are read-only and jurisdiction-scoped. Incident-bearing preparations/outputs require incident-view permission for CHED. Legacy manual signed-report intake remains supported; historical reports without an Annex G payload are not reconstructed. Existing installations require the additive draft/report migrations; run the normal Laravel migration command. The development workstation's normal and isolated QA databases were backed up and migrated without reseeding. This does not activate the CMO candidate.

## Annex H: incident report

Open **HEI -> 3. After the activity -> Prepare incident (Annex H) -> New incident draft**. Choose the activity-plan revision and, for a follow-up, the incident being supplemented. Basic proposed information is preserved from that revision. Enter occurrence in Manila time, exact incident location/type, necessary persons/roles/remarks, brief description, immediate actions, current status, supporting-document details and certifying official/position. Unknown issuance dates remain blank.

**Save/preview does not notify CHED.** Use **Send initial notice / file signed report** promptly: leave its signed-document selector empty to send an initial notice without waiting for signing/supporting evidence. Initial intake still needs occurrence, location/type and factual summaries. CMO 24 uses 48 elapsed hours from occurrence; historical records keep their versioned deadlines. Missing compliance documents do not block incident intake.

Use **Preview / print incident (Annex H)** for the eight-section A4 form once print fields are complete. Sign externally, upload a signed **Incident report** PDF and wait for a clean scan. To follow an already received notice, create a new preparation selecting that incident, then file the signed report. Each filing freezes the preparation and locks it; supplements retain the original occurrence/deadline and do not overwrite prior persons or narratives.

Retrieve **Preserved incident (Annex H)** from **Incident reports**. Generated PDFs remain unsigned preparation copies; signed originals and supporting files remain separate protected documents, not automatically merged into the PDF. Personal incident evidence must use sensitive document storage. All incident preparation/output routes require MFA and institutional ownership or regional CHED incident-view permission; CHED preparation screens are read-only. Legacy reports are not reconstructed from current information. Apply the additive draft/report migrations on existing installations; the development workstation's normal and isolated QA databases were backed up and migrated without reseeding, with existing records verified unchanged. Preparation does not activate the CMO candidate.

## Annex I: report of change of details

Open **HEI -> 3. After the activity -> Prepare changes (Annex I) -> New change draft**. Changes may be reported before or during an activity too. First revise the activity plan, then choose its original and later revisions. The form prints previously submitted/revised date/time, venue, PIC/APIC, participant totals and other recorded differences. Add other particulars, reason, supporting-document details, preparer contact/email and Prepared/Reviewed/Noted signatories.

Select the electronic receipt containing the original revision; historical/superseded receipts remain eligible. If the original was submitted outside LAKBAY, supply its date and external receipt reference instead. This is explicitly HEI-entered information, not a verified electronic receipt. Draft creation dates are never substituted for submission dates.

Saving or printing does not notify CHED. Use **Send immediate notice / file signed report** with no document selected for prompt CMO notice. Complete print fields, download the Annex I A4 PDF, sign externally, upload a signed **Change report** PDF through private scanning and select the clean original for signed filing. To follow an already received notice, create another preparation selecting it, retaining its revision pair, change time and original submission reference.

Filing freezes the preparation and prevents edits/duplicate filing. **Change reports -> Preserved change (Annex I)** retrieves the frozen unsigned preparation copy; uploaded signed originals and supporting files remain separate protected documents. Follow-ups preserve original notice timing and policy/deadline parameters. CMO formal deadlines remain unresolved; historical draft deadlines retain their original calculation. CHED preparation screens are read-only and jurisdiction-scoped. Existing reports without an Annex I payload are not reconstructed.

Both development SQLite databases were backed up and migrated without resetting/reseeding, with existing records checked unchanged. This does not activate the CMO candidate or prove browser/PostgreSQL readiness.

## Annex J: detailed itinerary

Open **HEI -> Activity plans -> New activity / Revise -> Detailed itinerary (Annex J)**. Add/reorder itinerary rows: date/time in Asia/Manila, activity, venue/location, DOT accreditation information where applicable, and responsible persons. Complete Prepared/Reviewed/Noted names/positions and preparer contact/email. Basic information is populated from the activity plan and HEI profile. Optional private letterhead uses the existing clean institutional image rules.

Save, then choose **Preview / print itinerary (Annex J)**. Partial signatory preparations may be saved and previewed; each entered itinerary row needs its core fields. PDF download requires at least one row and complete print particulars. The A4 output follows the template's two sections and six-column table, with repeated headers/continuation rows for long itineraries. DOT information is recorded without verifying accreditation.

Print/sign externally and upload the signed **Detailed Itinerary** PDF through normal private scanning. Attach it in the applicable submission/evidence workflow; generated copies are unsigned and do not replace signed evidence or establish approval. New submissions preserve **Preserved itineraries (Annex J)**, keyed to exact activity revisions, including header, rows, signatories and template references. Later plan/profile changes do not alter those copies; older submissions are not reconstructed.

Omitting itinerary/Annex J data from a domain revision preserves existing information; explicitly clearing rows removes them from the new revision only. CHED may read/print within its jurisdiction but cannot prepare an HEI itinerary. Existing installations require the additive activity-version migration. Browser and PostgreSQL checks remain separate from automated verification.

## Annex L: student consent form

Open **HEI -> Activity plans**, find the activity, and choose **Prepare / print consent (Annex L)** in its actions menu or record page. The preparation uses that exact activity revision for title, Manila dates, organizing office and venue, plus the current HEI profile. Optionally enter student name, program/year level, student/contact number and dates; parent/guardian name, relationship, emergency contact and dates; and private clean letterhead.

Choose **Preview filled form**, then **Download this printable PDF**, or download directly from the preparation page. Leave personal fields blank when distributing copies for paper completion. The A4 output includes student consent, parent/guardian acknowledgment, the sample privacy notice and empty signature spaces. Dates and names do not establish signing or consent.

Personal fields are used only for this response; they are not saved in a student register, working draft, submission snapshot, audit metadata or validation-session input. Download before closing the page. Generate one copy per participant if needed. The app does not infer guardian applicability or create a digital consent/approval status. Print and sign externally; retain/upload signed PDFs as **Sample Consent Form** sensitive evidence through the existing scanned document workflow when applicable. Signed originals remain separate; generating this PDF does not fulfill evidence requirements. No database migration is needed for Annex L.

## Annex M: CHEDRO monitoring and evaluation

Sign in as a regional CHED monitoring officer and open **CHED -> Monitoring & support -> Monitoring visits -> New monitoring record**. The form follows the template: period/date/type and HEI respondent/position, all 14 Yes/No checks with verification examples and remarks, good practices/deficiencies/improvements/other findings, Prepared/Reviewed/Noted names and positions, and HEI conforme name/date. Region, monitoring personnel and HEI name/type are captured from authorized records. Confirm/edit verification examples to describe evidence actually reviewed; avoid personal medical details or restricted-case narratives.

Save, then use **Preview / print monitoring (Annex M)** in the record's actions. The normal portrait A4 output follows both PDF pages 33-34; long narratives continue without discarding text. Optional signatory particulars print blank for external completion. The operational overall rating remains a human-entered reporting field, not an Annex M score, sanction or activity authorization.

New monitoring records freeze the complete printable payload. Later profile/staff/configuration changes do not alter it; recorded checks are not editable through this workflow. Print/sign externally and retain/upload the signed **Monitoring and Evaluation** PDF through the private scanned Document library, selecting the final Annex M template where applicable and a reference matching the monitoring record. Generated copies do not establish signatures or HEI conforme. Existing records retain their original data/evidence and are not reconstructed. Apply the additive monitoring-record snapshot migration on existing installations; CMO activation remains pending.

## Technology

PHP 8.4, Laravel 13, Filament 5, Livewire 4, Blade, Tailwind CSS 4, Vite 8, Node.js 24, Spatie Laravel Permission, and DomPDF. SQLite is used locally; PostgreSQL 17 is the production target. Private documents use ClamAV; database queues handle scanning, notifications, and exports.

One modular Laravel application serves both panels. Business transitions live in Actions; record authorization combines permissions, Policies, and institutional/regional checks. Lockfiles record exact dependency versions.

## Fresh clone setup

Install Git, PHP 8.4, Composer 2, and Node.js 24. PHP needs curl, fileinfo, mbstring, openssl, intl, GD, ZIP, and PDO SQLite; PostgreSQL also needs PDO PostgreSQL. These commands assume PHP, Composer, and npm are on PATH:

    git clone https://github.com/itsmeches/LAKBAY.git
    cd LAKBAY
    composer install --no-interaction --prefer-dist
    Copy-Item .env.example .env
    php artisan key:generate
    if (-not (Test-Path database/database.sqlite)) {
        New-Item -ItemType File database/database.sqlite | Out-Null
    }
    php artisan migrate --seed
    php artisan filament:assets
    npm ci --ignore-scripts
    npm run build
    php artisan lakbay:admin your-official-email@example.gov.ph
    php artisan serve --host=127.0.0.1

Open **http://127.0.0.1:8000**. The admin command prompts for a password without echoing it. There are no seeded default accounts/passwords. Sign in at /ched and enroll MFA.

These initialization commands are for a fresh clone. Do not overwrite an established .env, application key, or database. Generated Filament/Vite assets are excluded from Git; run filament:assets after dependency updates and npm run build after frontend changes.

### Existing Windows workstation

The PowerShell helper uses portable PHP under %LOCALAPPDATA%\lakbay-dev-tools\php when available, otherwise PHP on PATH:

    .\scripts\lakbay.ps1 serve
    .\scripts\lakbay.ps1 worker
    .\scripts\lakbay.ps1 admin your-official-email@example.gov.ph

Run server and worker in separate terminals. Portable tools, PHP settings, Windows scheduled tasks, accounts, and the database are machine setup and are **not included in a clone**.

## Accounts and onboarding

An administrator creates institutions/campuses and invites personnel. Invitation acceptance establishes the user's password; users then enroll MFA.

| Role | Main responsibility |
| --- | --- |
| HEI preparer | Prepare activity records and evidence |
| HEI authorized submitter | Submit packages, respond, and file reports |
| CHED records officer | Register/access permitted records and documents |
| CHED reviewer | Review submissions; request evidence/corrections |
| CHED signatory | Record signed acknowledgment of a complete revision |
| CHED monitoring officer | Monitoring, assistance, corrective follow-up |
| Restricted-case officer | Confidential cases, restrictions, external decisions |
| CHED analyst | Regional reports and exports |
| System administrator | Onboarding and bounded policy configuration |

Roles do not override ownership, verified institutional membership, jurisdiction, or confidentiality. Administrators do not automatically receive restricted-case access.

Local MAIL_MAILER=log writes invitations and workflow emails to storage/logs/laravel.log. Protect that log because it contains invitation links. Configure approved SMTP before real onboarding. Queued notifications and exports need a worker.

## Policy baseline

The application preserves the July draft and adds **CMO No. 24, s. 2026 as a candidate policy**. The order was issued on 28 September 2026; publication has not been verified. Official activation requires a publication reference and derives effectivity at publication +15 days. Do not use issuance as publication.

For local manual testing of draft/candidate policies only, set LAKBAY_ALLOW_DRAFT_POLICY=true in .env and run:

    php artisan config:clear

Unconfirmed submissions are refused outside local/testing. Production requires a confirmed, effective policy and authoritative holidays. Do not confirm a policy solely to pass a test. See [CHED clarification register](docs/CHED-clarifications.md).

### Updating an existing installation

    php artisan migrate --force
    php artisan db:seed --class=Cmo24PolicySeeder --force
    php artisan queue:restart

These additive updates retain existing records. Back up the database before deploying a migration. Do not run migrate:fresh against an existing installation.

### Trying CMO 24 locally

Select the candidate CMO 24 policy when submitting. Complete the activity's physical-activity/travel facts and PIC qualification, employment, accompaniment and first-aid references first.

- Category 1: submit the annual package from Calendars, then submit later pre-activity evidence from the individual Activity. Reusing its activity version across these two stages is supported.
- Categories 2–3: submit pre-activity evidence from Activities. Ordinary Category 3 does not require SAA. Category 3 treated as Category 2 does; existing valid HEI-term SAA evidence may be reused (coverage interpretation pending CHED confirmation). Other unclear deadlines remain unresolved.
- Evidence: one certification may cover multiple requirement rows. Include listed supporting evidence. Signed forms require PDF; permitted copies need not be marked signed.
- Incidents: the CMO candidate uses 48 elapsed hours. Missing signed evidence creates an initial notice; attach a signed report through a supplement.
- Changes: record the immediate notice after creating a revised activity version. The formal deadline remains unresolved pending CHED's interpretation of “upon conduct.”
- CHED: record clearance applications/routing and use Reporting for annual M&E snapshots, OSDS dispatch and receipt. These record external correspondence; they do not send documents to OSDS.

## Documents, scanning, and previews

Uploads accept PDF, JPG, or PNG up to **20 MB**. Signed official evidence must be **PDF**; cryptographic signatures and notarization are not verified.

1. Upload and save: the private file is Pending.
2. A worker runs ClamAV and checks the stored hash.
3. Only Clean files allow authorized downloads and PNG/JPG previews.
4. Open the document's View page for its preview, Open image, and Download actions.

Missing scanners, failed scans, and changed files keep evidence quarantined; infected files remain blocked. Incident intake can accept missing supporting evidence.

Install [official ClamAV](https://www.clamav.net/downloads), configure FreshClam, and download its signatures. Set these values in .env using your actual paths:

    LAKBAY_SCANNER_COMMAND='C:\tools\ClamAV\clamscan.exe'
    LAKBAY_SCANNER_DATABASE_PATH='C:\tools\ClamAV\database'

The database setting is optional if ClamAV already has a valid default location. Keep signatures current with FreshClam.

    php artisan config:clear
    php artisan queue:restart
    php artisan queue:work --tries=3 --timeout=180

Restart a worker after queue:restart unless a supervisor manages it. Review failures with queue:failed; retry the relevant scan job after fixing its cause.

### Windows upload troubleshooting

If transfer reaches 100% but fails, inspect the failed Network request's Response. PHP needs writable absolute folders for **both** upload_tmp_dir and sys_temp_dir. Example php.ini settings:

    upload_tmp_dir="C:/tools/php/upload-tmp"
    sys_temp_dir="C:/tools/php/upload-tmp"
    upload_max_filesize=20M
    post_max_size=25M
    output_buffering=4096

Create the folder, allow the PHP process to write to it, and restart the server. These are PHP settings, not .env values. Livewire stream_get_meta_data() errors can indicate PHP tmpfile() cannot create a file.

On the original workstation, ClamAV and signature databases live outside the repository under lakbay-dev-tools; a local queue worker and daily signature-update task were configured. Other machines need their own setup, and the local worker must be restarted after reboot.

## First manual acceptance flow

Use dummy documents and separate browser profiles for each role.

1. Administrator creates an HEI and invites submitter, reviewer, and signatory accounts.
2. HEI prepares an academic period, calendar, and Category 1 activity with personnel/authorization.
3. HEI uploads the signed evidence required by the policy and waits for Clean status.
4. HEI submits the calendar; its reference and preserved snapshot appear.
5. Reviewer requests specific corrections.
6. HEI revises the plan/evidence and resubmits against the correction request.
7. Reviewer marks the new revision complete.
8. Signatory uploads/records the clean signed acknowledgment.
9. HEI downloads it; earlier revisions and review history remain intact.
10. Verify a second HEI cannot see or download the first HEI's records.

After conclusion, record actual completion and file applicable reports. HEI authorization, acknowledgment, execution status, and exceptional clearance remain separate.

## Validation

For the isolated fictional-account demo, copy-paste setup commands, exact HEI/CHED click paths, database checks, 64 manual cases, and discovered defects, see [HEI & CHED Application — Complete QA Testing Walkthrough](docs/UAT-guide.md). `php artisan lakbay:demo --reset` resets only its dedicated QA database; it never resets the normal installation. The guide explains real malware scanning and first-login MFA. Application defects are reported there without changing business logic.

    php artisan test --compact
    php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
    php vendor/bin/pint --test
    composer audit
    npm audit
    npm run build

Windows helpers: .\scripts\lakbay.ps1 test and .\scripts\lakbay.ps1 check.

Local validation on **6 October 2026**: **111 tests, 412 assertions**, PHPStan and Pint passed. Tests use isolated fixtures and storage; they do not establish production readiness. GitHub Actions is configured for SQLite/PostgreSQL 17, dependency audits, formatting, and frontend build checks.

CMO alignment validation on **7 October 2026**: the full suite passed **171 tests, 547 assertions**. After adding the final submission-stage safeguard, all **61 CMO regression tests, 136 assertions** passed. PHPStan, Pint, and the frontend production build passed. The local SQLite database was backed up before the additive migration and candidate-policy seed. Official policy activation, PostgreSQL validation, and authenticated browser UAT remain pending.

QA walkthrough validation later on **7 October 2026**: **184 tests, 617 assertions**, PHPStan/Pint, and frontend build passed. The isolated demo's 23 files passed real ClamAV scans; Chrome HEI/reviewer MFA, scoped pages and private retrieval received smoke checks. Follow-up fixes remove unsupported direct submission creation and display timestamps consistently in Asia/Manila; category labels and boolean values are clearer. Stored timestamps and historical snapshots remain unchanged. Full browser UAT and PostgreSQL/deployment/recovery validation remain pending. See the walkthrough for regression steps and limitations.

Annex B/C/D validation on **7 October 2026**: **231 tests, 907 assertions**, PHPStan and Pint passed. The Annex D normal PDF was visually checked as one A4 page; a long-remarks PDF retained all 500 test remark sentences and final signatories across additional pages. Normal and isolated QA SQLite databases were backed up and additively migrated without reseeding. These checks do not establish authenticated browser, PostgreSQL or deployment readiness.

Annex E validation on **7 October 2026**: **246 tests, 1,008 assertions**, PHPStan and Pint passed. The normal RACP PDF was visually checked as one A4 page; a four-page version retained all 150 test contingency-measure sentences and final signatories. Normal and isolated QA databases were backed up and additively migrated without reseeding. Browser UAT and PostgreSQL/deployment checks remain pending.

Annex F validation on **7 October 2026**: **263 tests, 1,130 assertions**, PHPStan and Pint passed. Both certification pages were checked for full checklist, on-request instruction, attestation and signatory content; explained N/A adds a separate notes page. Normal and isolated QA databases were backed up and additively migrated without reseeding. Browser UAT and PostgreSQL/deployment verification remain pending.

## Architecture and deployment

Actions: app/Actions. Access/compliance/reporting: app/Services. Models: app/Models. Authorization: app/Policies. Panels: app/Filament/Hei and Ched. Views: resources/views. Jobs: app/Jobs. Schema/policy defaults: database/migrations and seeders.

See [architecture](docs/architecture.md), [deployment and backups](docs/deployment.md), and [outstanding CHED decisions](docs/CHED-clarifications.md).

Production needs HTTPS, APP_DEBUG=false, PostgreSQL, approved mail/storage/hosting, supervised workers, Laravel scheduler, current signatures, backups, and tested restoration. Docker configuration is provided but has not been verified on this workstation.

## QA verification

The 8 October 2026 QA campaign adds repeatable fictional fixtures and Chromium browser coverage. See the [executed results, defects and remaining checks](docs/UAT-guide.md#executed-qa-campaign-8-october-2026) before judging rollout readiness. Browser tests use **only the isolated QA server at port 8001**:

```powershell
php artisan db:seed --env=local --class=QaCampaignSeeder --no-interaction
php artisan serve --env=local --host=127.0.0.1 --port=8001 --no-interaction
# In another terminal, with Node/PHP on PATH:
npx playwright install chromium
npm run test:browser
```

Back up the QA database/private storage first. The campaign seeder requires the existing demo institution and genuinely scanned fixtures; it refuses the normal database. Random credentials and real MFA enrollment state remain in ignored QA storage. Browser artifacts stay under `storage/app/qa-campaign-browser/`. Reruns preserve completed submission/report history, so use another isolated copy to repeat the initial lifecycle.

## Known limits

### Personnel readiness (FIX-03)

For Category 1/2 treatment, including escalated Category 3, complete each PIC/APIC's employment, accompaniment and relevant qualifications. APICs use **APIC basic first-aid knowledge**; a separate APIC training-proof upload is not required. PIC training evidence and PIC-only ratios remain unchanged. Incomplete drafts can be saved, but the shared readiness check explains missing APIC particulars and blocks a new CMO filing. Ordinary Category 3 remains supervisor-only capable; optional PIC/APIC qualification scope is awaiting CHED confirmation.

Existing installations require the additive `2026_10_12_005824_add_basic_first_aid_knowledge_to_activity_personnel` migration before using the new field. It adds a nullable column with no historical backfill and refuses destructive rollback. Back up the database/private files, inspect `php artisan migrate --pretend --no-interaction`, then apply through the approved deployment process. This task applies migrations only to disposable QA/test databases, not the normal development database.

### Classification revision workflow (Gap #2 Slice 3)

In **HEI → Activities → Revise**, select an eligible classification rule set and enter the planning, supervision, baseline and change context. Saving creates a **recommendation**, not an authorized classification. Open the activity's **Classification history and requirements**, inspect the differences and evidence requirements, then use **Confirm saved classification** with an authorized submitter account and a reason. Confirmation creates another immutable activity version. A later edit requires another confirmation before filing under the selected rules.

CHED's **Submitted classification and requirements** action displays the filed snapshot, not subsequent unsent edits. New filing notices, selected Annex F preparations and regional exports include that same assessment context. Historical preparations and receipts are not regenerated. Annex F's classification continuation is explicitly application metadata, not an additional CMO clause.

Only submission-eligible policies appear in the selector. Unactivated candidates are available solely in the existing explicitly enabled local/testing evaluation mode; this does not activate CMO 24. The previously delivered classification migrations and rule-set seed must be present. Opening an old record alone does not enroll or rewrite it. See [Slice 3 verification and limits](docs/gap2-slice3-report.md).

Security/staging follow-up on **8 October 2026**: a scoped npm override pins concurrently's nested `shell-quote` to patched 1.11.0. Clean `npm ci`, npm audit (**zero vulnerabilities**), command-runner/quoting checks and the asset build pass. CI now runs npm audit and excludes the six deliberately SQLite-only demo tests from its PostgreSQL job; the SQLite job retains them. See [isolated PostgreSQL staging setup](docs/deployment.md#isolated-postgresql-staging-preparation-8-october-2026). Configuration syntax was checked locally; Docker/PostgreSQL execution remains blocked by missing tooling.

- Internal HEI consultation, approvals, signing, and notarization happen externally; the app prepares Annex D/E assessments and records separately signed evidence.
- Central Office/national consolidation, public complaint intake, student/guardian portals, spreadsheet import, mobile clients, and digital signing are deferred.
- July-draft records retain their provisional deadline parameters. New CMO 24 incident calculations use 48 hours; the change-report deadline anchor remains unresolved.
- Publication, Category 3 timing, SAA coverage, holidays, receipt rules, evidence applicability, retention and approved template variants require confirmation.
- Student totals are estimated participations, not unique students.
- No automatic official-record purge is enabled.
- Full browser/accessibility UAT, load tests, PostgreSQL concurrency, container execution, and production recovery verification remain rollout gates. Local Chromium and SQLite/file recovery results are documented separately.

## Repository hygiene

Commit source, migrations, tests, documentation, and lockfiles. Exclude .env, keys, invitation links, credentials, SQLite databases, uploaded evidence, exports, logs, backups, portable tools, and generated assets. Only sanitized environment examples belong in Git.
