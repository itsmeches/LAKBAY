# Cross-role handoffs and presentation explanations

Based on the implemented baseline `741f29d`, not a promise of production readiness. Roles verified against `database/seeders/DatabaseSeeder.php`; journeys checked against the report's [role/permission evidence](../04-user-roles-and-workflows.md). Public visitors are not a stored role.

## Responsibility handoffs

| From | To | What is handed over |
|---|---|---|
| HEI preparer | Authorized HEI official, outside the app | Forms for authorization, signing or notarization as applicable |
| HEI preparer | HEI submitter | Prepared activity and scanned evidence |
| HEI submitter | CHED reviewer, supported by records staff | Frozen filed package and exact evidence version |
| CHED reviewer | HEI submitter, if incomplete | Specific correction request; the HEI files a new revision |
| CHED reviewer | CHED signatory, if complete | Completeness record for acknowledgment preparation |
| CHED signatory | HEI | Signed acknowledgment of the exact calendar submission |
| HEI submitter | CHED monitoring personnel | Activity reports and permitted supporting records |
| CHED monitor | HEI / regional reporting | Recommendations, recorded follow-up and monitoring information |
| CHED analyst | Official external recipients | Authorized regional outputs; no national portal is implemented |

The administrator supports account and reference setup. The case officer follows a separate confidential process when a case exists; neither is an obligatory stage of every activity. The overall chart shows the usual successful sequence. **Acknowledgment is not authorization or a prerequisite that replaces HEI authority, restrictions or exceptional clearance.**

## Presentation explanations

### Whole-system journey

The HEI prepares and files its evidence. CHED checks completeness and records an acknowledgment. The HEI conducts under its own authorization and applicable restrictions or clearance, then reports outcomes. CHED monitors and compiles regional information.

- If incomplete: CHED requests corrections; HEI files a new submission.
- Acknowledgment is not permission to conduct. HEI authority, restrictions
- and any required clearance independently govern conduct; this is the typical journey.

### HEI Preparer

The preparer assembles activity information and forms, arranges external authorization and signing, and uploads evidence. Once the package is ready, the authorized submitter takes over.

- Missing information stays in draft until corrected.
- This role prepares and uploads; it cannot submit official reports or packages.

### HEI Submitter

The authorized submitter checks the package and files it officially. They follow review messages, resubmit corrections when needed, and file the activity reports.

- If CHED requests corrections: revise and submit a new package.
- Only successfully scanned evidence is retrievable; filed versions stay unchanged.
- Acknowledgment confirms receipt/completeness, not authorization to conduct.

### CHED Records Staff

Records staff locate submissions, inspect permitted documents and organize correspondence. They support the review team without making completeness or signing decisions.

- This role does not review completeness or issue acknowledgments.
- Restricted records and unscanned files remain protected.

### CHED Reviewer

The reviewer checks the requirements against the exact filed package. Missing information results in a correction request. A complete package is ready for the signatory.

- If incomplete: request specific corrections and review the new submission.
- Completeness review is not activity approval; the signatory issues the letter.

### CHED Signatory

The signatory prepares Annex K for a complete calendar package, arranges official signing, and records the clean signed letter against the exact submission.

- Incomplete evidence or an unscanned letter prevents issuance.
- The letter confirms receipt/completeness, not permission to conduct.

### CHED Monitor

The monitor documents the visit using Annex M, records findings and recommendations, and prints the form. Assistance and corrective follow-up are recorded when necessary.

- Follow-up is recorded when needed; it is not required for every visit.
- Monitoring findings do not automatically impose sanctions.

### CHED Case Officer

The case officer records evidence of external proceedings, recommendations, decisions and receipt. Access is confidential, and a complaint alone does not impose a sanction.

- External proceedings are recorded here; the app does not perform them.
- New decisions require prior evidence; historical gaps must be explained.
- A complaint alone does not suspend an HEI. Clearance routing remains unfinished.

### CHED Analyst

The analyst selects the reporting period, freezes the regional figures and requests an export. Once ready, the authorized report can be downloaded and shared through the official external process.

- Failed exports can be retried; expired outputs need a new request.
- Student totals count participations, not distinct students.
- External dispatch/receipt can be recorded; full narrative coverage is pending.

### System Administrator

The administrator maintains institutions and invites personnel. Invited users complete their own account setup and MFA. Policy controls remain subject to publication verification and CHED authority.

- CMO 24 remains a candidate; activation requires verified publication and authority.
- Administration does not grant access to confidential cases.
- No policy activation is part of this journey.

