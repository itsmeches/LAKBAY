# Open CHED questions

## Gap #2 operational interpretations — still pending

CMO No. 24 §13.B.3, PDF p.6; §13.C.5, PDF p.7; §16, PDF p.12; Annex I, PDF p.29: confirm the approved materiality defaults, restoration of Category 1/ordinary Category 3 after a reversion, consolidated versus per-event RCDs, original versus latest revised receipt selection, and treatment of amendments versus old-policy corrections. These are explicit configuration/context and history decisions, not silent exemptions. No numerical/“minor change” threshold is implemented. The existing RCD formal anchor question below remains unresolved.

## RCD 72-hour anchor — unresolved, gap #2

CMO No. 24 §16 and §14 row 22, PDF p.12; §24, PDF p.13; Annex I, PDF p.29: deviations require immediate reporting, and the formal RCD is due not later than 72 hours “upon conduct”. Confirm commencement, conclusion or another event as the legal anchor. Until confirmed, store `rcd_anchor=unresolved`, retain immediate notice and show no calculated authoritative formal due date/on-time verdict. This is not `changed_at + 72 hours`. Section 22 (PDF p.13) concerns HEI discretion, not this deadline. Future confirmed rules must be prospective and versioned.

## SAA term coverage — pending CHED confirmation

Working interpretation for gap #1: a valid SAA is per HEI per term, not per activity. Category 1/2 and Category 3 receiving Category 2 requirements can reference the same existing signed original covering that term.

Confirm semester/term coverage, whether one affidavit covers all applicable activities and programs/campuses of the HEI, how activities spanning terms are handled, and acceptable receipt/signing/notarization and coverage evidence. Annex C identifies semester and academic year; the owner's Section 39 reference does not by itself establish an independently verified per-term rule.

The app supports reuse of clean institutional evidence and coverage metadata, but does not automatically certify term coverage or notarization. Do not introduce guessed validity dates or require a new SAA for each escalated activity.

Ordinary Category 3 SAA applicability is resolved for this implementation: Section 14 row 10 marks only Categories 1–2. Do not carry ordinary Category 3 SAA timing as an unresolved requirement. Other operational questions remain in [CHED-clarifications.md](CHED-clarifications.md); they are outside this task.
