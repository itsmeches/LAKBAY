# Actual roles, permissions and journeys

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

The seed defines **nine roles**. Public visitors are an actor, not a stored role. Role grants below are the seeded defaults, not a claim that every deployed user has only one role. Verified institutional membership/submitter authority or CHED region, email verification, active account, MFA, record state and confidentiality apply in addition to role permissions.

Evidence: `database/seeders/DatabaseSeeder::run`; `User::canAccessPanel`; `Access::{owns,regional,authorize,document}`; `InstitutionRecordPolicy::view`; `ScopedResource`; `WorkflowUi::actions`. A hidden button is not the security boundary: shared Actions and download controllers enforce access.

## User journeys

| Role | Responsibility and step-by-step journey | Limits and interactions |
|---|---|---|
| `hei_preparer` | Accept invitation → choose own HEI → create academic period/calendar/activity → complete Annex B–F/J inputs → preview/print → upload signed/ordinary evidence → check scan state | Can prepare/upload; cannot file submissions/reports. Hands prepared records to authorized HEI submitter. Approval/signing remain outside app. |
| `hei_submitter` | Prepare or inspect HEI plans → confirm saved classification where selected → attach applicable evidence → submit → read correction → revise/resubmit → receive acknowledgment → file completion/incident/change information → respond to follow-up | Must have authorized verified membership. Ordinary Category 3 SAA is excluded; escalated Category 3 has Category 2 treatment. No access to CHED restricted cases. |
| `ched_records` | Sign in → open regional intake/records → inspect filed snapshots and institutional documents → upload correspondence/evidence → locate issued letters | Read/upload support; no seeded review/signatory/monitor/case/export privilege. Cannot replace immutable filed originals. |
| `ched_reviewer` | Sort submission inbox by filed date → inspect exact revision/checklist → review completeness → request specific correction or record complete → review resubmission/evidence response | Completeness review, not activity approval. Does not issue signed acknowledgment with this role alone; confidential incidents/cases excluded. |
| `ched_signatory` | Open complete calendar package → prepare Annex K addressee/date/Director details → inspect originals → preview → sign externally → upload clean signed acknowledgment → record issue | Seeded role can issue acknowledgment, not perform review. Coordination with reviewer is required. No automatic notarization/signing. |
| `ched_monitor` | Select regional institution → create monitoring visit → complete 14 Annex M checks/verification/findings/signatories/conforme particulars → print → record assistance/corrective follow-up | Rating is human-entered operational information, not automatic sanction. HEI follow-up is separately recorded. No restricted-case authority. |
| `ched_case_officer` | Open regional restricted case → record signed source/referral/findings/Committee/CEB evidence → record HEI dispatch/receipt → review unknown historical flags; record external clearance/reconsideration when authorized | Requires `cases.sensitive`; incident access uses `incidents.view`. Complaint does not create suspension. FIX-06 routing completeness is pending. Cross-region transfer not granted by referral. |
| `ched_analyst` | Open regional reporting → select period/cutoff → prepare snapshot → request CSV/PDF → worker generates → retrieve authorized unexpired export → record external dispatch/receipt where supported | Summed student participations, not distinct students. Monitoring narrative/dispatch association gap remains FIX-07. No case evidence/incident narrative access. |
| `system_administrator` | Maintain regional institution/campus records → invite verified personnel → assign supported role/membership → inspect policy controls → await documented CHED/publication authority before confirmation | No seeded restricted-case, review, issue, monitor or export power. Do not activate candidate during demo. Invitation acceptance creates account; MFA enrollment follows. |

All users encounter alternatives: invalid inputs retain a draft/show errors; unauthorized access is denied; missing scan blocks retrieval; stale/duplicate transition is rejected where guarded; corrections create another receipt rather than editing the old one. HEI execution is externally authorized and readiness is separate from acknowledgment.

## Seeded RBAC matrix

The permission matrix below is generated directly from the role seed. `Y` means a seeded grant only; an accessible action still needs ownership/jurisdiction and its other guards. Ordinary regional read access excludes sensitive/restricted content unless the explicit additional permission applies.

