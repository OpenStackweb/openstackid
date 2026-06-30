# MFA Test Gap Report — PR 142

**Branch:** `feat/mfa---login-ui-flow`  
**Date:** 2026-06-30  
**Scope:** All files changed across the MFA feature branch (backend + frontend)

---

## Summary

PR 142 adds full MFA authentication support: 65 files changed, +6,900 lines. The PHP backend layer has strong coverage — 11 dedicated test files were added as part of the PR. The entire frontend refactor (15 JavaScript/JSX files, ~2,220 lines) has **zero test coverage**, and four specific PHP areas were identified as gaps in isolation-level coverage even though they are exercised indirectly by the integration suite.

| Layer | Files Changed | Files with Tests | Coverage |
|---|---|---|---|
| PHP — services, strategies, repositories | 30 | 30 | ✅ Direct |
| PHP — HTTP / controller layer | 6 | 0 (integration only) | ⚠️ Partial |
| JavaScript — login UI | 15 | 0 | ❌ None |

---

## What IS Covered — PHP Test Files Added in PR 142

The following 11 test files were added or substantially extended as part of this PR. They form the baseline any reviewer can rely on.

### Integration / Feature Tests

| File | Tests | What it covers |
|---|---|---|
| `tests/TwoFactorLoginFlowTest.php` | 19 | Full end-to-end MFA login flow via HTTP: admin/non-admin routing, OTP verify/fail/reuse, recovery codes, device trust cookie enrollment, trusted-device bypass, audit failure resilience, rate-limit enforcement on verify/recovery/resend endpoints |
| `tests/AuthServiceValidateCredentialsIntegrationTest.php` | 2 | `AuthService::validateCredentials` integration path including the MFA gate check |

### Unit Tests

| File | Tests | What it covers |
|---|---|---|
| `tests/unit/AuthServiceValidateCredentialsTest.php` | 9 | Password validation, account state guards, `validateCredentials` under MFA gate (unit) |
| `tests/unit/UserTwoFactorTest.php` | 14 | User entity 2FA flag logic, enforcement rules, group-based enforcement, method availability |
| `tests/Unit/MFAGateServiceTest.php` | 5 | `MFAGateService::requiresChallenge` decision tree for all trust/enforce/cookie combinations |
| `tests/Unit/TwoFactorAuditServiceTest.php` | 7 | Audit event recording: challenge issued, verified, failed, device trusted |
| `tests/DeviceTrustServiceTest.php` | 15 | Full `DeviceTrustService` contract: trust/revoke/expire/validate, SHA-256 storage, audit wiring |
| `tests/Unit/MFA/AbstractMFAChallengeStrategyTest.php` | 8 | Base strategy: OTP generation, expiry, session binding, reuse prevention |
| `tests/Unit/MFA/EmailOTPMFAChallengeStrategyTest.php` | 5 | Email OTP dispatch, already-redeemed race, numeric-only validation |
| `tests/Unit/MFA/MFAChallengeStrategyFactoryTest.php` | 2 | Factory resolves correct strategy for each `MFA_METHODS` value |

### Repository / Model Tests

| File | Tests | What it covers |
|---|---|---|
| `tests/TwoFactorRepositoriesTest.php` | 11 | Doctrine round-trips for `UserTrustedDevice`, `TwoFactorAuditLog`, `UserRecoveryCode`: persistence, expiry/revocation queries, uniqueness constraints, `setCodeHash` guards against plaintext |

---

## Gaps — PHP Backend

These four items lack isolated test coverage. They are exercised indirectly by `TwoFactorLoginFlowTest` but would be invisible to a unit test runner.

### 1. `cancelLogin` Controller Endpoint (Critical)

**File:** `app/Http/Controllers/UserController.php` — `cancelLogin()` action  
**What it does:** Tears down the pending-MFA session state when the user cancels mid-challenge. If this is broken, users can get stuck in an unrecoverable MFA state or, worse, a session may retain stale auth context.  
**Gap:** No unit or dedicated integration test for the `POST /auth/cancel-login` route. The flow test exercises the happy-path continuation but not cancellation edge cases (double-cancel, cancel with no pending session, cancel with concurrent session).

### 2. `TwoFactorRateLimitMiddleware` Isolation (High)

**File:** `app/Http/Middleware/TwoFactorRateLimitMiddleware.php`  
**What it does:** Cache-backed, fixed-window rate limiting for verify/recovery/resend. Counters survive session cleanup. Verify/recovery increment only on failure; resend increments always.  
**Gap:** The middleware is tested indirectly through `TwoFactorLoginFlowTest` (`testVerifyRateLimitBlocksAfterThreshold` etc.), but there are no isolated middleware unit tests covering: window expiry after TTL, per-action counter separation, resend counting regardless of response status, and behavior when no pending session key exists.

### 3. `MFACookieManager` Trait Isolation (Medium)

**File:** `app/Http/Controllers/Traits/MFACookieManager.php`  
**What it does:** Reads the raw device-trust cookie from the request and queues the `Set-Cookie` header. Cookie name, lifetime, and security flags (Secure, HttpOnly, SameSite=lax) are configuration-driven.  
**Gap:** No unit test verifies that `queueDeviceTrustCookie` passes the correct flags to `Cookie::queue`, that the lifetime calculation (`days × 24 × 60`) is right, or that `getCookieToken` returns `null` when no cookie is present. A misconfigured `$secure = true` hardcode already exists in the code and warrants explicit assertion.

### 4. `EncryptCookies` Exclusion (Medium)

