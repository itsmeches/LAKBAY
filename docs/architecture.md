# Application architecture

LAKBAY is one modular Laravel application, with two Filament panels and a public Blade site. PostgreSQL is the production database. SQLite is the local development convenience.

## Code map

- app/Models: relational records, casts, relationships and immutability guards.
- app/Actions: explicit business operations grouped by Activities, Submissions, Documents, Reports, Operations, Identity and Clearances. These enforce permissions, ownership, transitions and evidence requirements independently of the interface.
- app/Services/Access.php and app/Policies: regional/institutional ownership and confidential access. There is no administrative superuser bypass.
- app/Services/Compliance: bounded predicates, category treatment, deadlines and recorded readiness.
- app/Filament/Hei and Ched: panel resources/pages. Support contains shared fields, resource definitions and workflow actions. Large calendars use paginated activities.
- app/Jobs: scanning, notification delivery and private exports. Jobs use record IDs and recheck access where applicable.
- app/Http: authorized private retrieval, invitations and MFA enrollment checks.
- database/migrations: additive schema, foreign keys and unique constraints; seeders preserve the draft and add a CMO 24 candidate with all 22 Section 14 rows and versioned annex metadata.
- config/monitoring.php: Annex M's 14 criteria, copied into each monitoring record with its criterion version.
- tests/Feature/LakbayWorkflowTest.php: domain, private access and Livewire regression checks.
- docs/CHED-clarifications.md: unresolved policy decisions.
- docs/deployment.md, Dockerfile and compose.yaml: deployment and recovery preparation.

## Integrity and authorization

Gap #1 correction: ordinary Category 3 excludes SAA; escalated Category 3 resolves Category 2 rules. The additive `lakbay:correct-saa` command defaults to dry-run and copies existing rules/templates into a candidate successor without updating historical rows. New draft forms follow `parameters.supersedes_policy_version_id`; corrections retain their original baseline. See decisions.md for recomputation, evidence reuse and history guarantees.

Activity details create immutable versions. Submission freezes exact versions, evidence, document hashes and policy/holiday references before review. Corrections create new revisions. Acknowledgment references one revision and does not authorize implementation.

Database transactions and row locks protect state-changing actions; unique constraints protect acknowledgment/reconsideration limits. Model guards prevent application-level historical edits. Database administrators must use separate operational credentials; those guards cannot prevent direct SQL by privileged operators.

Private uploads are quarantined until ClamAV returns clean and their hash matches. Documents are streamed through authorized routes. No public storage symlink is required. Restricted-case access is separate from administration and ordinary regional review.

Working dates use versioned holiday records and Asia/Manila display. Receipt is application receipt time in the current implementation, pending CHED confirmation. Late incidents remain accepted. Report deadline parameters and references are recorded with intake.

CMO 24 has separate annual-calendar, pre-activity, post-activity and requested-evidence stages. Category 1 pre-activity evidence references a recorded annual submission; it does not require acknowledgment as HEI authorization. Requirement snapshots include signature mode, supporting types, applicability and resolved/unresolved deadlines. Retained assessments are supplied immediately on request. One document can support multiple rows.

PolicyRules selects the effective CMO baseline for event-time reports, otherwise the linked submission baseline or preserved draft. Publication and its reference are required for official CMO confirmation; effectivity is derived at +15 days. AY 2026–2027 CLOCA/SAA use publication +30 calendar days. The transition's date-only cutoff is displayed at the end of that Manila date; electronic receipt interpretation remains a CHED decision.

PIC ratios and third-party contracts are checked by ActivityCompliance. APICs are not counted toward PIC ratios. Preparation references cover inclusivity, parallel learning, grievance mechanisms, student participation and emergencies without creating master student or medical records.

Incidents distinguish initial notices from signed reports and preserve supplements. Change notices preserve before/after personnel and itinerary details; a null formal deadline means unresolved, not timely. Completion cannot overwrite a recorded actual conclusion. Clearance intake stores application timing and an append-only routing history; external decision receipts anchor reconsideration. The 90-day filing unit remains explicitly provisional calendar arithmetic.

Annual regional reports require an explicit period start and cutoff; scheduled activities, actual conclusions, incident occurrences and monitoring dates are filtered to that period, using evidence available at the cutoff. Reports freeze activity and monitoring aggregates. Only initially empty dispatch/receipt fields may be populated; snapshots and recorded correspondence cannot be overwritten. Recording dispatch does not send email to OSDS. August reminders are operational reminders, not an invented exact statutory cutoff. Incident supplements retain the original occurrence and deadline and do not increment root-incident totals.

