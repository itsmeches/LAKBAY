# LAKBAY — CMO No. 24, s. 2026 conformance audit

Audit date: 8 October 2026. Scope: read-only source/code/test/navigation review of the existing regional application. No application code, policy records, accounts, uploads or operational data were intentionally changed. Only the three requested audit deliverables were created. Existing uncommitted development changes were preserved.

**Subsequent gap #1 update, 8 October 2026:** ordinary Category 3 SAA is now corrected in fresh/corrected candidate rules with additive successor versioning. SEC14-R10-C3 and SEC14-R10-T3 are IMPLEMENTED in the current CSV. The findings/counts below remain the original audit baseline; see decisions.md and FIX-01 for subsequent verification. All other gaps remain open, and no policy activation occurred.

Gap #1 verification: final PHPUnit 393 passed / 1,917 assertions; Pint/PHPStan/PHP lint passed; direct browser checklists passed. The complete disposable browser campaign had 19 passes / 15 failures (PDF temporary-file errors and upload/login failures); later retries did not establish a green suite. See decisions.md for exact output, history comparisons and unresolved verification limits.

## Conclusion

The Laravel/Filament regional evidence-registry architecture is appropriate. The application has substantial submission, reporting, private-file and annex-generation support, but **cannot yet be described as fully CMO-conformant or ready for official activation**.

The clearest confirmed policy defect is **ordinary Category 3 SAA**: Section 14 row 10 on PDF page 9 checks Categories 1 and 2; the Category 3 cell is blank. The candidate seeder includes all three and the tests repeat that incorrect expectation. This corrects the earlier analysis. A Category 3 activity receiving Category 2 requirements is a separate situation.

Publication and its reference are not verified. The issuance date is not the publication date. Keep CMO 24 a candidate; effectivity is publication +15 days, and the AY 2026–2027 CLOCA/SAA transition is publication +30 days.

## Source and method

Source: supplied `CMO NO. 24 S. 2026 (1).pdf`, 36 PDF pages; Sections 1–41 and Annexes A–N. SHA-256: `a7915e3eea2f89a6a5cb63ac83ce3947aa40abbd79bf42499aa4a17d22174c01`. Issuance shown: 28 September 2026.

The complete PDF text was reviewed. The Section 14 checkmarks, conceptual framework and both Annex N diagrams were also inspected visually. References use PDF page numbers. Attached prompts are audit instructions; policy obligations come from the PDF. Incorporated JMC/program/anti-hazing guidance was not supplied in full, so this audit cannot establish compliance with every incorporated instrument.

Repository evidence included policy seeding/resolution, deadline calculations, shared submission/report/case/clearance Actions, authorization and document-serving code, annex builders/views/configuration, feature tests, public routes and project architecture/clarifications. The requested alternative documents `docs/cmo.md`, `docs/open-questions.md` and `docs/decisions.md` were absent; existing architecture and CHED clarification documents were used.

The [traceability matrix](traceability-matrix.csv) contains **957 entries** with stable IDs, source, requirement, classification, verdict, severity, evidence and limitations. These are traceability atoms, not 957 independent features or executable tests. Annex fields often share a builder/test.

| Verdict | Entries |
|---|---:|
| COULD NOT VERIFY | 135 |
| IMPLEMENTED | 748 |
| PARTIAL | 45 |
| MISSING | 5 |
| NEEDS CHED DECISION | 22 |
| CONTRADICTS | 2 |

`IMPLEMENTED` means the inspected software represents/supports the requirement; it does not certify actual HEI compliance, signatures, legal authority, every browser interaction or production readiness. `COULD NOT VERIFY` includes human/legal obligations and informational material, not simply missing software. Severity describes impact if unresolved, not an assertion that every row is defective. Do not calculate shipping progress from these counts.

## Top findings and disposition

| Priority | Finding | Disposition |
|---|---|---|
| 1 | Correct ordinary Category 3 SAA | FIX-01 — Major — confirmed contradiction |
| 2 | Validate classification facts and changed activity treatment | FIX-02 — Major — partial implementation |
| 3 | Complete personnel qualification checks | FIX-03 — Major — partial implementation |
| 4 | Track deficiencies before conduct and notify change intake | FIX-04 — Major — partial implementation |
| 5 | Record complaint/investigation/decision notice history | FIX-05 — Major — partial procedural history |
| 6 | Complete exceptional-clearance routing | FIX-06 — Major — partial procedural history |
| 7 | Include findings/recommendations in annual regional reporting | FIX-07 — Major — partial output |
| 8 | Verify original bytes at private retrieval | FIX-08 — Major — supporting security hardening |
| 9 | Confirm publication and remaining operational interpretations | DEC-01 — Blocker — CHED/source decision |
| 10 | Finish independently captured environment and browser verification | QA-01 — Major — verification limitation |

