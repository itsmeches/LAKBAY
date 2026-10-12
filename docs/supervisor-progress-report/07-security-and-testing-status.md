# Security and verification evidence

> Evidence snapshot: 12 October 2026; application commit `741f29d`. Documentation-only audit; no FIX-06 implementation, policy activation, data mutation or new dependency.

## Controls and practical limits

| Area | Implemented control | Qualification / evidence |
|---|---|---|
| Identity | Invitation token stored hashed, three-day expiry, one-time transactional acceptance; password minimum 12 at acceptance | `InviteUser::execute`; `InvitationController::accept`; invitation acceptance is used as email-possession verification, not separate accreditation validation |
| MFA / secrets | Required authenticator MFA/recovery on both panels; encrypted MFA fields; hashed passwords; hidden credential attributes | panel providers; `User::casts`; production key/recovery handling still needs ICT procedure |
| Web sessions | Laravel session/cookie and CSRF middleware; Filament login performs MFA; output routes require authentication and an active, verified MFA-enrolled account | providers; `EnsureMfaEnabled`; `routes/web.php`; the output middleware checks enrollment, not a fresh challenge timestamp |
| Access | Permission plus verified ownership/authorized membership, CHED jurisdiction and confidentiality | `Access`; `InstitutionRecordPolicy`; shared Actions/controllers/jobs; admin has no automatic case access |
| Input/transactions | Laravel validation, record locks, state guards and selected unique revision/draft/replay links | `SubmitActivity`, `SubmitReport`, `RecordCaseEvent`; failure coverage; not all operations proven under concurrent PostgreSQL load |
| Files | Private disk, MIME/limit checks, signed official PDF, pending/infected quarantine, genuine scanner and scan-time hash | `UploadDocument`; `ScanDocument`; retrieval in `DocumentController`; fresh generic retrieval hash is FIX-08 |
| PDF / output | Protected generated forms; escaped Blade; renderer restrictions on annex routes | print controllers/builders; template tests; generated output is unsigned preparation |
| Export | Recheck requesting user/region, private expiry-based retrieval, CSV formula escape | `GenerateExport::handle`; `ExportController`; snapshot/narrative limits remain |
| Audit/history | Immutable versions/events and preserved submitted payloads; access/transition audit entries | model traits; `Audit`; preservation tests; not independent tamper-proof/WORM storage |
| Notifications | Recipient access rechecked; successful delivery ledger; local capture in QA | notification jobs; no exactly-once guarantee after external transport crash |

Do not publish `.env`, account manifests, session JSON, invitation links, logs, private uploads or backups. Demo artifacts are ignored and contain fictional data, but may still include auth/session material. Only selected visual previews should be shared after inspection.

**Additional static qualification:** `EnsureMfaEnabled::handle` checks the stored authenticator secret, active account and verified email. It does not itself verify a recent MFA challenge for every direct output request. Filament provides the normal login challenge; challenge/session freshness across every possible entry point has not been independently established by this report. Review that boundary before claiming universal step-up authentication. This is a documented review question, not a demonstrated bypass or an application change.

## Last completed campaign, not a new rerun

Application snapshot `741f29d`; evidence from FIX-05 on 12 October 2026:

| Check | Actual retained result | Scope |
|---|---|---|
| Full PHPUnit | 497 passed; 2,385 assertions; 364,963 ms | SQLite/test fixtures; selected mocks do not establish genuine scans |
| Playwright Chromium | 38 passed (6.4m) | copied QA database/private files; desktop/narrow checks; actual local scan/retry |
| Focused new case browser | 1 passed (50.0s) | form save/history/admin denial |
| Pint | passed | changed PHP formatting |
| PHPStan | passed, zero errors | configured static analysis |
| PHP syntax / JS syntax | 388 PHP files, zero errors; JS exit 0 | source syntax |
| Vite build | exit 0, built in 2.83s | asset compilation; existing optional-font warning |
| Preservation | original receipt/verdict/case/audit rows unchanged; 36 registered files unchanged | disposable QA comparisons; unsigned Annex F draft intentionally edited |
| SQLite scratch restore | integrity ok; 66 tables, zero count mismatches | local database recovery only |
| PostgreSQL / Docker | not executed locally | missing executable tooling; CI configuration exists but no CI success claimed |
| Production mail/hosting/load/recovery | unverified | no deployment performed |

Evidence paths: `storage/app/fix05/phpunit.log`, `phpunit.xml`, `browser-final.log`, `browser-final-focused.log`, `preservation.json`, `restore-result.json`, `build.log`. These are ignored local artifacts, not uploaded appendices. The audited report `docs/fix-backlog.md` preserves outputs and caveats. This documentation task read the results; it did not rerun or modify test/application behavior.

The two initial new browser failures were test selectors/session switching, corrected and rerun. Production throttles were not loosened. Former upload/PDF configuration failures have successful local regression evidence, but a different machine still needs writable PHP temporary directories, scanner signatures and workers.

## Current environment mismatch

Read-only `artisan migrate:status` on the normal database lists three pending migrations: classification rule sets/assessments (8 October), APIC basic knowledge (12 October), and case procedural metadata (12 October). Normal application code is therefore newer than its normal schema. **Do not run the meeting demo there without an approved, backed-up migration process.** The FIX-05 QA copy has all three and is the source of schema/visual evidence here. No migrations or seeds were applied by this report task.

## Production release gates

Verified publication and approved policy interpretations; staged PostgreSQL migrations/concurrency/restore; role/DPO review; approved retention/legal holds; HTTPS/keys/proxy; official mail/invitations/MFA recovery; scanner updates/worker/scheduler observability; remaining high-priority fixes; HEI/CHED pilot sign-off. Passing tests supports the baseline but does not remove these gates.