**File:** `app/Http/Middleware/EncryptCookies.php`  
**What it does:** Excludes the device-trust token from Laravel's cookie encryption layer so the raw token survives the round-trip.  
**Gap:** No test asserts that `config('two_factor.cookie_name')` is in `$except`, so a future refactor that drops the constructor injection would silently encrypt the cookie and break device trust comparison in `DeviceTrustService` with no test failure.

---

## Gaps — JavaScript Frontend

All 15 frontend files introduced or substantially modified by this PR have no test coverage of any kind.

### File Coverage Table

| File | Lines | Category | Risk | Notes |
|---|---|---|---|---|
| `resources/js/login/login.js` | 1,000 | State machine / orchestrator | **Critical** | Core MFA flow controller: `handleAuthenticatePasswordOk` dispatches to `FLOW.MFA`; `handleMfaError` maps 401/412/429/0 to UI states; `resetToPasswordFlow`; `onVerify2FA`; `onVerifyRecovery`; `onResend2FA` |
| `resources/js/login/components/two_factor_form.js` | 149 | UI Component | **Critical** | Countdown timer with dual `useEffect` (expiry + cooldown), resend cooldown guard, expired-code state, trust-device checkbox |
| `resources/js/login/components/otp_input_form.js` | 117 | UI Component | **High** | OTP entry for email-verification flow; error display, submit guard |
| `resources/js/login/components/password_input_form.js` | 193 | UI Component | **High** | Password entry + show/hide; attempt-count error states; `data-testid` error label |
| `resources/js/login/components/recovery_code_form.js` | 84 | UI Component | **High** | Recovery code entry, empty-submit guard |
| `resources/js/login/actions.js` | 66 | API Layer | **High** | `verify2FA`, `resend2FA`, `verifyRecoveryCode`, `cancelLogin`, `authenticateWithPassword` — all XHR wrappers; URL sourced from `window.*_ENDPOINT` |
| `resources/js/base_actions.js` | 248 | API Layer | **High** | `postRawRequest` / `postRawRequestFull` — XHR transport, redirect-following, `responseURL` extraction; used by every action |
| `resources/js/login/components/email_input_form.js` | 61 | UI Component | **Medium** | Email entry step; `data-testid="error-label"` |
| `resources/js/login/components/email_error_actions.js` | 60 | UI Component | **Medium** | Unknown-email CTA display |
| `resources/js/login/components/existing_account_actions.js` | 47 | UI Component | **Medium** | Account-exists action set |
| `resources/js/login/components/help_links.js` | 78 | UI Component | **Medium** | Context-sensitive help links |
| `resources/js/login/constants.js` | 32 | Constants | **Low** | `FLOW`, `HTTP_CODES`, `MFA_ERROR_CODE` enum values |
| `resources/js/login/components/otp_help_links.js` | 20 | UI Component | **Low** | OTP-specific help link |
| `resources/js/login/components/third_party_identity_providers.js` | 36 | UI Component | **Low** | SSO provider list display |
| `resources/js/shared/HTMLRender.jsx` | 29 | Shared Utility | **Low** | DOMPurify wrapper; `...rest` prop forwarding |

---

## Priority Recommendations

| Priority | Item | Rationale |
|---|---|---|
| **Critical** | Unit tests for `login.js` state machine | `handleAuthenticatePasswordOk`, `handleMfaError`, `handleAuthenticateValidation`, and `resetToPasswordFlow` are pure state logic that can be tested without a DOM. These are the highest-value, lowest-effort tests — each branch covers a real user failure mode. |
| **Critical** | Jest component tests for `TwoFactorForm` | The countdown + cooldown dual-timer is the most complex UI logic in the PR. Timer behavior, expired-code state, and resend-button disabling are invisible in E2E tests but trivially verifiable with `@testing-library/react` + `jest.useFakeTimers`. |
| **Critical** | Dedicated integration test for `cancelLogin` | Covers the session-cleanup contract that is otherwise only exercised by the happy path. |
| **High** | Jest tests for `actions.js` and `base_actions.js` | Mock `window.*_ENDPOINT` and `superagent`; assert that `postRawRequestFull` extracts `responseURL` as `finalUrl`. These are the only XHR-level contracts between React and the PHP backend. |
| **High** | Playwright E2E: full MFA flow | `goes to 2FA step after password → enters code → logs in` and the expired-session regression. The scaffold (`tests/e2e/`) already exists. |
| **High** | `TwoFactorRateLimitMiddleware` unit tests | Isolated cache-mock tests for window expiry and per-action counter separation. |
| **Medium** | `MFACookieManager` unit tests | Assert cookie flag values. |
| **Medium** | Jest component tests: `RecoveryCodeForm`, `PasswordInputForm`, `OTPInputForm` | Error-display and empty-submit guard branches. |
| **Medium** | `EncryptCookies` exclusion assertion | One-line test: `assertContains(config('two_factor.cookie_name'), (new EncryptCookies(...))->getExcept())`. |
| **Low** | `constants.js` smoke test | Not worth dedicated tests; covered by any consumer test that imports the file. |

---

## How to Run What Exists Today

```bash
# PHP — all suites
./vendor/bin/phpunit

# PHP — MFA suite only
./vendor/bin/phpunit --testsuite "Two Factor Authentication Test Suite"

# PHP — integration suite only
./vendor/bin/phpunit tests/TwoFactorLoginFlowTest.php

# JS — unit tests (Jest)
yarn test:unit:ci

# E2E (requires Docker stack)
docker compose --profile e2e run --rm playwright npx playwright test tests/e2e/tests/auth/
```
