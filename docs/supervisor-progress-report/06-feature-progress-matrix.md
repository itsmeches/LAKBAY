# Feature and milestone assessment

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

Status definitions: **Verified locally** = implemented source plus identified recent local automated coverage; **Partial** = working parts with a documented gap; **Source only** = identifiable implementation without separate proof for every path; **Pending** = not delivered; **Blocked** = missing decision/environment. None means production accepted or all possible defects absent.

| Module / feature | Current status | Evidence | Remaining work | Priority |
|---|---|---|---|---|
| Public site/guidance/FAQ | Verified locally for browsing | `routes/web.php`; browser public case | Confirm registered approved blank templates and Annex N publication | Medium |
| Invitations/MFA/panel isolation | Verified locally, production operations unverified | `InviteUser`; `InvitationController`; `User`; `PanelQaTest`; browser role cases | Institutional mail and shared-office throttle proposal | High |
| Ownership/jurisdiction/confidentiality | Verified local negative cases | `Access`; `InstitutionRecordPolicy`; `CaseProcedureTest`; browser other-HEI/admin cases | Production concurrency/access review; cross-region cases decision | High |
| Annual CLOCA / pre-activity filing | Verified local workflow paths | `SubmitActivity`; `ClocaTest`; `Cmo24AlignmentTest`; browser corrections | Pending normal migrations, legal receipt/calendar decisions | High |
| Category rules and historical SAA correction (FIX-01) | Verified locally | `CorrectSaaPolicy`; `RequirementResolver`; `LakbayWorkflowTest`; gap1 reports | Per-term SAA confirmation | High |
| Classification/materiality (FIX-02 / gap2 slices) | Verified locally, defaults provisional | `ClassificationEvaluator`; `ConfirmActivityClassification`; `Classification*Test` | Written CHED definitions/materiality decisions | High |
| PIC/APIC readiness (FIX-03) | Verified locally | `ActivityCompliance`; `ActivityReadiness`; `Cmo24AlignmentTest`; browser APIC check | APIC counting/optional ordinary Cat 3 interpretation | High |
| Review attention/change notices (FIX-04) | Verified locally | `ReviewSubmission`; `NotifyChedChangeReport`; `LakbayWorkflowTest`; browser inbox | Production worker/mail/concurrency | High |
| Restricted procedural records (FIX-05) | Verified registry; legal/transfer scope partial | `RecordCaseEvent`; `CaseProcedureTest`; browser restricted-case | CHED transfer/access decision; no legal authenticity automation | High |
| Annex B CLOCA / C SAA | Verified preparation/print/preservation | `ClocaTest`, `SaaTest`; browser PDF/frozen outputs | Coverage and external signatures/notarization | Medium |
| Annex D diagnostic / E RACP | Verified input/print/snapshots | `DiagnosticTest`, `RacpTest`; browser PDFs | Human assessment quality and CHED sample adaptations | Medium |
| Annex F certification | Verified preparation/treatment/freeze | `CertificationTest`; classification tests; browser edit/freeze | N/A/coverage acceptance and exact-template interpretations | Medium |
| Annex G ROC / H incident / I change | Verified local prepare/file/freeze | corresponding `*ReportTemplateTest`; `SubmitReport`; browser filing | RCD legal anchor; operational real-time intake | High |
| Annex J itinerary | Verified input/print/freeze | `ItineraryTest`; browser PDF | Time ranges/DOT applicability interpretation | Medium |
| Annex K acknowledgment | Verified workflow/output; exact-format acceptance partial | `AcknowledgmentTest`; browser signed letter branch | FIX-10 exact template/reference-line acceptance | Medium |
| Annex L consent | Verified response-only output | `ConsentFormTest`; browser PDF | Guardian applicability/privacy notice; not a student portal | Medium |
| Annex M monitoring | Verified 14-item input/print | `MonitoringFormTest`; browser creation/print | HEI feedback adequacy FIX-09, rating methodology | Medium |
| Annex A / N | Reference material, not entry forms | CMO mapping; `FormTemplate`; clarification register | FIX-11 both N pages/reference publication and order clarification | Medium |
| Evidence requests | Implemented with feature coverage | `RequestEvidence`; `LakbayWorkflowTest` | Demonstrate requested retention evidence with CHED pilot | Medium |
| Completion/incidents/changes timing | Verified policy-version paths | `PolicyRules`; `SubmitReport`; alignment/template tests | Cat 3 unresolved rows; formal RCD timing | High |
| Clearance/reconsideration | Partial | `RecordClearanceRouting`; `RecordClearanceDecision`; `RecordReconsideration`; workflow/alignment tests | **FIX-06** complete routing/staff-work chain | High |
| Monitoring/assistance/corrective follow-up | Verified parts; feedback scope partial | `RecordOperationalRecord`; `ManageCorrectiveAction`; monitoring/workflow tests | Confirm all required HEI feedback outcomes | Medium |
| Regional reporting/export | Partial, local tested basics | `RegionalReportBuilder`; `ManageRegionalReport`; `GenerateExport` | FIX-07 narrative findings, proper report-evidence association | High |
| Private files/quarantine | Verified local genuine scanner and failure | `UploadDocument`; `ScanDocument`; browser upload/retry | FIX-08 fresh retrieval integrity, production signatures/worker | High |
| Policy activation safeguard | Verified logic; activation blocked | `ConfirmPolicy`; `PolicyRules`; `Cmo24AlignmentTest` | Verified publication/reference and CHED decisions | Blocker |
| Audit/backup | Local record-preservation/SQLite restore verified | FIX-05 preservation/restore artifacts; `Audit` | PostgreSQL and production recovery; retention/holds | High |
| Production deployment/load/accessibility | Prepared / unverified | Compose/CI/deployment docs; desktop/narrow browser cases | ICT hosting, full accessibility/load/staging acceptance | Blocker |

## History-supported improvements

`741f29d`: restricted procedural ledger and visible detail action (FIX-05). `a69e192`: filed-schedule attention plus authorized deduplicated change notices (FIX-04). `b607b70`: scoped PIC/APIC checks (FIX-03). `3cd71b5`: classification interfaces/frozen outputs; `9f41b8c`/`385cc43`: earlier classification logic/configuration/history slices. `290344d`: supervisor-only ordinary Category 3 plans. FIX-01 evidence and candidate-selection regression are documented in `docs/gap1-verification-report.md`; do not invent a single commit covering every earlier iteration.

All software slices do not close the underlying CHED decisions. The improvement is traceable input, review, history and evidence handling, not a guarantee of legal compliance. No completion percentage is used because the scope includes unresolved requirements and production gates.
