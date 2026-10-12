# Recommended next milestones

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

This is a proposed sequence, not authorization to implement FIX-06 or later work.

| Order | Deliverable | Dependency / acceptance |
|---|---|---|
| 1 | Present the current tested regional baseline | Use migrated isolated QA; disclose policy/environment limits |
| 2 | Approve scoped FIX-06 | CMO §27, PDF pp.13–14: complete recorded routing, OSDS staff-work proof, exact activity-version decision and one receipt-based reconsideration; unknown history retained |
| 3 | Implement/test FIX-06 after approval | Additive/idempotent changes, dry-run counts, no policy activation, preserved receipts; test late filing vs acceptance and separate three-working-day requirements |
| 4 | FIX-07 regional outputs | Freeze institutional findings/recommendations; correct signed regional-report evidence association and dispatch/receipt |
| 5 | FIX-08 retrieval integrity | Fail closed on altered/missing private bytes without tampering with existing uploads; privacy-safe integrity audit |
| 6 | FIX-09–11 confirmation/output checks | HEI feedback, Annex K exact formatting/reference acceptance, both Annex N reference pages/order differences |
| Parallel decision track | CHED policy/records/DPO answers | Publication/reference, SAA scope, Cat 3 timelines, RCD anchor, materiality, holidays, privacy/retention/signatories and legal receipt rules |
| Parallel ICT track | Isolated PostgreSQL staging | Working container/driver/tooling, full applicable tests/migrations, concurrency, backup restore and production-like scanner/queues |
| Pilot | HEI/CHED UAT on approved representative institutions | Training, accessibility, incident fallback, worker/mail/observability and formal acceptance criteria |
| Controlled release | Deployment and policy confirmation separately approved | Verified publication/effectivity; hosting/HTTPS/keys/mail/backup/retention; no historical rule rewrite |

FIX-06 matters because receipt of a clearance application is not an activity-specific grant. Current recorded routing can be incomplete, while signed decisions can already be recorded. The next work should make missing staff-work/routing visible and enforce the approved chain without inventing historical events. Clarify the ninety-day day unit; do not silently replace later applicable evidence deadlines.

No student portal, national OSDS panel, mobile/API client, electronic signature, spreadsheet import or new infrastructure stack is proposed for the next milestone. Login/MFA shared-office-IP throttling remains a separate plan awaiting approval.

Evidence: `docs/fix-backlog.md` FIX-06–11; `docs/next-steps.md`; `docs/open-questions.md`; `docs/policy-activation-runbook.md`; `docs/deployment.md`.
