# 10–15 minute supervisor presentation

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

Use the supplied HTML/PDF slide deck. Aim for 11–12 minutes, followed by questions; the longer standalone live demo can take 5–7 minutes if scheduled separately.

| Section / time | Key points and visual | Technical explanation | Plain-language emphasis |
|---|---|---|---|
| 1. Overview / 0:40 | Regional compliance records; title slide | One Laravel application, two panels | One organized place for HEI submissions and CHED records |
| 2. Problem / 0:45 | Documents, revisions and deadlines scattered | Versioned intake and snapshots | Know what was actually submitted and what needs attention |
| 3. Objectives / 0:40 | Prepare, file, review, follow up | Form mapping + guarded transitions | Less repeated encoding; clearer responsibilities |
| 4. Architecture / 1:00 | Architecture visual | Filament/Livewire → Actions → relational records/private files/jobs | Separate workspaces share one controlled records system |
| 5. Roles / 0:50 | HEI / review / sign / monitor / cases / admin | Nine seeded roles plus ownership/confidentiality | Administrator does not automatically see sensitive cases |
| 6. Workflow / 1:00 | End-to-end flow | Immutable receipt, correction revision, separate acknowledgment | CHED receipt is not permission to conduct |
| 7. Current features / 2:00 | Mini-demo CLOCA/letter/monitoring | Fields → unsigned PDF → signed original → frozen snapshot | Show prepared form and preserved filed copy |
| 8. Progress / 0:55 | FIX-01–05 milestones | Policy rules, classification, readiness, review and cases | Concrete delivered improvements; no guessed percentage |
| 9. Security / 0:55 | Access/quarantine/history | MFA, regional ownership, scanner, jobs | Private documents are unavailable until clean scan |
| 10. Limitations / 1:00 | Candidate / environment / production gates | Normal DB pending migrations; no local PostgreSQL run | Strong local evidence, honest remaining risks |
| 11. Next work / 0:55 | FIX-06 + staging/CHED decisions | Complete external clearance staff-work/evidence chain | Trace a case-specific decision without implying authorization from receipt |
| 12. Discussion / 0:30 | Requested decisions | Policy/ICT/DPO ownership and pilot scope | Request approval of next scoped work, not immediate launch |

Technical file citations belong in the written appendix, not on every live slide. Emphasize demonstrated outcomes and distinguish external decisions from app recording. If asked to prove a claim, use the feature matrix, role seed or retained test output.
