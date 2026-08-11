# 0001. Recovery code generation is not automatically triggered by group-based 2FA enforcement

## Status

Accepted — 2026-07-08

## Context

Ticket [CU-86ba2zp66](https://app.clickup.com/t/86ba2zp66) ("Recovery Code Management UI and Endpoints") requires:

> Create endpoint to generate recovery codes **when 2FA is enabled or admin enforcement requires code creation**.

Two-factor authentication in this project can become mandatory for a user in two distinct ways:

1. **Explicit self-enrollment** — the user calls the new `UserApiController::enableTwoFactor()` endpoint
   (`app/Http/Controllers/Api/UserApiController.php`), which invokes `User::enable2FA($method)` and, in the
   same transaction, calls `RecoveryCodeService::generateRecoveryCodes()` to mint the user's first batch of
   recovery codes.
2. **Group-based enforcement** — `User::shouldRequire2FA()` (`app/libs/Auth/Models/User.php`) returns `true`
   for any user belonging to one of the groups listed in `config('two_factor.enforced_groups')`
   (`config/two_factor.php`: SuperAdmin, Admin, OAuth2ServerAdmin, OpenIdServerAdmins), **independently** of
   whether that user ever called `enableTwoFactor()` or has the `two_factor_enabled` column set.

Only path (1) generates recovery codes automatically. Path (2) has no corresponding hook: there is no
listener on group-membership assignment (e.g. when a user is added to the `Admin` group via
`GroupApiController` or any other admin-management flow) that generates a recovery-code batch for that user.

Practical consequence: a user who is force-enrolled into MFA purely by being added to an enforced group —
and who never separately visits their profile to enable 2FA or regenerate codes — has **zero recovery codes**
until they proactively open their profile's "Two-Factor Authentication" section and click "Regenerate Codes"
(`resources/js/components/recovery_codes_panel.js`). That manual path works correctly and is not gated on
prior enrollment, but it is not automatic, so the literal wording of the ticket ("or admin enforcement
requires code creation") is not satisfied for this path.

Implementing the automatic path would require identifying every place group membership can be granted
(direct admin action, bulk import, programmatic group assignment, etc.) and wiring a listener/hook into each
one to call `RecoveryCodeService::generateRecoveryCodes()` exactly once per user, without duplicating codes
on repeated grants or interfering with a user who already regenerated codes themselves. No single
well-defined integration point for "group membership changed" was identified during implementation without
a dedicated exploration pass, and building one was judged to be a meaningfully larger change than the rest
of this ticket.

## Decision

We accept the gap as a documented scope limitation for this ticket. Recovery code generation for
group-enforced users remains **on-demand**: the user (or an admin acting on their behalf, e.g. via a support
flow) must visit the profile's Two-Factor Authentication section and use "Regenerate Codes" at least once
after being enrolled through group enforcement.

No code changes accompany this decision; it documents the trade-off already present in the shipped
implementation (`feat/recovery-codes-management` branch).

## Consequences

**Positive**

- No new event/listener infrastructure needed for group-membership changes, keeping the change surface of
  this ticket limited to the profile self-service flow it was originally scoped around.
- The manual path is simple, already implemented, and requires no additional user-facing concept: a
  group-enforced user sees the same "Two-Factor Authentication" section and the same "Regenerate Codes"
  action as anyone who self-enrolled.
- Avoids the risk of generating recovery codes an admin never asked for or expects during unrelated
  group-management operations (e.g. bulk group imports).

**Negative**

- A user who is force-enrolled by group membership and is challenged for MFA (e.g. at their next login)
  before ever visiting their profile has **no recovery codes available** if they lose access to their normal
  2FA method (email, for Phase I) at that point. Their only recourse is out-of-band administrative
  intervention (e.g. a server admin resetting their 2FA state directly), not a self-service recovery path.
- The literal acceptance criterion "generate recovery codes ... when admin enforcement requires code
  creation" is not met for the group-enforcement path — only for explicit self-enrollment.

**Follow-up (not scheduled)**

If this gap needs to be closed later, the natural integration point is wherever group membership is granted
(`GroupApiController` and any other code path that adds a user to `Group`) — call
`RecoveryCodeService::generateRecoveryCodes($user)` immediately after granting membership to a group in
`config('two_factor.enforced_groups')`, guarded so it only fires when the user currently has zero unused
codes (to avoid clobbering codes on every re-grant).

## Alternatives considered

- **Hook into group-assignment code paths now.** Rejected for this ticket: requires auditing every place
  group membership can change (there is more than one — see `GroupApiController` and related admin flows)
  to guarantee the hook fires exactly once and doesn't silently invalidate codes a user already saved. Judged
  to be new scope beyond "Recovery Code Management UI and Endpoints," better handled as its own ticket if
  the org decides to close this gap.
- **Generate codes lazily on first MFA challenge instead of on group grant.** Rejected: the MFA challenge
  screen itself has no natural place to show a one-time "here are your recovery codes" modal without
  interrupting the login flow the ticket explicitly requires to remain "unaffected."