See [fix backlog](fix-backlog.md) for concrete tasks, evidence, acceptance criteria and remaining lower-priority scope decisions. No fix was applied in this read-only audit.

## Section 14: independently checked source matrix

A listed category means a visible source checkmark. Conditional requirements remain conditional. A blank cell must not be treated as an affirmative requirement.

| Row | Obligation | Source categories | Applicability | Source |
|---|---|---|---|---|
| 1 | Mission/vision alignment | 1, 2, 3 | always | p.7 |
| 2 | Academic/representation/well-being purpose | 1, 2, 3 | always | p.8 |
| 3 | Academic-calendar inclusion | 1 | always | p.8 |
| 4 | CLOCA | 1, 2 | always | p.8 |
| 5 | PADP | 1, 2 | always | p.8 |
| 6 | Student/parent/guardian costs consultation and expense breakdown | 1, 2, 3 | student/family bears cost | p.9 |
| 7 | External invitation | 2, 3 | government/external invitation | p.9 |
| 8 | LGU coordination | 2, 3 | coordination; research/field-study exception disputed | p.9 |
| 9 | Accommodation or venue DOT information | 2, 3 | accommodation or relevant venue | p.9 |
| 10 | SAA | 1, 2 | always | p.9 |
| 11 | HEI authorized approval | 1, 2 | always | p.9 |
| 12 | PIC designation/training/ratio | 1, 2 | physical/accommodation/travel selects ratio | p.10 |
| 13 | RACP | 1, 2, 3 | always | p.10 |
| 14 | Diagnostic | 1, 2, 3 | always | p.10 |
| 15 | Permission/medical/insurance | 2, 3 | strenuous OR physical/medical condition OR hazardous destination | p.10 |
| 16 | Detailed itinerary | 1, 2, 3 | always | p.11 |
| 17 | Parent/guardian/spouse notice | 1, 2, 3 | always | p.11 |
| 18 | Pre-departure orientation/safety protocols | 2, 3 | always | p.11 |
| 19 | Faculty/OSAS/equivalent approval | 3 | always | p.11 |
| 20 | ROC | 1, 2, 3 | always after activity | p.11 |
| 21 | Incident report | 1, 2, 3 | if incident | p.11 |
| 22 | Change report | 1, 2, 3 | if change | p.12 |

Rows 1–12 generally distinguish Category 1 annual-calendar timing from Category 2 pre-activity timing. Do not make annual intake require every later pre-activity item. RACP, diagnostic, itinerary and notice have separate three-working-day timing as shown by their rows. Category 3 timing is not established for every marked cell.

Incident reporting is 48 elapsed hours from occurrence; ROC is 72 elapsed hours from actual conclusion. RCD retains immediate notification and the separate “72 hours upon conduct” wording: its formal anchor requires CHED clarification. Do not use issuance as publication, or silently interpret “upon conduct” as changed-at.

Evidence mode and signature are separate: an invitation/accreditation copy is not a sworn affidavit. Accomplished diagnostic/RACP and relevant sensitive evidence are retained/requested according to the source; a certification can support multiple obligations without duplicate uploads.

PIC ratio is 1:30 for required physical activity/accommodation/travel and 1:50 otherwise. APIC counting is not assumed. Qualification evidence and ratio calculation are separate obligations.

## Annex coverage

The matrix itemizes template headers, inputs, repeated columns, checkboxes, certification text and signatory spaces. A and N are references/process material, not additional data-entry forms.

| Annex | PDF page(s) | Builder/test family | Traceability entries |
|---|---|---|---:|
| B | 21 | Cloca / ClocaTest | 31 |
| C | 22 | Saa / SaaTest | 29 |
| D | 23 | Diagnostic / DiagnosticTest | 91 |
| E | 24 | Racp / RacpTest | 64 |
| F | 25–26 | Certification / CertificationTest | 46 |
| G | 27 | CompletionReport / CompletionReportTemplateTest | 25 |
| H | 28 | IncidentReport / IncidentReportTemplateTest | 29 |
| I | 29 | ChangeReport / ChangeReportTemplateTest | 32 |
| J | 30 | Itinerary / ItineraryTest | 29 |
| K | 31 | Acknowledgment / AcknowledgmentTest | 21 |
| L | 32 | ConsentForm / ConsentFormTest | 31 |
| M | 33–34 | Monitoring / MonitoringFormTest | 81 |