Signed originals are uploaded externally; the system records them and their hashes without claiming cryptographic verification. No legal approval is inferred from a complaint, acknowledgment or technical assistance entry.

## Annex B generation

ClocaForms groups calendar print metadata and activity-row inputs. SaveClocaCalendar authorizes and validates calendar header changes; SaveActivity stores CLOCA facts on immutable activity versions. ClocaBuilder produces a renderer-versioned payload using institution/coverage details, signatories, exact activity versions and optional clean letterhead references. SubmitActivity freezes that payload for calendar submissions while holding the calendar row lock. Existing snapshots are not backfilled.

Protected calendar/submission CLOCA routes require authenticated MFA-enabled users and institution/regional records-view access. The calendar route previews current plans; the submission route renders only its frozen payload, never live header or activity data. HTML previews list missing print fields. PDF download requires printable completeness, uses escaped Blade content, disables remote resource fetching/PHP execution, and records generation in the audit trail. Generated PDFs remain unsigned responses and do not create evidence records or modify submission/authorization state.

Letterhead is optional text or a private institutional PNG/JPG document up to 2 MB. Each retrieval rechecks ownership, confidentiality, malware-scan state and the frozen SHA-256 against actual bytes. A changed/quarantined image blocks rendering rather than bypassing quarantine. A4 portrait PDFs repeat table headings and include page counts; synchronous generation is bounded to 500 rows and 1200 characters per printable narrative cell. Add a new renderer version for future layout/data-contract changes instead of reinterpreting version 1 snapshots.

## Annex C generation

AccountabilityAffidavit records belong to an institution and academic period, with separate semester drafts rather than a single affidavit per annual calendar. SaveSaaDraft validates ownership, print metadata and clean private letterhead references. It increments the working draft revision and records changes in the audit trail; a draft is never marked signed or notarized. SaaForms exposes HEI-head affiant, coverage, optional execution and optional notarial fields. CHED resources are read-only. Government ID numbers/copies are outside the preparation form.

SaaBuilder maps the CMO Annex C template (PDF page 22) into a renderer-versioned payload with template identity, draft revision, institution/coverage, particulars and hashed letterhead reference. Authenticated MFA-protected SAA routes apply the same ownership/jurisdiction and private/no-store controls as CLOCA. Missing core print fields block PDF generation, while unsigned draft previews remain available. Signing/notarization occur externally and their signed original remains independently uploaded evidence.

SubmitActivity optionally freezes a selected, same-institution/same-academic-period draft into `snapshot.annex_c`. This applies to calendar and activity packages without imposing a new draft requirement on historical workflows. A correction without a replacement selection retains the earlier payload verbatim. Submitted SAA rendering uses only frozen data, never current institution/draft values; legacy submissions are not reconstructed. Layout changes require a new renderer version to preserve the meaning of historical data.

## Annex D generation

DiagnosticForms adds an optional, grouped Annex D assessment to activity creation/revision. DiagnosticPreparation validates the 20 Yes/No answers, bounded remarks, explicit HEI recommendation, signatories and private letterhead; blank forms remain absent. SaveActivity stores these facts in the immutable activity version with the criterion groups/version, recommendation labels and template identity assigned server-side. Omitting diagnostic data in a domain revision preserves the previous assessment. Partially completed drafts are supported; this introduces no new policy submission blocker or automatic approval rule.

DiagnosticBuilder derives basic information from the exact activity version and institution. SubmitActivity freezes each prepared assessment into `snapshot.annex_d`, keyed by activity-version ID, including institution/header details. Current activity previews use the working version; submission previews/downloads use only frozen data and validate requested version membership. Older submissions are never backfilled from current assessments.

Authenticated MFA-protected routes enforce records-view ownership/jurisdiction and private/no-store output. Letterhead retrieval reuses the clean-file/hash checks. Complete printing requires every Yes/No answer, an HEI recommendation and signatory particulars. PDF generation preserves Annex D's sample wording, repeats criterion headers for long reports, records an audit event and does not create signed evidence, fulfill requested evidence, or change authorization. Signed originals remain separately uploaded documents under existing evidence rules.

## Annex E generation

