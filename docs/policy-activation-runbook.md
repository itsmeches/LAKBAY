# Policy activation and rollback runbook

Status: operational preparation only. No policy was activated during verification on 8 October 2026.

## What candidate and active mean

A candidate is an editable policy proposal. CMO24-2026-SAA-R1 is the corrected candidate; its predecessor remains historical. Neither has verified publication information in the development databases. `confirmed` is a stored status; there is no separate `active` status. CMO eligibility requires confirmed status, publication date/reference, effectivity equal to publication +15 days, and an effectivity date that has arrived. Issuance alone is insufficient.

Local/testing with `lakbay.allow_draft_policy=true` deliberately permits candidate submissions for experimentation. HEI submission selectors expose draft/candidate/confirmed versions there; choosing an old baseline on a new submission follows an eligible successor and rebuilds the evidence fields. Existing selections are cleared/refilled, not migrated in the database. Preparation records are not bulk rewritten. CHED sees the exact submitted requirements and evidence snapshots, plus policy configuration for authorized staff. Optional preparation forms do not constitute legal acceptance. Production selectors expose confirmed policies and server validation enforces eligibility.

Verification found a gap #1 regression: draft selection followed an unactivated candidate in production. The fix reuses `authorizeSubmission` when choosing successors. Local candidate experiments remain allowed; production retains its prior eligible baseline until a successor is eligible. Corrections reference the previous submission's policy, not the latest candidate. An old submission is never silently re-evaluated.

## Preconditions and authority

1. Obtain authoritative publication date/reference, the confirmation reference and CHED approval of the rule matrix, term coverage and unresolved interpretations. Do not guess publication information.
2. Back up the database and private storage using the approved production backup procedure. Restore to an isolated database and compare counts/checksums. Record deployment commit, policy IDs/codes, source hash and backup identifiers.
3. Run browser, PostgreSQL, scanner and recovery gates; SQLite tests alone are insufficient. Ensure production environment and candidate testing disabled.
4. Inspect the candidate's requirements and final annex versions. Capture their hashes and the open-draft/submission inventory. Notify users through the authorized operational channel; this runbook sends no messages.
5. Only an active, email-verified CHED user with regional assignment, `panel.ched` and `policy.manage` may invoke `ConfirmPolicy`. The policy configuration page is under CHED Administration. Access requires the normal session/MFA controls. Administrative roles do not imply confidential-case access.

## Controlled confirmation and effectivity

1. Open CHED Policy configuration and the confirmation action; select the reviewed candidate code explicitly.
2. Enter verified publication date/reference and confirmation reference, and record authorized CHED confirmation. The Action derives effectivity at publication +15 days, rejects future publication/issuance contradictions, locks the row, makes it immutable and records `policy.confirmed` in audit history.
3. Confirmation before effectivity does not permit early official submission. There is no cron job required to flip an active flag; submission checks evaluate the date at use time.
4. After effectivity, verify a new unsubmitted draft follows the eligible successor and resolves its rules. Reopen the submission dialog and reselect evidence because checklist fields are regenerated. A stale direct submission is rejected with a reopen/recompute instruction.
5. Verify historical submissions, requirement verdicts, certifications, signed originals and acknowledgment references remain unchanged. Corrections continue with their original baseline unless a separate authorized policy decision is implemented.
6. Reporting policy selection is separate: `forActivity` chooses the latest effective, verified confirmed CMO policy, otherwise the latest nonsuperseded submission baseline or July draft. Existing report snapshots/deadlines are not rewritten by activation. Verify new event calculations explicitly.

## Rollback: limitations and safe response

There is no supported one-click policy deactivation, automatic draft rollback, or confirmed-policy demotion. Model guards prevent editing/deleting confirmed policy and its components. Do not bypass those guards, restore an old database over newer official submissions, remove evidence, or edit audit history.

Before confirmation: leave the candidate unconfirmed; production continues to use its eligible baseline. Local testing deliberately follows candidates and needs an operationally isolated environment. The existing app does not offer a withdrawal/pinned-baseline control; stopping a local experiment must not be represented as implemented policy rollback.

After confirmation/effectivity: stop new affected submissions through the approved operational maintenance procedure; preserve read access and incident fallback according to CHED procedures. Record the incident, affected versions and earliest affected receipt. Revert application code only if necessary, using the recorded release commit; code rollback does not reverse policy data or business records.

Prepare a prospective corrective policy using **Create editable policy version**: this copies rules/templates but does not supply a complete operational rollback workflow. Its copied parameters may retain a predecessor reference; the page does not provide explicit successor-link editing. A reviewed additive change is required to establish the intended supersession linkage and applicable effective date. Obtain CHED authority before that change; do not invent a retroactive publication date. Confirm and verify the corrective version through the same gates. Historical receipts remain on their original version. If emergency selection/pinning is required, treat that as a separately designed and tested capability; this task does not implement it.

Use a scratch restore only to investigate or recover from actual data loss, with an authorized recovery plan that accounts for records received after backup. The verified SQLite scratch restore here is development evidence, not a PostgreSQL production recovery certification.
