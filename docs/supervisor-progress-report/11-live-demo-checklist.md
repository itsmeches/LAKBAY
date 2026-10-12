# 5–7 minute live demonstration

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

## Before the meeting

- Use the **already migrated, isolated FIX-05 QA copy**, fictional institutions and local mail capture. Do not use production data or reset/reseed the normal database. The normal DB has three pending migrations.
- Confirm the intended QA URL responds; the verification server at port 8007 was stopped when testing finished, so its old URL is not a running service. Start only the approved isolated QA configuration using the existing setup/run instructions in `docs/UAT-guide.md` and `docs/deployment.md`; do not launch normal Artisan with the wrong database/storage path.
- Test HEI submitter, CHED reviewer and signatory logins before the meeting; optionally monitor. Keep separate browser profiles/contexts to avoid role-cookie confusion. Use genuine MFA; keep credentials/recovery codes out of slides.
- Check private-file access and scanner/worker only in QA. Never mark files clean for presentation. Confirm the required named actions are visible.
- Prepare a complete filed example and an existing correction/acknowledgment. Read-only display is safer than creating a time-sensitive full lifecycle on stage. State when a fixture is already filed.
- Open the HTML slides, PDF report and backup visuals from the report package. New reports/exports contain source references, not passwords or session files.

## Timed demonstration

| Time | Role / action | Expected visible response | What to explain |
|---|---|---|---|
| 0:00–0:40 | Public: home/guidelines | Portal links and policy guidance | Public information is separated from private submissions |
| 0:40–2:00 | HEI submitter: Annual calendars → existing CLOCA; Preview / print CLOCA | Entered institutional fields/table/signatories and unsigned PDF | Fields map into the form; signed original remains separate |
| 2:00–3:00 | HEI: existing submission → preserved CLOCA/requirements/correction | Exact filed revision, evidence states and correction history | Later edits cannot change the old receipt |
| 3:00–4:00 | CHED reviewer: Submission inbox | Filed-start column, attention cue, complete/deficiency review record | This is completeness review, not conduct authorization |
| 4:00–5:00 | CHED signatory: complete calendar package → saved Annex K preview | Addressee/Director/received date/program totals; issued signed original if existing | Separate preparation and signed acknowledgment; no click claims approval |
| 5:00–6:00 | CHED monitor: existing Monitoring visits record → print Annex M | Fourteen criteria, verification/findings and HEI conforme block | Monitoring and corrective follow-up have documented evidence |
| 6:00–6:40 | Return to progress/next-step slide | FIX-05 delivered; FIX-06 pending | Do not display real confidential cases or stage policy activation |

Optional technical defense: show the fictional restricted-case timeline under a case officer in a separate session, then show that administrator access is denied. Prefer the diagram instead if confidential workflow is not needed by the supervisor. Case stages record external evidence, not legal decisions performed by LAKBAY.

## If something fails

Say: “This is the local environment; I will use the captured QA output and record the environment issue.” Show `exports/annex-B-example.png`, `annex-M-example.png`, architecture/workflow visuals and the verified results. Do not loosen permissions, activate policy, disable MFA/scanner or overwrite records to make the demonstration proceed.

Avoid claiming FIX-06 routing completeness, FIX-07 narrative aggregation, FIX-08 fresh file-hash retrieval, exact legal-template approval, public Annex N availability, PostgreSQL readiness or production mail. No infrastructure installation or database mutation was performed for this package.