RacpForms adds an optional, grouped Annex E form to activity creation/revision: six fixed risk areas, explicit Low/Medium/High ratings and measures, bounded additional risks, five emergency contacts, bounded other contacts, optional issuance date and three signatories. RacpPreparation validates the inputs and private letterhead, preserves blank forms as absent, and assigns assessment version, risk/contact labels, certification wording and template metadata server-side. SaveActivity stores these in the immutable activity version; omitted RACP data in a domain revision preserves the existing plan. This is an activity-level assessment, not a student medical-history registry.

RacpBuilder derives basic information from the exact activity version. SubmitActivity freezes prepared RACPs in `snapshot.annex_e`, keyed by activity-version ID, including the institution/header and source wording. Protected activity previews use the working version; protected submission previews/PDFs use frozen data and validate the requested version's inclusion. Historical submissions are not reconstructed. Scoped CHED users can read/print; HEI preparation uses the existing activities-manage permission and institutional membership checks.

Printing checks each named risk rating/measure, emergency contact, signatory and added row. Unknown issuance dates print as blank. A4 PDFs include certification/signature blocks and repeat table headers when long measures continue to more pages. Private/no-store retrieval, MFA, escaped Blade output, disabled remote/PHP execution and letterhead scan/hash checks match other annex outputs. Generation is audited but does not establish signing, completion before conduct, authorization or requested-evidence fulfillment. Signed originals remain separately uploaded evidence.

## Annex F generation

CertificationDraft records belong to an institution and academic period and are reusable in calendar/activity packages. The period organizes selection; Annex F itself does not state a period or activity list, so this is not a legal coverage determination. CertificationForms exposes all 14 requirement rows and 16 supporting-document checks from PDF pages 25-26, one authorized official, optional issuance particulars and private letterhead. SaveCertificationDraft authorizes membership, validates item states, requires reasons for N/A, rejects N/A with checked support, and assigns an immutable definition/template reference to the draft. Edits increment the working revision and keep its original definition; the CHED panel is read-only.

CertificationBuilder freezes institution identity/address, official particulars, item decisions, supporting checks and definition metadata in a renderer-versioned payload. Pending/unassessed rows and unconfirmed applicable supporting documents prevent final PDF generation; drafts remain editable/previewable. The two-page source layout and page-two immediate-on-request instruction are preserved. Explained N/A items remain unchecked in the source table with a separate printed notes page. Neither these product print checks nor human checkbox entries replace RequirementResolver's policy/evidence validation.

SubmitActivity optionally freezes a selected same-institution/same-period certification in `snapshot.annex_f`. Corrections without a replacement retain it verbatim. The selected draft must be printable, but no new preparation-draft requirement is imposed on legacy/manual signed-evidence workflows. Protected current/submission previews apply MFA, ownership/jurisdiction, private/no-store output, escaped content and private letterhead scan/hash controls. Generated unsigned PDFs are audited and do not create signed evidence or prove authenticity/authority. Historical submissions without Annex F data are not backfilled.

## Annex G generation and intake

CompletionReportDraft belongs to an institution, activity and exact immutable activity version. SaveCompletionReportDraft is HEI-only with verified membership and reports-submit permission; it stores partial report details and increments revisions without changing activity execution or report receipt. The original proposed facts, institution header and Annex G template/certification wording are captured in a renderer-versioned context at creation. Venue and participant counts are human-confirmed actual values; actual conclusion remains separate from printed proposed dates. Create another draft to change its source version or refresh the captured HEI profile.

CompletionReportForms groups source selection, implementation/outcome, incidents and certification. Missing actual conclusion, counts, narrative/signatory fields and conditional explanations prevent final printing/filing, but not draft preparation. Unknown issuance dates remain blank. Incident links refer to existing records from the same activity; narratives are minimized and CHED incident-view permission is required for incident-bearing preparations and outputs. Private letterhead reuses scan/hash checks.

SubmitReport accepts an optional completion-report draft ID. It derives intake fields from the draft, requires the existing clean signed completion PDF, enforces incident reconciliation and the recorded actual-conclusion anchor, and freezes annex_g on the immutable report. Activity and draft locks plus a unique draft/report link prevent concurrent edits and duplicate filing of the same draft. Filed drafts cannot be edited through the shared Action. Late intake remains accepted; policy-versioned deadlines are unchanged. Linked submission identity, when present, uses the draft's exact activity version. Legacy manual intake is preserved without retrospective reconstruction.

MFA-protected current/preserved HTML and A4 PDF routes enforce ownership, jurisdiction and confidential access; outputs use escaped Blade, private/no-store headers, disabled remote/PHP execution and audit events. Generated PDFs do not create evidence or claim signatures; the uploaded signed original remains separate. Source Annex G is PDF page 27.

