# Editable system diagrams

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

Diagrams represent the current source baseline; they do not certify deployed infrastructure. Solid paths represent application functions. Dashed paths represent queued/configured or externally performed work. The diagram source files in `diagrams/` are editable Mermaid. The use-case model additionally has formal PlantUML source; its Mermaid companion is a readable use-case map, not native UML notation.

## Diagram index

| Diagram | Editable source | Meaning |
|---|---|---|
| High-level architecture | [architecture.mmd](diagrams/architecture.mmd) | Public/HEI/CHED entry points, application, records and private files |
| Component architecture | [components.mmd](diagrams/components.mmd) | UI → Actions → rules/access → storage/jobs |
| Core ERD | [erd-core.mmd](diagrams/erd-core.mmd) | Identity, versions, receipts, requirements and evidence |
| Operational ERD | [erd-operations.mmd](diagrams/erd-operations.mmd) | Reports, monitoring, restricted cases and clearance |
| DFD context / Level 0 | [dfd-0.mmd](diagrams/dfd-0.mmd) | External actors and whole application boundary |
| DFD Level 1 | [dfd-1.mmd](diagrams/dfd-1.mmd) | Preparation, filing, review, after-activity and records |
| Authentication/authorization | [auth.mmd](diagrams/auth.mmd) | Identity, MFA and record-level access checks |
| End-to-end workflow | [end-to-end.mmd](diagrams/end-to-end.mmd) | Institutional preparation through regional follow-up |
| Cross-role interaction | [cross-role.mmd](diagrams/cross-role.mmd) | Which responsibilities exchange records |
| Use cases | [use-cases.mmd](diagrams/use-cases.mmd), [use-cases.puml](diagrams/use-cases.puml) | Current actors and system capabilities |
| Submission sequence | [sequence-submission.mmd](diagrams/sequence-submission.mmd) | Validation, rollback, snapshot, review/corrections |
| Upload sequence | [sequence-upload.mmd](diagrams/sequence-upload.mmd) | Quarantine, scan failure, authorized retrieval |
| Case sequence | [sequence-case.mmd](diagrams/sequence-case.mmd) | External stage validation and historical gaps |
| Nine role flows | `diagrams/role-*.mmd` | Individual journeys; links in the role document |

ERDs deliberately omit some columns and infrastructure pivots for readability; the generated [complete column/FK dictionary](05-database-and-data-flow.md) supplies the full inspected QA schema. JSON references are identified as application references rather than invented database foreign keys.

Mermaid diagrams are embedded below for supporting Markdown viewers. Offline HTML/PDF exports include simple architecture/workflow visuals; rendering every Mermaid/UML diagram into images was not available without an additional renderer. No renderer or dependency was installed.

## Architecture

```mermaid
flowchart TB
 Visitor[Public visitor] --> Public[Blade public site]
 HEI[HEI preparer / submitter] --> HP[Filament HEI panel]
 CHED[CHED regional roles] --> CP[Filament CHED panel]
 Public --> App[One Laravel application]
 HP --> App
 CP --> App
 App --> DB[(Relational records / snapshots)]
 App --> Files[(Private files / quarantine)]
 App -. queued .-> Worker[Laravel worker]
 Worker --> Scanner[Configured local malware scanner]
 Worker --> Files
 Worker -. configured .-> Mail[Mail transport / QA capture]
 External[HEI signing / OSDS / CEB external work] -. recorded evidence .-> App
 Note[Local verified: SQLite. PostgreSQL deployment: prepared, unverified.] --- DB
```

## Components

```mermaid
flowchart LR
 UI[Filament / Livewire / Blade] --> Entry[Resources / Pages / web controllers]
 Entry --> Actions[Shared domain Actions]
 Actions --> Access[Permissions / membership / jurisdiction / confidentiality]
 Actions --> Rules[Versioned requirements / classification / deadlines]
 Actions --> Models[Eloquent / transaction guards]
 Models --> DB[(Relational data)]
 Actions --> Builders[Annex snapshot builders]
 Builders --> Print[Protected HTML / DOMPDF]
 Actions -. after commit where configured .-> Jobs[Scan / notify / export jobs]
 Jobs --> DB
 Jobs --> Private[(Private storage)]
 Models --> Audit[Audit events]
```

## Erd Core