Annex D includes 20 diagnostic questions, Yes/No and remarks, grouped criteria and recommendations. Annex E includes six fixed risk areas plus “Other,” risk levels/contingencies and emergency contacts. Annex F spans two pages: 14 requirement checks and 16 supporting-document checks; its permission/medical/insurance subitems remain available on request. Annex M spans two pages with 14 criteria, verification examples, remarks, findings and sign-offs.

Generated preparations must stay distinguishable from signed originals. Typed names and dates are not signatures or notarization. Annex L preparation does not create consent or require a student/guardian master database.

Annex K's period and student-count semantics need confirmation: the implementation prints planned coverage and summed participations, which cannot establish unique student counts without a different data scope. The generated letter's calendar-package restriction also needs review for supplementary Category 2 CLOCA.

Field mappings and rendering support were inspected; this audit did not freshly save and print every form with new data. Earlier PDF artifacts are supporting historical QA evidence, not fresh visual approval of every long-content continuation.

## Annex N transcription and actual application support

The two source diagrams are not identical about acknowledgment order. Keep this conflict explicit.

### First diagram — HEI lane

1. Identify need/purpose; Determine Category 1/2/3; Conduct student consultation; Prepare documents.
2. Conduct Diagnostic Assessment; Prepare RACP; Ensure applicable standards/policies/documentary compliance.
3. Internal review by appropriate offices; Approval by authorized official; Designation of PIC/APIC.
4. Prepare CLOCA; Complete Certification; Execute SAA.
5. Submit required documents within prescribed timelines where applicable; Submit RCD if any.
6. Implement HEI-approved activity; Observe safety/security/health/welfare measures; Maintain proper documentation/records.
7. Submit ROC; Submit incident report if any.
8. Participate in monitoring/validation/fact-finding; Comply with recommendations; Provide improvement feedback.

### First diagram — CHEDRO lane

1. Receive and check submission completeness.
2. Issue acknowledgment if submission found in order.
3. Encode and maintain records.
4. Monitor/validate as necessary; Conduct fact-finding when warranted.
5. Provide guidance and technical assistance; Recommend appropriate action if needed.
6. Prepare regional report.

### First diagram — Central Office/OSDS lane

1. Consolidate CHEDRO reports/data.
2. Analyze data; Recommend CEB actions/policy enhancements.

### First diagram — visible connections

HEI boxes 1→2→3→4→5→6→7→8. CHEDRO boxes 1→2→3→4→5→6. HEI box 5 connects to CHEDRO box 1; CHEDRO monitoring box 4 connects to HEI box 8; HEI box 8 connects to CHEDRO regional-report box 6. Regional-report box 6 connects to OSDS consolidation box 1, then OSDS analysis/recommendation box 2. Central-office consolidation does not imply a required national panel in this regional release.

### Second diagram

HEI: Plan and identify activity → Classify activity → Prepare and comply with Section 14 → Secure HEI head/authorized official approval → Conduct activity → Prepare post-activity reports.

CHEDRO: Receive Section 14 submission → Issue acknowledgment letter → Check compliance → Advise deficiencies.

A dashed connection joins preparation/compliance to the CHEDRO submission group; it should not be interpreted as an additional mandatory approval gate. The first diagram says check completeness then acknowledge if in order; Annex K says reviewed for completeness. The second diagram puts acknowledgment before compliance. Separate immediate electronic intake receipt from signed completeness acknowledgment until CHED confirms the sequence.

### Mapping to real interfaces and responsibilities

| Source stage | Application support | Qualification |
|---|---|---|
| Identify/classify/consult | HEI activities, versioned facts and classification history | Original-calendar/change treatment and some thresholds need work |
| Diagnostic/RACP/documents | HEI structured preparations, evidence registry and private uploads | Human assessments/signing remain HEI responsibilities |
| Internal review/authorization/PIC | External authorization evidence and personnel records | The app is not a full internal HEI approval platform |
| CLOCA/Certification/SAA | Annex B/C/F preparation and submission package | Ordinary Category 3 SAA is incorrectly required |
| Submission/receipt | Frozen submission revision and intake information | Electronic receipt and completeness letter differ |
| Completeness/corrections | CHED inbox, shared review Action, HEI correction/resubmission | Pre-conduct advice tracking needs strengthening |
| Annex K | Signed-letter preparation and acknowledgment tied to revision | Conflicting source sequence and supplementary package scope unresolved |
| Conduct/safety/records | Readiness flags, separate authorization/restrictions/clearance | Software cannot certify real-world safety or prevent physical conduct |
| ROC/incident/RCD | Annex G/H/I, supplements and versioned reporting deadlines | RCD formal anchor remains unresolved; late intake must stay open |
| Monitor/assist/follow up | Annex M, assistance logs and corrective responses | General participation/feedback handoff is only partly explicit |
| Regional report/OSDS | Frozen regional report and external dispatch/receipt | Findings/recommendations and proper signed-report association incomplete |
| National consolidation/CEB | Externally issued correspondence/decisions recorded | National action is manual/outside regional software scope |