## Annex H generation and intake

IncidentReportDraft uses the Annex G preparation pattern with universally confidential access. SaveIncidentReportDraft validates verified HEI membership, exact activity revision, optional same-activity supplement reference, occurrence consistency, bounded persons/narratives and private supporting/letterhead references. Basic plan information and the PDF page-28 template/certification wording are captured at creation. Persons belong to individual reports; no master student or medical-history records are introduced.

IncidentReportBuilder creates a renderer-versioned payload with preserved activity facts, incident details, persons and supporting-file names/IDs/hashes. Protected HTML/PDF routes require MFA, records-view access and either institutional ownership or jurisdiction plus incidents-view permission. Print completeness is distinct from intake completeness. Generated copies are unsigned and do not notify CHED, create evidence or embed supporting documents. Letterhead retrieval retains the clean-scan/actual-hash controls.

SubmitReport optionally derives incident intake from a preparation. A signed original requires complete print fields and a clean sensitive incident PDF; initial notices retain the existing core-information checks without requiring signatures or supporting files. The Action freezes annex_h and copies persons into immutable incident records under activity/draft locks and a unique draft/report link. Filed preparations cannot be edited/reused. New supplements preserve root occurrence, deadline parameters, policy/calendar references and provisional status. Late intake is accepted. Legacy manual intake and original signed files remain supported without retrospective reconstruction.

## Annex I generation and intake

ChangeReportDraft references an institution/activity, ordered immutable original/revised activity versions, an optional matching electronic Submission and optional original ChangeReport for follow-up. SaveChangeReportDraft validates HEI ownership/membership, same-activity ordering, receipt membership, source immutability, bounded report details and clean institutional supporting/letterhead references. Partial preparation does not change execution or create receipt.

ChangeReportBuilder captures the HEI header, original title/category, PDF page-29 template reference and comparison rows at creation. Electronic submission dates come from submitted_at in Manila time, never draft creation. External original date/reference is labelled HEI-entered; it cannot override a selected electronic receipt. Other particulars include recorded itinerary/activity differences and bounded human-entered additions. Printable output covers four sections and three signatories; long cells use continuation rows.

SubmitReport derives change intake from the selected preparation, checks authorized HEI submission, freezes annex_i under activity/draft locks and a unique draft/report link, and prevents filed preparation reuse. Signed filing requires complete print fields, a clean institutional Change report PDF and available clean supporting files. CMO immediate notices do not require signatures/support; unresolved formal deadlines remain null. Follow-ups must retain the same revision pair, change time and original receipt reference; they link to the root report and preserve notice time, policy and deadline parameters without overwriting prior data.

MFA-protected HTML/A4 PDF routes enforce ownership/jurisdiction, escaped content, private/no-store headers, disabled remote/PHP PDF execution, audited generation and existing clean-scan/hash letterhead checks. CHED preparations are read-only. Generated copies are unsigned; signed originals/support remain separate protected files. Historical reports without an Annex I payload return a conflict rather than being reconstructed. Both local SQLite databases received additive migrations after backups, without reseeding.

## Annex J generation

ItineraryForms extends activity creation/revision with existing itinerary_items plus a nullable activity_versions.annex_j JSON column for signatories, optional letterhead and server-assigned template metadata from PDF page 30. SaveActivity validates bounded rows (200 maximum), required row fields and permitted keys, converts Manila input to UTC and assigns sequence by submitted order. Only verified HEI members with activities-manage access may prepare itinerary inputs. Omitting rows/metadata preserves the previous version; an explicit empty row array/null metadata clears only the new revision. EditActivity prefills rows in Manila time and strips historical row identities before revision.

ItineraryPreparation validates signatory/private image references without inferring signature or authority. ItineraryBuilder maps exact-version basic information, six printed itinerary columns, human signatories and hashed letterhead into a renderer-versioned payload. Blank DOT information remains optional; it is not an accreditation determination. Partial signatory preparations are previewable; printing requires one valid row, profile address, PIC/office and all signatory particulars.

SubmitActivity freezes Annex J payloads in snapshot.annex_j, keyed by activity-version ID, for activity/calendar packages. Current previews use working plans; submission outputs use only frozen data and check requested version membership. No historical submissions are backfilled. Protected HTML/PDF routes apply MFA, institution/jurisdiction authorization, private/no-store headers, escaped output and existing scan/hash letterhead checks. A4 PDFs repeat itinerary headings and bound continuation cells; unsigned generation is audited and leaves signed evidence/submission/authorization separate.

