# Supervisor presentation package

Prepared 12 October 2026 from application commit `741f29d`. No FIX-06, application change, policy activation or data migration is included.

**Start here:** [Executive summary](01-executive-summary.md), [HTML slide deck](exports/slides.html), [printable slide deck](exports/slides.pdf), [PDF report](exports/supervisor-report.pdf), [demo checklist](11-live-demo-checklist.md).

The report PDF is a meeting-length curated briefing, not a reproduction of every schema column/Mermaid source. The complete technical appendix is in the Markdown documents. The slide deck is editable standalone HTML with keyboard controls (←/→, Home/End); print/export uses one slide per page. No PowerPoint package/renderer was available, so no PPTX or all-diagram image export is claimed. No dependencies were installed.

## Documents

- [01-executive-summary.md](01-executive-summary.md)
- [02-system-architecture.md](02-system-architecture.md)
- [03-system-diagrams.md](03-system-diagrams.md)
- [04-user-roles-and-workflows.md](04-user-roles-and-workflows.md)
- [05-database-and-data-flow.md](05-database-and-data-flow.md)
- [06-feature-progress-matrix.md](06-feature-progress-matrix.md)
- [07-security-and-testing-status.md](07-security-and-testing-status.md)
- [08-presentation-outline.md](08-presentation-outline.md)
- [09-presentation-script.md](09-presentation-script.md)
- [10-supervisor-qa-preparation.md](10-supervisor-qa-preparation.md)
- [11-live-demo-checklist.md](11-live-demo-checklist.md)
- [12-development-roadmap.md](12-development-roadmap.md)
- [audit-evidence.md](audit-evidence.md)

## Safe backup visuals

`exports/annex-B-example.png` and `annex-M-example.png` are first-page renders from verified fictional QA PDF outputs, visibly labelled as QA preparations/records. They are not signed evidence. These are selected sanitized illustrations, not copies of auth manifests or private upload libraries.

Read [security/testing status](07-security-and-testing-status.md) before claiming readiness. The retained latest suite passed 497 PHPUnit / 38 browser cases, but PostgreSQL and production remain unverified; the normal DB has three pending migrations. CMO 24 remains a candidate.

## Package verification

Final exports: 18-page report PDF and 12-page slide PDF, with no empty pages. Offline deck Next/Home/End navigation passed in installed Chromium. All local Markdown file links resolve; there are nine separate role flowcharts and 22 original Mermaid sources plus ten matching simplified presentation sources. Export SHA-256 values are recorded in `export-manifest.json` and matched the final files. Source/normal record-group checksums remain unchanged. No full application suite was rerun for this documentation-only task; the application results above are the retained FIX-05 campaign.

The original briefing package contains 44 files; the added flowchart gallery contains ten PNGs, ten editable SVGs, ten matching Mermaid sources, handoff/speaker notes and navigation files. The package includes only documentation, editable diagrams, HTML/PDF exports, an evidence-hash manifest and two labelled fictional QA illustrations. No account/session manifest, credentials, live institution records or signed-upload originals are included. No application functionality, dependency/configuration or existing record was changed; FIX-06 remains pending.

## Added workflow pictures

[Open the gallery](flowcharts/gallery.html) for the whole-system chart and nine role charts. [Download the image bundle](exports/flowchart-images.zip) to insert PNGs into slides. Editable SVGs and Mermaid sources are included. [Handoff summary and presentation explanations](flowcharts/presentation-notes.md) accompany the simplified 6–9 step journeys. The report PDF and original slide deck retain their earlier technical diagrams; use these replacement PNGs for the simpler presentation. No application behavior or policy was changed.