```mermaid
erDiagram
 regions ||--o{ institutions : contains
 institutions ||--o{ campuses : has
 users ||--o{ institution_user : membership
 institutions ||--o{ institution_user : verifies
 institutions ||--o{ academic_periods : schedules
 academic_periods ||--o{ calendars : organizes
 institutions ||--o{ activities : owns
 calendars o|--o{ activities : includes
 activities ||--o{ activity_versions : revisions
 activity_versions ||--o{ activity_personnel : names
 activity_versions ||--o{ itinerary_items : itinerary
 activity_versions ||--o{ classification_assessments : assessments
 classification_rule_sets ||--o{ classification_assessments : rules
 policy_versions ||--o{ classification_rule_sets : baseline
 policy_versions ||--o{ policy_requirements : defines
 requirement_definitions ||--o{ policy_requirements : obligation
 policy_versions ||--o{ submissions : governs
 institutions ||--o{ submissions : files
 submissions ||--o{ submission_activities : includes
 activity_versions ||--o{ submission_activities : frozen_version
 submissions ||--o{ submission_requirements : snapshots
 submissions ||--o{ submission_reviews : reviews
 submission_reviews ||--o{ review_findings : findings
 submissions ||--o| acknowledgments : issued_letter
 document_types ||--o{ documents : classifies
 institutions ||--o{ documents : owns
 documents ||--o{ document_links : evidence_links
 submissions o|--o{ submissions : supersedes
```

## Erd Operations

```mermaid
erDiagram
 institutions ||--o{ completion_report_drafts : prepares
 activities ||--o{ completion_reports : completed
 completion_report_drafts o|--o| completion_reports : filed
 activities ||--o{ incident_reports : incident
 incident_reports ||--o{ incident_persons : report_people
 incident_reports o|--o{ incident_reports : supplement
 activities ||--o{ change_reports : deviations
 change_report_drafts o|--o| change_reports : filed
 institutions ||--o{ monitoring_records : monitored
 monitoring_records ||--o{ monitoring_items : criteria
 institutions ||--o{ corrective_actions : followup
 institutions ||--o{ technical_assistance_logs : guidance
 institutions ||--o{ case_records : restricted
 case_records ||--o{ case_events : immutable_history
 documents o|--o{ case_events : evidence
 activity_versions ||--o{ clearance_requests : exact_plan
 clearance_requests ||--o| reconsiderations : one_request
 institutions ||--o{ institutional_restrictions : signed_orders
 regions ||--o{ regional_reports : summaries
 users ||--o{ exports : requests
 users o|--o{ audit_events : actor
```

## Dfd 0

```mermaid
flowchart LR
 H[HEI personnel] -->|Plans / evidence / reports| S((LAKBAY regional records))
 S -->|Receipts / deficiencies / printable forms| H
 C[CHED regional personnel] -->|Review / monitoring / official evidence| S
 S -->|Filed records / letters / regional outputs| C
 V[Public visitor] -->|Guidance request| S
 S -->|Public guidance / registered templates| V
 X[External HEI authorization / OSDS / CEB] -. correspondence recorded by personnel .-> S
 S -. configured invitations / notices .-> M[Mail transport; QA capture]
```

## Dfd 1

```mermaid
flowchart TB
 H[HEI] --> P1((1 Prepare / classify))
 P1 --> D1[(Institutions / policy / versions)]
 H --> P2((2 Upload / scan))
 P2 --> D2[(Private documents / scan state)]
 P1 --> P3((3 Submit / snapshot))
 D2 --> P3
 D1 --> P3
 P3 --> D3[(Immutable submissions / evidence)]
 D3 --> P4((4 Review / corrections / acknowledgment))
 C[CHED roles] --> P4
 P4 --> D3
 P4 -->|Correction request| H
 H --> P5((5 Reports / follow-up))
 C --> P5
 P5 --> D4[(Reports / monitoring / restricted history)]
 D3 --> P6((6 Regional snapshot / export))
 D4 --> P6
 P6 -->|Authorized output| C
 P3 --> A[(Audit / delivery records)]
 P4 --> A
 P5 --> A
```

## Auth

```mermaid
flowchart TD
 I[Invitation: expiring hashed token] --> Accept[Set password / one-time acceptance]
 Accept --> Login[Panel login]
 Login --> Identity{Active / verified email / panel role and affiliation?}
 Identity -->|No| Deny[Access denied]
 Identity -->|Yes| MFA{Required authenticator MFA satisfied?}
 MFA -->|No| Setup[Enroll / complete challenge]
 Setup --> MFA
 MFA -->|Yes| Record{Permission + ownership/jurisdiction + confidentiality?}
 Record -->|No| Deny
 Record -->|Yes| State{Valid record state / required evidence?}
 State -->|No| Error[Validation / stale-state error]
 State -->|Yes| Action[Shared Action or protected controller]
 Action --> Audit[Record transition/access audit]
```

## End To End

```mermaid
flowchart TD
 Plan[HEI plan / classify / consult] --> Prepare[Prepare forms and applicable evidence]
 Prepare --> External[External HEI authorization / signing / notarization]
 External --> Upload[Private upload / successful genuine scan]
 Upload --> Check{Authorized and complete applicable checklist?}
 Check -->|No| Prepare
 Check -->|Yes| Submit[Submit frozen receipt/version]
 Submit --> Review[CHED completeness review]
 Review --> Decision{Deficiencies?}
 Decision -->|Yes| Correct[HEI revises / resubmits new receipt]
 Correct --> Review
 Decision -->|No| Ack[Record signed completeness acknowledgment]
 Ack -. separate checks; not authorization .-> Conduct[HEI conducts under external authority / restrictions / clearance]
 Conduct --> Reports[ROC / incident / change reports]
 Reports --> Monitor[Monitoring / assistance / corrective follow-up]
 Monitor --> Regional[Regional snapshots / recorded OSDS dispatch]
```