## Annex K generation

SaveAcknowledgmentDraft locks the exact submission, requires regional acknowledgment-issue access and a complete unacknowledged calendar, checks that its attached signed CLOCA/SAA originals remain clean and hash-matched, and records the staff attestation of signing/notarization inspection. AcknowledgmentDraft is one mutable working preparation per submission; revisions and changes are audited. AcknowledgmentBuilder assigns Annex K/page 31 rendering metadata and freezes receipt, institution identity, program groups, planned date coverage and student participation counts from the submission. No individual-student data or guessed publication date is introduced.

Filament exposes preparation/printing under the existing submission actions. CHED letterhead is text, not an externally fetched image or HEI letterhead. MFA-protected HTML/PDF retrieval requires regional issue permission for working copies; issued frozen copies use institution/jurisdiction records-view access. Blade escapes data, PDFs disable remote/PHP execution and outputs are private/no-store. Generation is audited and does not advance status.

IssueAcknowledgment optionally uses the saved Annex K payload, requires matching letter particulars and valid scanned signed PDF bytes, and freezes it into immutable acknowledgment.summary.annex_k while locking the submission. Issued letters cannot be re-prepared. Generic/historical filing remains compatible and receives no reconstructed payload. Signed originals remain separate protected documents. Program counts are student participations; their interpretation and planned-date coverage remain CHED clarification items.

## Annex L generation

PrepareConsentForm authorizes a verified HEI member with activities-manage permission against an exact immutable ActivityVersion. It assigns PDF page-32 source/rendering metadata and derives title, Manila dates, office and venue server-side. The HEI profile is current at generation; there is no saved or reconstructed submission consent payload. Optional participant/guardian particulars are bounded response-only inputs, not master records or stored drafts. Private letterhead selection reuses institutional scan, ownership and actual-byte hash checks.

ConsentFormController serves an MFA-protected GET preparation page and CSRF-protected POST preview/PDF endpoint. Personal values are not accepted from GET query parameters, stored in URLs, flashed into validation sessions or included in audit metadata. Validation errors return a private escaped 422 preparation response. The preview posts its validated hidden values to download without session persistence. Blade preserves Annex L student consent, guardian acknowledgment, privacy notice and unsigned spaces; DomPDF disables remote/PHP execution, with private/no-store headers and bounded inputs.

The HEI Activity actions link to preparation; CHED accounts cannot use this personal-data preparation endpoint. Generated output does not record consent, create evidence, establish guardian applicability, notify CHED or authorize participation. Signed forms use the existing sensitive consent document type and quarantine workflow only when required. No migrations, historical backfill or default student/guardian accounts are introduced.

## Annex M generation

RecordOperationalRecord retains regional monitoring authorization, validates the complete distinct 1-14 Yes/No matrix and bounded evidence/remarks, and assigns canonical criterion wording from config.monitoring.annex_m. The earlier abbreviated criteria catalogue remains available for historical conventions. Final source wording and verification examples come from PDF pages 33-34; metadata accepts only named respondent/signatory/conforme fields, preventing caller replacement of region or definitions.

MonitoringBuilder captures HEI name/type, authorized region and officer, period/date/type, respondent position, exact checked items, source/examples, findings and optional signatory/conforme particulars. The additive nullable monitoring_records.annex_m column stores a renderer-versioned frozen payload; a model guard rejects overwrites. Existing records are not backfilled. New records preserve the snapshot at creation; external signing remains separate from recording monitoring results.

Definitions groups the Filament form in template order. MonitoringRecordResource supplies the Filament 5 create-authorization response for active verified regional users with monitoring-manage permission; the shared Action independently checks institution jurisdiction. MFA-protected CHED print routes use only preserved payloads, escape Blade fields, disable remote/PHP PDF execution and return private/no-store responses. Normal A4 output has two pages with the 10/4 criterion split; bounded continuation rows handle longer evidence/findings. Typed names/dates do not establish signatures, acceptance, sanctions or authorization. Generated PDFs are audited, and original signed monitoring documents remain private uploaded evidence.

## Scope and validation limits

The evidence registry is intentionally smaller than a full student or internal HEI approval platform. It does not collect master student, guardian or medical histories. Public pages do not reveal protected records.

Local checks do not prove multi-process PostgreSQL concurrency, production email delivery, real scanner behavior, browser accessibility or approved policy interpretation. The CI database matrix and pilot UAT cover those follow-on environments. Do not treat this implementation as deployment approval.
