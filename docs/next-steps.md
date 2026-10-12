# Remaining work after Gap #2 Slice 3

Saved 8 October 2026. This is a planning list, not authorization to implement the remaining items. Detailed acceptance criteria and source references remain in [the fix backlog](fix-backlog.md) and [traceability matrix](traceability-matrix.csv).

Slice 3 is committed as `3cd71b5` on `fix/gap2-classification-slice3`. Its verification passed 462 PHPUnit tests with 2,229 assertions, 35 browser tests, Pint, PHPStan, syntax checks and the asset build. CMO 24 remains a candidate; PostgreSQL verification is still blocked by missing local tooling. See [the executed report](gap2-slice3-report.md).

| Item | Remaining work |
|---|---|
| Gap #2 closure | Obtain CHED confirmation on classification defaults, materiality and unresolved change-report timing. All three software slices are complete. |
| FIX-03: Personnel | Category 1/2 PIC/APIC readiness checks delivered 12 October; confirm optional ordinary Category 3 qualification scope and production verification. The ordinary Category 3 PIC blocker remains fixed. |
| FIX-04: Review and alerts | Highlight impending activities with unresolved deficiencies and notify authorized CHED staff of filed changes. |
| FIX-05: Complaints | Complete referral, investigation, CHED Committee recommendation, signed decision and HEI dispatch/receipt history. |
| FIX-06: Exceptional clearance | Complete routing/evidence tracking and coordinate the 90-day application with later pre-activity requirements. |
| FIX-07: Regional reports | Freeze monitoring findings/recommendations and associate the correct report with dispatch and OSDS receipt evidence. |
| FIX-08: File integrity | Verify private-file hashes before serving previews/downloads; block altered or missing files. |
| FIX-09: Monitoring feedback | Confirm whether existing corrective responses sufficiently cover HEI participation and feedback. |
| FIX-10: Annex K | Confirm exact-template formatting and acceptance of the added reference line. |
| FIX-11: Annex N | Verify both approved reference pages and clarify their differing step order. |
| Login/MFA proposal | Review the separate shared-office-IP throttling proposal. Plan only; not implemented. |
| Production verification | Run isolated PostgreSQL migrations, concurrency and backup restoration tests, followed by staging/pilot checks. |
| CHED decisions and activation | Resolve publication/effectivity, SAA coverage, Category 3 timelines, RCD anchor and supplementary CLOCA handling before official activation. |

Recommended next coding task after the scoped FIX-03 delivery: **FIX-04**, after separate authorization. Preserve historical versions, receipts, verdicts and signed evidence throughout; do not guess publication details or resolve legal ambiguities silently.
