# 0002. Recovery codes are enrolled at the next 2FA challenge and counted only once acknowledged

## Status

Accepted — 2026-11-05. Supersedes [0001](0001-recovery-code-generation-not-triggered-by-group-enforcement.md).

## Context

[0001](0001-recovery-code-generation-not-triggered-by-group-enforcement.md) accepted that a user who is
force-enrolled in 2FA by group membership gets recovery codes only if they open the profile and use
"Regenerate Codes". Two problems followed from it, and a third one was found while closing them:

1. **The gap of 0001.** A group-enforced admin who is challenged at sign-in before ever visiting the profile
   has no recovery codes, and nothing in the sign-in flow prompts them to get a set. For an account without a
   password (social-only) the profile path does not even exist: `Regenerate Codes` needs the current password.
2. **The unseen batch.** The profile counts unused rows of `user_recovery_codes`. The system cannot tell
   whether the user ever saved the batch it displayed, so a batch the user closed without saving still reads
   "10 of 10 remaining". Any design that generates codes for a user who is not looking at the screen (the
   Rev 2.4 form of `idp:enforce-admin-2fa`) produces codes nobody can ever see.
3. **Passwordless generation.** `POST 2fa/enable` rejected a user only when the stored `two_factor_enabled`
   flag was true, so any authenticated session of a group-enforced admin could mint a fresh batch without the
   password, and left the flag set after the user left the group.

## Decision

A batch counts as usable only after the user confirms it, and a user who requires 2FA but has no usable batch
is challenged at every sign-in until they confirm one. The design is specified in the SDS
(`ftn-docsnsklz` `sds/idp-mfa.md` rev 2.5 and rev 2.6) and implemented as follows.

**Acknowledgment.** `users.recovery_codes_acknowledged_at` is set when the user confirms the displayed batch
with "I've Saved These Codes" and cleared in the same transaction as every generation. The confirmation is
bound to the exact batch: the identifier is `MIN(user_recovery_codes.id)` of the user's rows, produced inside
the locked transaction that generates the batch and returned with the codes (`RecoveryCodesBatch`), never read
after the commit. `POST recovery-codes/acknowledge` answers 412 when the identifier is not the current batch.
A user *requires enrollment* when `shouldRequire2FA()` and (no unused codes or `recovery_codes_acknowledged_at`
is NULL).

**Enrollment at the next challenge.** After a successful `verify2FA` / `verify2FARecovery` the JSON response
carries `recovery_codes_enrollment_required`. The login page calls `POST recovery-codes/enroll`, shows the
non-dismissable "Save Your Recovery Codes" modal, and navigates to `redirect_url` only after the batch is
acknowledged. An enrollment failure never blocks the sign-in: enrollment stays pending and the next sign-in is
challenged again. The profile offers the same modal through an explicit "Set up recovery codes" action, never
on page load, because every call replaces the batch.

**Trusted devices do not skip the challenge while enrollment is required.** `MFAGateService::requiresChallenge()`
checks enrollment before device trust. This is the only way to guarantee the modal is reachable for accounts
without a password, and it stops as soon as one batch is acknowledged.

**Passwordless generation only right after a real challenge.** `POST recovery-codes/enroll` (and `regenerate`
for a user without a password) is accepted only inside `two_factor.recovery_enrollment_window` seconds of
`MFAConstants::SESSION_KEY_COMPLETED_AT`, written solely by `verify2FA` and `verify2FARecovery` after the
session is established and bound to the user id. The principal's `auth_time` is not used: every sign-in sets
it, trusted-device sign-ins included. `enroll` is rate limited per authenticated user (`2fa.rate:enroll`,
5 per 15 minutes); the limiter cannot be keyed by the pending-challenge user because `verify2FA` clears that
state before the page calls enroll. `POST 2fa/enable` answers 412 for a user who already requires 2FA.

**Serialized writes.** Every path that writes or deletes a user's recovery codes (generation, acknowledgment,
redemption, and later disabling 2FA / `idp:reset-2fa`) takes a `PESSIMISTIC_WRITE` lock on the `users` row
first and only then touches `user_recovery_codes`; one lock order everywhere rules out deadlocks. The lock
re-hydrates the user entity (`IUserRecoveryCodeRepository::lockUserRow()`), since a lock that leaves stale
in-memory state would let an acknowledgment decide on an outdated column. Without it two concurrent
generations under `READ_COMMITTED` leave two live batches.

**CSRF token after sign-in.** `Auth::login()` regenerates the session id *and* the CSRF token
(`SessionGuard::updateSession()` calls `Session::regenerate(true)`), so the token the login page was rendered
with is stale once `verify2FA` succeeds. The verify responses therefore carry the current `csrf_token`, and
the page sends it with the enroll and acknowledge calls. `Authenticate` also stops redirecting JSON requests
to the session `backurl`, which would otherwise answer the first XHR after a deep-link sign-in with an HTML
page.

## Consequences

**Positive**

- Every user who requires 2FA ends up with a batch they confirmed seeing, including admins who never open
  their profile and accounts without a password.
- The profile no longer reports "10 of 10 remaining" for a batch nobody saved.
- No passwordless path to new codes remains for users who already require 2FA, outside a short window after a
  real challenge.
- No group-membership hook is needed: the trigger is the sign-in itself.

**Negative**

- A user who keeps ignoring the modal receives an email OTP at every sign-in, trusted device or not, until
  they acknowledge a batch.
- Closing the tab before acknowledging replaces the batch at the next sign-in: codes that were displayed but
  never confirmed stop verifying.
- Acknowledgment is a user assertion: the server cannot prove the codes were saved, only that the user
  confirmed the batch it generated.
- The enrollment window leaves a passwordless regeneration path open for a few minutes after each challenge
  for users without a password.
- Recovery-code redemption holds the `users` row lock while it checks the unused codes (up to 10 password
  hashes).

## SDS references

`sds/idp-mfa.md` rev 2.5 (ftn-docsnsklz PR #142): §3.1, §3.4, §3.5, §3.6, §4.10.1, §4.10.3, §4.11, §4.13,
§4.14. Rev 2.6 (ftn-docsnsklz PR #179): §3.5, §4.2, §4.10.1, §4.10.3, §4.12, §4.13, §4.14, §5.6.
