# 0002. `idp:enforce-admin-2fa` notifies enforced users; it does not generate recovery codes

## Status

Accepted — 2026-11-05. Complements [ADR 0001](0001-recovery-code-generation-not-triggered-by-group-enforcement.md),
which remains in force.

## Context

Group-based 2FA enforcement is derived at runtime from group membership (`User::shouldRequire2FA()`,
`config('two_factor.enforced_groups')`). Rolling it out to existing admins needs a one-off operator step that
tells them 2FA is now required.

An earlier shape of the command also minted a recovery-code batch per user. That would have contradicted
ADR 0001 (recovery codes for group-enforced users stay on-demand) and had a delivery bug: the codes were
committed before the email was sent, so a transient SMTP or audit failure left the user "skipped" on every
later run (they already had unused codes) and never notified.

## Decision

`php artisan idp:enforce-admin-2fa [--dry-run]`:

- **Only notifies.** It sends `TwoFactorEnforcedMail` to every user of the enforced groups. It never generates
  recovery codes and never touches `two_factor_enabled`, `two_factor_method` or `two_factor_enforced_at`.
  Users get their codes from the profile ("Enable Two-Factor Authentication" generates the first batch,
  "Regenerate Codes" replaces it), as stated in ADR 0001. The mail links to the profile and carries no codes.
- **The audit row is the "already notified" marker.** After `Mail::send` succeeds it writes an
  `enrollment_changed` event with metadata `source=enforce-admin-2fa`. Users with such a row are skipped.
  Because the row is written only after the mail was sent, a re-run retries exactly the users whose mail failed.
  Delivery is at-least-once: if the audit write fails after the mail was sent, the user is mailed again.
- **Refuses to run when `two_factor.enabled` is false** (`TWO_FACTOR_ENABLED`), also with `--dry-run`: with the
  global kill-switch off `shouldRequire2FA()` is false for everyone, so telling admins 2FA is required would be
  wrong. The command warns and exits with failure.

## Consequences

- The command is safe to re-run; `--dry-run` writes and sends nothing.
- A group-enforced user who is challenged for MFA before visiting their profile still has no recovery codes
  (the negative consequence recorded in ADR 0001 is unchanged). The mail is what steers them to the profile.
- Operators must enable `TWO_FACTOR_ENABLED` before running the command.