## Cross Role

```mermaid
flowchart LR
 Prep[HEI preparer] -->|Prepared records| Submit[HEI submitter]
 Submit -->|Frozen filing| Records[CHED records / intake support]
 Submit --> Reviewer[CHED reviewer]
 Reviewer -->|Correction advice| Submit
 Reviewer -->|Complete receipt| Sign[CHED signatory]
 Sign -->|Signed acknowledgment| Submit
 Monitor[CHED monitor] <-->|Follow-up evidence| Submit
 Analyst[CHED analyst] -->|Regional snapshots / exports| Output[External regional reporting]
 Case[CHED case officer] -->|Restricted external evidence| Cases[Case / clearance records]
 Admin[System administrator] -->|Invitations / reference and policy controls| Accounts[Verified personnel]
 Note[External OSDS / CEB decisions recorded; no national panel] --- Cases
```

## Use Cases

```mermaid
flowchart LR
 HEI[HEI preparer / submitter] --> A([Prepare plans / Annexes])
 HEI --> B([File and correct records - submitter only])
 HEI --> C([File reports / respond to follow-up])
 R[CHED reviewer] --> D([Review completeness / deficiencies])
 S[CHED signatory] --> E([Prepare and record acknowledgment])
 M[CHED monitor] --> F([Monitor / assist / follow up])
 K[CHED case officer] --> G([Record restricted external procedure])
 N[CHED analyst] --> H([Regional snapshot / export])
 I[Administrator] --> J([Invite / maintain references / policy controls])
 Public[Public visitor] --> P([Read guidance])
 Guard([MFA and record authorization]) -. applies .-> A
 Guard -. applies .-> G
```

## Sequence Submission

```mermaid
sequenceDiagram
 actor HEI as Authorized HEI submitter
 participant UI as Filament/Livewire
 participant Act as SubmitActivity
 participant Rule as Access / PolicyRules / Resolver
 participant DB as Database
 HEI->>UI: File applicable stage and evidence
 UI->>Act: execute(selected versions, policy, evidence)
 Act->>DB: Begin transaction / lock records
 Act->>Rule: Verify membership, state, eligible policy and completeness
 alt Invalid or stale
 Rule-->>Act: Validation / denial
 Act->>DB: Rollback
 Act-->>UI: Error; no filed revision
 else Valid
 Act->>DB: Freeze receipt, versions, evidence and annex payloads
 Act->>DB: Audit / commit
 Act-->>UI: Receipt and follow-up notice
 end
 actor Reviewer as CHED reviewer
 Reviewer->>UI: Review exact receipt
 UI->>DB: Record findings / review transition via Action
 alt Corrections
 UI-->>HEI: Deficiency advice
 HEI->>Act: Resubmit new revision
 else Complete
 UI-->>Reviewer: Complete; signatory step remains separate
 end
```

## Sequence Upload

```mermaid
sequenceDiagram
 actor User as Authorized uploader
 participant App as UploadDocument
 participant Store as Private storage
 participant DB as Document records
 participant Worker as ScanDocument job
 participant Scanner as Configured scanner
 User->>App: Valid file and metadata
 App->>Store: Store private bytes
 App->>DB: Pending scan / hash / confidentiality
 App-->>Worker: Queue scan
 Worker->>Scanner: Scan resolved local path
 alt Scanner fails or infection found
 Worker->>DB: Preserve quarantine / infected state
 User->>App: Attempt retrieval
 App-->>User: Denied until clean
 else Genuine successful scan and hash match
 Worker->>DB: Clean
 User->>App: Preview / download
 App->>DB: Recheck access and scan state; audit
 App->>Store: Retrieve authorized bytes
 App-->>User: Protected response
 end
 Note over App,Store: Fresh generic retrieval hash check remains FIX-08
```

## Sequence Case

```mermaid
sequenceDiagram
 actor Officer as CHED case officer
 participant UI as Restricted case form
 participant Act as RecordCaseEvent
 participant DB as Case / evidence / events
 Officer->>UI: Stage, actual time, evidence and particulars
 UI->>Act: execute with replay key
 Act->>DB: Regional/confidential access, lock case, inspect prior evidence
 alt New decision missing required stages
 Act-->>UI: Validation failure; no event
 else Explicit historical external record with explanation
 Act->>DB: Preserve missing-stage snapshot and actual date
 else Complete valid stage
 Act->>DB: Append immutable event and audit
 end
 Act-->>UI: Recorded timeline; no automatic restriction
 Note over Officer,DB: External Committee/CEB work and HEI service are recorded, not performed
```