Recording an actual conclusion, incident or late report despite a restriction is **not** itself a defect: the registry must capture actual noncompliant events. A readiness warning is different from approval, and the application must not suppress reporting.

## Second pass: attempts to disprove IMPLEMENTED verdicts

1. Rechecked the Section 14 source images rather than trusting the seeded matrix or its tests. This disproved the Category 3 SAA assumption.
2. Compared personnel loops against the PIC/APIC definitions: collected APIC fields do not equal enforced qualification completeness.
3. Inspected output builders rather than accepting annual-report freeze tests as proof of contents: a frozen report can still omit findings/recommendations.
4. Inspected case/clearance Actions rather than inferring workflow completeness from event enums: recorded stages do not ensure a documented procedural chain.
5. Inspected private-serving routes separately from scanner tests: clean state does not prove current bytes still match their hash.
6. Distinguished actual human signing/approval from generated typed forms, and external decision records from a new software decision-making mandate.
7. Separated ordinary Category 3 from Category 3 receiving Category 2 treatment; separate evidence timing from annual package timing.

## Reverse check: avoid invented obligations

Two Filament panels, MFA, invitations, private scans, revisions and audit logs are reasonable security/design safeguards; the CMO does not prescribe those exact technologies. No student, guardian, transport-provider or OSDS account is inferred solely from a named participant. No electronic-signature system, public complaint portal or national consolidation panel is automatically required.

Do not label acknowledgment “CHED approval.” Do not turn every invitation into Category 2 without checking original planning and circumstances. Do not create a sanction from a complaint. Do not publish sanctions through a routine case screen without separate authority. Do not make every uploaded copy “signed,” require all retained sensitive evidence routinely, or present null disputed deadlines as compliance.

The framework's second outcome heading was visually confirmed as **Safe and Equitable Experiences**. The conceptual diagram describes mission, ethical governance, integrated learning and supporting pillars; it is not an extra transactional approval workflow.

## Verification and limitations

**Fresh focused policy results:** The focused suite `tests/Feature/Cmo24AlignmentTest.php` passed **61/61 tests, 136 assertions**, exit code 0, after the filesystem-permitted rerun. The first sandbox attempt passed 60 and errored on one temporary-upload fixture (`fwrite` received false). No application fix was needed for that environment failure. A passing suite still includes the incorrect Category 3 SAA expectation, so it does not establish source conformance.

A full-suite sandbox attempt encountered temporary-file/DomPDF errors; its failed result must not be interpreted as confirmed application defects. A later full-suite rerun was started outside the sandbox, but its final output was not retained in the captured audit evidence; no fresh full-suite pass total is claimed here.

The existing `storage/app/qa-campaign-browser/phpunit-final.json` reports **381 passed tests / 1,824 assertions** from the earlier campaign. That is prior evidence, not a fresh result of this audit. Earlier browser/scanner/PDF/recovery artifacts likewise remain historical.

Fresh read-only Chromium navigation captured:
- HEI preparer and submitter: 17 destinations each, HTTP 200, no reported page errors.
- Reviewer, monitor, director and records: 19 destinations each, HTTP 200, no reported page errors.
- Analyst: 20 destinations, HTTP 200, no reported page errors.

These are navigation checks using existing fictional MFA-authenticated sessions, not seven full lifecycle runs. Final output for case/admin/other-HEI navigation was not retained; those outcomes are not claimed. No fresh forms were submitted, real recipients emailed, scan status bypassed, or candidate policy activated. Creation-button locator counts were not used as a defect because a Filament creation action can be rendered as a link.

PostgreSQL/container checks are **blocked**: Docker and psql were unavailable. SQLite tests do not establish PostgreSQL locking, deployment or restoration readiness. No infrastructure installation was included. Fresh scanner processing, backup restoration, fault injection, complete keyboard/mobile accessibility and long-content PDF inspection were not repeated during the read-only audit.

## Next authorized work

Approve FIX-01 first: independently correct and test the candidate category matrix while preserving every historical snapshot. Follow with classification/personnel enforcement, procedural/reporting fixes and a disposable cross-role QA run. Obtain publication and policy decisions separately before official activation.

The [backlog](fix-backlog.md) contains acceptance criteria. The [CSV](traceability-matrix.csv) is the detailed evidence register; its limitations override any broad “implemented” interpretation.
