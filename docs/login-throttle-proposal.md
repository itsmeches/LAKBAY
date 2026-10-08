# Login/MFA throttle proposal — not implemented

Separate from gap #2. No application, vendor, test-isolation or throttle configuration changes are authorized by this proposal.

Current Filament defaults: five authenticate calls per 60 seconds keyed by component/method/IP, shared across HEI and CHED. Password and MFA submissions both consume that limit. MFA additionally allows five challenge calls per 60 seconds keyed by guard/user. A shared office can exhaust the IP limit even with correct credentials.

Propose a project Login class used by both panels, retaining mandatory MFA and generic credential errors:

- Credential attempts: normalized email + IP + guard, **5 per 60 seconds**. Normalize consistently with actual account lookup; hash identifiers in cache keys. Count failed credentials, clear the narrow credential counter on success; avoid charging the password limiter again for the MFA step.
- Additional IP ceiling: **60 credential attempts per 60 seconds**, counted whether successful or failed. This is a proposed configurable starting value, not an approved number. Size it against actual office concurrency and public-IP/proxy topology before adoption. Both panels share protection to prevent hopping panels to evade limits.
- MFA: **5 challenges per 60 seconds per guard/user**, retained; never key solely by IP. Preserve recovery-code validation/replay protection. Decide whether to clear the counter only after fully successful MFA.
- Temporary lockout: display remaining TTL, generic messages; no permanent account disabling or email-triggered whole-account lockout.

Risks: a higher IP ceiling enables broader password spraying; distributed attackers evade per-IP limits; a user can be targeted for temporary denial of service; faulty proxy trust collapses all users into one IP or enables spoofing. Use existing trusted proxy configuration only after infrastructure validation. Do not log passwords, OTPs or full identifier cache keys. Consider monitoring and progressive backoff before adding CAPTCHA or dependencies.

Tests after approval: multiple valid HEI/CHED accounts sharing one IP can log in below the ceiling; one bad account/IP reaches its narrow limit without blocking neighbors; broad spraying reaches the IP ceiling; exact TTL boundaries; normalization aliases; distinct IPs; cross-panel limiter consistency; MFA bad-code/recovery/replay limits; successful MFA/session regeneration; inactive/unverified users; proxy header spoofing; concurrent attempts and generic responses. Browser fixtures must run in scratch cache/session namespaces without disabling or resetting the limiter during throttle tests.

Adoption requires an approved configuration/test diff, measured thresholds and full PHPUnit/browser/static-analysis verification. This proposal is not part of Slice 1.