| Role | panel.hei | records.view | activities.manage | documents.upload | submissions.submit | reports.submit | panel.ched | submissions.review | acknowledgments.issue | monitoring.manage | cases.sensitive | incidents.view | reports.export | institutions.manage | users.invite | policy.manage |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| hei_preparer | Y | Y | Y | Y | — | — | — | — | — | — | — | — | — | — | — | — |
| hei_submitter | Y | Y | Y | Y | Y | Y | — | — | — | — | — | — | — | — | — | — |
| ched_records | — | Y | — | Y | — | — | Y | — | — | — | — | — | — | — | — | — |
| ched_reviewer | — | Y | — | Y | — | — | Y | Y | — | — | — | — | — | — | — | — |
| ched_signatory | — | Y | — | Y | — | — | Y | — | Y | — | — | — | — | — | — | — |
| ched_monitor | — | Y | — | Y | — | — | Y | — | — | Y | — | — | — | — | — | — |
| ched_case_officer | — | Y | — | Y | — | — | Y | — | — | — | Y | Y | — | — | — | — |
| ched_analyst | — | Y | — | — | — | — | Y | — | — | — | — | — | Y | — | — | — |
| system_administrator | — | Y | — | — | — | — | Y | — | — | — | — | — | — | Y | Y | Y |

### hei_preparer

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open own HEI workspace]
 N0 --> N1[Prepare calendar / activity and forms]
 N1 --> N2[Print unsigned preparation / sign externally]
 N2 --> N3[Upload evidence; wait for clean scan]
 N3 --> N4[Hand off to authorized submitter]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-hei_preparer.mmd).

### hei_submitter

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open verified authorized HEI]
 N0 --> N1[Inspect / confirm classification where selected]
 N1 --> N2[Prepare applicable signed evidence]
 N2 --> N3[Submit frozen receipt]
 N3 --> N4[Corrections: revise and resubmit]
 N4 --> N5[Read issued letter; file ROC / incidents / changes]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-hei_submitter.mmd).

### ched_records

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open regional records]
 N0 --> N1[Inspect exact intake and institutional evidence]
 N1 --> N2[Upload correspondence / locate issued letter]
 N2 --> N3[Hand review responsibility to reviewer]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_records.mmd).

### ched_reviewer

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open regional submission inbox]
 N0 --> N1[Inspect filed start and exact revision]
 N1 --> N2[Review completeness]
 N2 --> N3[Deficiencies: request corrections; otherwise record complete]
 N3 --> N4[Signatory records separate signed acknowledgment]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_reviewer.mmd).

### ched_signatory

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open complete calendar submission]
 N0 --> N1[Prepare Annex K / inspect originals]
 N1 --> N2[Preview; sign externally]
 N2 --> N3[Upload clean signed letter]
 N3 --> N4[Record acknowledgment of exact receipt]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_signatory.mmd).

### ched_monitor

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Select regional institution / monitoring visit]
 N0 --> N1[Complete 14 checks and verification]
 N1 --> N2[Record findings / signatories / HEI conforme particulars]
 N2 --> N3[Print saved Annex M]
 N3 --> N4[Assistance / corrective follow-up]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_monitor.mmd).

### ched_case_officer

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Open regional restricted record]
 N0 --> N1[Inspect signed source and unknown history]
 N1 --> N2[Record referral / findings / CHED Committee]
 N2 --> N3[Record signed CEB decision with prerequisites or explicit historical gaps]
 N3 --> N4[Record written HEI dispatch / linked receipt]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_case_officer.mmd).

### ched_analyst

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Select regional cutoff and reporting period]
 N0 --> N1[Prepare regional snapshot]
 N1 --> N2[Request CSV / PDF export]
 N2 --> N3[Worker rechecks access and generates]
 N3 --> N4[Retrieve unexpired private output / record external dispatch]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-ched_analyst.mmd).

### system_administrator

```mermaid
flowchart TD
 Login[Sign in and complete MFA] --> Guard{Active verified role and affiliation?}
 Guard -->|No| Deny[Denied]
 Guard -->|Yes| N0[Maintain regional institutions / campuses]
 N0 --> N1[Invite supported verified personnel]
 N1 --> N2[Inspect policy / publication controls]
 N2 --> N3[Await written CHED authority; do not activate candidate in demo]
 N0 -. invalid input / evidence / state .-> Error[Validation error; no unauthorized transition]
 Error --> N0
```

[Editable flow](diagrams/role-system_administrator.mmd).
