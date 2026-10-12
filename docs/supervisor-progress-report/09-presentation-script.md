# Suggested speaking script

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

## 1. Overview

“Today I’m presenting LAKBAY’s current development baseline for CHED Region IV-A. It helps HEIs prepare and submit off-campus activity records and helps regional staff review, acknowledge, monitor and follow up. The central point is that we now have a tested regional workflow, while production and some policy decisions are still pending.”

## 2. Problem

“The practical problem is keeping forms, supporting files, corrections and subsequent reports consistent. When a plan changes, we need to know what the HEI originally filed, what changed, and which record CHED reviewed. LAKBAY preserves those versions instead of replacing the original.”

## 3. Objectives

“Our objectives are clearer preparation, controlled submission, reliable historical records and visible follow-up. We are also mapping the CMO forms to application fields so staff can prepare printable documents from information already encoded.”

## 4. Architecture

“The application has a public information site, an HEI workspace and a CHED workspace. They share one Laravel backend. Business transitions use shared Actions, so the review and submission rules are enforced by the server rather than only by visible buttons. Files are private and scanned before access.”

## 5. Responsibilities

“HEI preparers encode and upload; authorized submitters file. CHED records staff support intake, reviewers check completeness, signatories record signed letters, monitors record visits, analysts prepare reports and case officers manage restricted records. Administrators manage identities and reference settings but do not automatically receive confidential-case access.”

## 6. Workflow

“An HEI prepares its activity, completes applicable requirements, obtains its own authorization and signs documents externally. Submission creates a preserved receipt. CHED can request corrections, which create a new revision, or record completeness. A signed acknowledgment is separate. It confirms receipt and completeness; it is not permission to conduct the activity.”

## 7. Demonstration

“Here are the entered CLOCA details and the generated preparation. This is an unsigned copy for external execution. Here is a filed preparation tied to an exact receipt, so later editing cannot change that historical output. I will also show the acknowledgment and the fourteen-item monitoring record. These are fictional QA examples, not live institutional filings.”

## 8. Progress

“Recent fixes corrected ordinary Category 3 SAA treatment, added classification and change history, strengthened PIC/APIC readiness, improved review attention and change notices, and completed the restricted procedural ledger. The latest local campaign passed 497 PHPUnit tests with 2,385 assertions and 38 browser tests. These are recorded results from the SQLite QA environment, not a production acceptance certificate.”

## 9. Security

“Access is checked using role, institution, region and confidentiality. Both workspaces require MFA. Documents remain quarantined when scanning fails. Submissions preserve evidence and rule versions, and important operations create audit records. We still have planned file-retrieval integrity hardening and production access/recovery validation.”

## 10. Limitations

“The final CMO is our source, but official activation remains blocked until publication and its reference are verified. Several operational interpretations also need CHED confirmation. The normal workstation database is behind the migrated QA database, so the demonstration uses the tested QA environment. PostgreSQL, production mail, hosting and recovery are still release gates.”

## 11. Next work

“The next proposed task is FIX-06. Clearance applications, routing and decisions already have records, but the system does not yet enforce a complete recorded chain including OSDS staff-work evidence. We need that traceability, and we must keep application receipt, the ninety-day filing requirement and later documentary deadlines separate.”

## 12. Request and questions

“I’m asking for agreement on the next scoped work, identification of policy and ICT/DPO reviewers, and a controlled pilot plan. I am not proposing immediate production launch or treating a local test result as proof of full regulatory compliance. I can show the supporting evidence and remaining decisions for any part of the workflow.”
