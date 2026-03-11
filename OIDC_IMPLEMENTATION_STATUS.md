# OpenStackID - OpenID Connect Implementation Status

This document details the OpenID Connect and OAuth 2.0 specifications implemented by the OpenStackID Identity Provider, including what is fully implemented, partially implemented, and missing.

---

## Table of Contents

1. [OpenID Connect Core 1.0](#1-openid-connect-core-10)
2. [OAuth 2.0 Multiple Response Types 1.0](#2-oauth-20-multiple-response-types-10)
3. [OAuth 2.0 Form Post Response Mode 1.0](#3-oauth-20-form-post-response-mode-10)
4. [OpenID Connect Discovery 1.0](#4-openid-connect-discovery-10)
5. [OpenID Connect Session Management 1.0](#5-openid-connect-session-management-10)
6. [OpenID Connect RP-Initiated Logout 1.0](#6-openid-connect-rp-initiated-logout-10)
7. [Front-Channel Logout / Back-Channel Logout](#7-front-channel-logout--back-channel-logout)
8. [Additional RFCs](#8-additional-rfcs)
9. [Summary Matrix](#9-summary-matrix)

---

## 1. OpenID Connect Core 1.0

**Spec:** https://openid.net/specs/openid-connect-core-1_0.html

### Authentication Flows (Section 3)

| Flow | Status | Implementation |
|------|--------|----------------|
| Authorization Code | Implemented | `AuthorizationCodeGrantType.php` |
| Implicit | Implemented | `ImplicitGrantType.php` |
| Hybrid | Implemented | `HybridGrantType.php` |

### Endpoints

| Endpoint | Status | Route / File |
|----------|--------|--------------|
| Authorization Endpoint | Implemented | `AuthorizationEndpoint.php` |
| Token Endpoint | Implemented | `TokenEndpoint.php` |
| UserInfo Endpoint | Implemented | `OAuth2UserApiController`, `IUserService` |

### ID Token (Section 2)

Fully implemented with signing and encryption support.

**Signing algorithms:** HS256, HS384, HS512, RS256, RS384, RS512, PS256, PS384, PS512

**Encryption algorithms (alg):** RSA1_5, RSA-OAEP, RSA-OAEP-256, Dir

**Encryption algorithms (enc):** A128CBC-HS256, A192CBC-HS384, A256CBC-HS512

### Standard Claims (Section 5.1)

All standard OIDC claims are implemented in `app/libs/OAuth2/StandardClaims.php`:

| Claim | Status |
|-------|--------|
| `sub` | Implemented |
| `name`, `given_name`, `family_name`, `middle_name` | Implemented |
| `nickname`, `preferred_username` | Implemented |
| `profile`, `picture`, `website` | Implemented |
| `email`, `email_verified` | Implemented |
| `gender`, `birthdate` | Implemented |
| `zoneinfo`, `locale` | Implemented |
| `phone_number`, `phone_number_verified` | Implemented |
| `address` | Implemented |
| `updated_at` | Implemented |

**Custom claims (beyond spec):** `groups`, `bio`, `statement_of_interest`, `second_email`, `third_email`, `language`, `irc`, `linked_in_profile`, `twitter_name`, `github_user`, `wechat_user`, `company`, `job_title`

### Scopes (Section 5.4)

| Scope | Status |
|-------|--------|
| `openid` | Implemented |
| `profile` | Implemented |
| `email` | Implemented |
| `address` | Implemented |
| `phone` | Not advertised in discovery |
| `offline_access` | Not advertised in discovery |

### Subject Identifier Types (Section 8)

| Type | Status |
|------|--------|
| `public` | Implemented |
| `pairwise` | Implemented |

### Client Authentication (Section 9)

| Method | Status |
|--------|--------|
| `client_secret_basic` | Implemented |
| `client_secret_post` | Implemented |
| `private_key_jwt` | Implemented |
| `client_secret_jwt` | Implemented |

### Not Implemented / Not Confirmed

- **Request Object** (`request` / `request_uri` parameters, Section 6) - Not confirmed present
- **Dynamic Client Registration** (openid-connect-registration-1_0) - Clients are registered through admin UI/API, not via the OIDC dynamic registration endpoint
- **Self-Issued OpenID Provider** (Section 7) - Not implemented
- **Claims request parameter** (Section 5.5) - Not implemented

---

## 2. OAuth 2.0 Multiple Response Types 1.0

**Spec:** https://openid.net/specs/oauth-v2-multiple-response-types-1_0.html

### Response Types (Sections 3-5)

All defined response types are implemented in `OAuth2Protocol.php`:

| Response Type | Flow | Status |
|---------------|------|--------|
| `code` | Authorization Code | Implemented |
| `token` | Implicit (OAuth2) | Implemented |
| `id_token` | Implicit (OIDC) | Implemented |
| `id_token token` | Implicit (OIDC) | Implemented |
| `code id_token` | Hybrid | Implemented |
| `code token` | Hybrid | Implemented |
| `code id_token token` | Hybrid | Implemented |
| `none` | - | Defined as constant but not routed through grant type flows |

### Response Modes (Section 2.1)

| Mode | Status | Advertised in Discovery |
|------|--------|------------------------|
| `query` | Implemented | Yes |
| `fragment` | Implemented | Yes |
| `form_post` | Implemented (see Section 3) | Yes |
| `direct` | Implemented (custom extension) | No |

### Default Response Mode Mapping (Section 2.1)

Implemented in `OAuth2Protocol::getDefaultResponseMode()`:

- `code` alone -> `query` (per spec)
- `token` alone -> `fragment` (per spec)
- All multi-value combinations -> `fragment` (per Section 5)

### Response Classes

| Class | Purpose |
|-------|---------|
| `OAuth2AccessTokenFragmentResponse` | `token` (fragment encoding) |
| `OAuth2IDTokenFragmentResponse` | `id_token` / `token id_token` (fragment encoding) |
| `OAuth2HybridTokenFragmentResponse` | Hybrid combinations with `code` in fragment |
| `OAuth2AccessTokenResponseFactory` | Code exchange, branching on hybrid vs standard OIDC |

---

## 3. OAuth 2.0 Form Post Response Mode 1.0

**Spec:** https://openid.net/specs/oauth-v2-form-post-response-mode-1_0.html

**Status:** Fully implemented.

### Implementation

| Component | File | Purpose |
|-----------|------|---------|
| Response object | `OAuth2PostResponse.php` | Renders auto-submitting HTML form with hidden inputs |
| HTTP strategy | `PostResponseStrategy.php` | Converts to Laravel response with cache-prevention headers |
| Routing | `OAuth2ResponseStrategyFactoryMethod.php:59-61` | Routes `response_mode=form_post` to form post strategy |
| Registration | `StrategyProvider.php:43` | Registered in DI container |

**Spec compliance details:**

- Returns HTTP 200 (per Section 4: "A server receiving a valid request MUST send a response with an HTTP status code of 200")
- `Content-Type: text/html`
- Auto-submitting `<form method="post">` with hidden `<input>` fields for each response parameter
- Cache-prevention headers: `no-cache, no-store, must-revalidate`, `Pragma: no-cache`

---

## 4. OpenID Connect Discovery 1.0

**Spec:** https://openid.net/specs/openid-connect-discovery-1_0.html

### Provider Configuration Endpoint (Section 4)

**Status:** Implemented.

**Endpoint:** `GET /.well-known/openid-configuration` at two paths:
- `/.well-known/openid-configuration` (`routes/web.php:104`)
- `/oauth2/.well-known/openid-configuration` (`routes/web.php:119`)

Handled by `OAuth2ProviderController::discovery()`, returns JSON. Document built by `OAuth2Protocol::getDiscoveryDocument()` using `DiscoveryDocumentBuilder`.

### Provider Metadata (Section 3)

#### REQUIRED Fields

| Field | Status |
|-------|--------|
| `issuer` | Included |
| `authorization_endpoint` | Included |
| `token_endpoint` | Included |
| `jwks_uri` | Included (served at `/oauth2/certs`) |
| `response_types_supported` | Included (`code`, `token`, `code token`, `token id_token`, `code token id_token`) |
| `subject_types_supported` | Included (`public`, `pairwise`) |
| `id_token_signing_alg_values_supported` | Included (HS256/384/512, RS256/384/512, PS256/384/512) |

#### RECOMMENDED Fields

| Field | Status |
|-------|--------|
| `userinfo_endpoint` | Included |
| `scopes_supported` | Included (`openid`, `address`, `email`, `profile`) |
| `claims_supported` | Included (JWT + standard OIDC claims) |

#### OPTIONAL Fields

| Field | Status |
|-------|--------|
| `response_modes_supported` | Included (`form_post`, `query`, `fragment`) |
| `token_endpoint_auth_methods_supported` | Included (`client_secret_basic`, `client_secret_post`, `private_key_jwt`, `client_secret_jwt`) |
| `id_token_encryption_alg_values_supported` | Included (RSA1_5, RSA-OAEP, RSA-OAEP-256, Dir) |
| `id_token_encryption_enc_values_supported` | Included (A128CBC-HS256, A192CBC-HS384, A256CBC-HS512) |
| `userinfo_signing_alg_values_supported` | Included |
| `userinfo_encryption_alg_values_supported` | Included |
| `userinfo_encryption_enc_values_supported` | Included |
| `display_values_supported` | Included (`page`, `popup`, `touch`, `wap`, `native`) |
| `end_session_endpoint` | Included (Session Management extension) |
| `check_session_iframe` | Included (Session Management extension) |
| `revocation_endpoint` | Included (RFC 7009 extension) |
| `introspection_endpoint` | Included (RFC 7662 extension) |
| `registration_endpoint` | **Not included** |
| `grant_types_supported` | **Not included** |
| `acr_values_supported` | **Not included** |
| `claims_locales_supported` | **Not included** |
| `ui_locales_supported` | **Not included** |
| `claims_parameter_supported` | **Not included** |
| `request_parameter_supported` | **Not included** |
| `request_uri_parameter_supported` | **Not included** |
| `require_request_uri_registration` | **Not included** |
| `service_documentation` | **Not included** |
| `op_policy_uri` | **Not included** |
| `op_tos_uri` | **Not included** |

Note: The `OpenIDProviderMetadata` class defines constants for all optional fields above, but the `DiscoveryDocumentBuilder` does not populate them.

#### Custom (Non-Spec) Fields

| Field | Purpose |
|-------|---------|
| `third_party_identity_providers` | Available social login providers |
| `allows_native_auth` | Whether native authentication is enabled |
| `allows_otp_auth` | Whether OTP authentication is enabled |

### WebFinger / Issuer Discovery (Section 2)

**Status:** Not implemented. No `/.well-known/webfinger` endpoint exists. Only the Provider Configuration endpoint (Section 4) is implemented.

---

## 5. OpenID Connect Session Management 1.0

**Spec:** https://openid.net/specs/openid-connect-session-1_0.html

**Status:** Fully implemented.

### Creating/Updating Sessions (Section 4.1)

`session_state` is computed in `InteractiveGrantType::getSessionState()` per spec:

```
salt    = random 16 bytes (hex)
message = client_id + origin + opbs + salt
hash    = SHA-256(message)
session_state = hash + "." + salt
```

Called from all three interactive grant types (each referencing the spec):
- `AuthorizationCodeGrantType` at line 380-381
- `ImplicitGrantType` at line 193-194
- `HybridGrantType` at line 171-172

The `session_state` is tied to both user and client:
- **Different client** -> different `client_id` + `origin` -> different `session_state`
- **Different user** (or logout) -> different session ID -> different `opbs` -> different `session_state`

### OP iframe / Check Session Endpoint (Section 4.2)

**Endpoint:** `GET /oauth2/check-session` -> `OAuth2ProviderController@checkSessionIFrame`

**View:** `resources/views/oauth2/session/check-session.blade.php` loads CryptoJS (SHA-256), jQuery Cookie, and `check.session.js`.

**OP iframe logic** (`public/assets/js/oauth2/session/check.session.js`):

1. Listens for `postMessage` events from the RP iframe
2. Rejects same-origin messages
3. Parses the message as `"client_id session_state"`
4. Splits `session_state` into `hash.salt`
5. Reads the `op_bs` cookie (OP Browser State)
6. Recomputes `SHA-256(client_id + origin + opbs + salt)`
7. Returns `"unchanged"` (hashes match), `"changed"` (mismatch), or `"error"` (missing data)

### OP Browser State (`op_bs` Cookie)

Managed by `PrincipalService` (`app/Services/OAuth2/PrincipalService.php`):

| Lifecycle | Method | Behavior |
|-----------|--------|----------|
| Login | `register()` | `op_bs = SHA-256(session_id)`, stored in session + queued as cookie |
| Read | `get()` | Refreshes the `op_bs` cookie on every principal retrieval |
| Logout | `clear()` | Removes `op_bs` from session, queues cookie deletion (negative expiry) |

Cookie configuration:
- `httpOnly = false` (JavaScript must read it per spec)
- `secure = true`
- `sameSite = 'none'` (cross-site iframe access)
- Exempt from encryption in `EncryptCookies` middleware

### Discovery Metadata

Both endpoints advertised in the discovery document:
- `check_session_iframe`
- `end_session_endpoint`

---

## 6. OpenID Connect RP-Initiated Logout 1.0

**Spec:** https://openid.net/specs/openid-connect-rpinitiated-1_0.html

**Status:** Implemented with one deviation.

### Endpoint

`GET|POST /oauth2/end-session` -> `OAuth2ProviderController::endSession()` (`routes/web.php:110-111`)

Advertised in discovery document as `end_session_endpoint`.

### Request Parameters (Section 2)

| Spec Parameter | Status | Implementation |
|----------------|--------|----------------|
| `id_token_hint` | Implemented | Parsed as JWT, validates `iss`, extracts `aud` and `sub` |
| `client_id` | Implemented | Explicit parameter or extracted from `id_token_hint` |
| `post_logout_redirect_uri` | Implemented | Validated against client's registered URIs (HTTPS required) |
| `state` | Implemented | Passed through to `OAuth2LogoutResponse` |
| `ui_locales` | **Not implemented** | |

### Logout Flow (`OAuth2Protocol::endSession()`)

1. Validates the request via `OAuth2LogoutRequest`
2. If `id_token_hint` is present: parses JWT, verifies `iss` matches current issuer, extracts `aud` (client_id) and `sub` (user_id)
3. If both `client_id` param and `id_token_hint` are present, verifies they match
4. Looks up the client, validates `post_logout_redirect_uri` against registered URIs
5. Enforces HTTPS for `post_logout_redirect_uri`
6. Compares user from `id_token_hint` against currently logged-in user (logs warning on mismatch, proceeds)
7. Calls `auth_service->logout()` to terminate session
8. If `post_logout_redirect_uri` is set: returns redirect with optional `state`
9. Otherwise: renders "session ended" view

### Deviation from Spec

The implementation **requires `client_id`** as a mandatory request parameter (`OAuth2LogoutRequest::isValid()` returns false if `client_id` is empty). The spec states `client_id` SHOULD be provided but is not mandatory when `id_token_hint` is present. Although the code can extract `client_id` from `id_token_hint`'s `aud` claim, the validation rejects the request before reaching that logic.

### Relying Party Tracking

The OP tracks which RPs the user has logged into via an encrypted `rps` cookie (`AuthService::registerRPLogin()` / `getLoggedRPs()`). This cookie is deleted on logout. However, `getLoggedRPs()` is **never called** in the logout flow to notify those RPs (see Section 7).

---

## 7. Front-Channel Logout / Back-Channel Logout

**Specs:**
- https://openid.net/specs/openid-connect-frontchannel-1_0.html
- https://openid.net/specs/openid-connect-backchannel-1_0.html

**Status:** Not implemented. Configuration plumbing exists but is not wired to any business logic.

### Client Configuration Fields

Three fields exist on the Client model (`app/Models/OAuth2/Client.php`):

| Field | DB Column | Intended Purpose | Used in Logic? |
|-------|-----------|------------------|----------------|
| **Logout Uri** | `logout_uri` | URL where the OP notifies the RP that a logout occurred (Front-Channel or Back-Channel Logout) | No |
| **Session Required** | `logout_session_required` | When true, the OP should include a `sid` (session ID) claim in the logout notification so the RP knows which session to terminate | No |
| **Use IFrame** | `logout_use_iframe` | When true, use a hidden iframe to call the RP's `logout_uri` (Front-Channel Logout). When false, use server-to-server notification (Back-Channel Logout) | No |

All three fields:
- Are persisted in the database
- Have getters on the Client model (`getLogoutUri()`, `getLogoutSessionRequired()`, `useLogoutIframe()`)
- Are settable via the Client API with validation rules
- Are serialized for the admin UI (`ClientAdminSerializer`)

### Missing Implementation

The complete notification chain is disconnected:

1. **RP login tracking** (`rps` cookie via `registerRPLogin()`) - Implemented but `getLoggedRPs()` is never called
2. **RP notification on logout** (iterate logged RPs, call each RP's `logout_uri`, optionally via iframe) - **Not implemented**
3. **Session ID in logout token** (`logout_session_required`) - **Not implemented**
4. **Logout token generation** (JWT sent to RP's `logout_uri` containing `sid` and `sub` claims) - **Not implemented**

The `endSession()` flow terminates the OP session but does not notify any RPs. RPs can only detect session changes via the Session Management polling mechanism (check session iframe).

---

## 8. Additional RFCs

### RFC 7636 - PKCE (Proof Key for Code Exchange)

**Status:** Implemented.

| Method | Implementation |
|--------|----------------|
| `plain` | `PKCEPlainValidator.php` |
| `S256` | `PKCES256Validator.php` |

Factory: `OAuth2PKCEValidationMethodFactory.php`

### RFC 7009 - Token Revocation

**Status:** Implemented.

Endpoint: `TokenRevocationEndpoint.php`, advertised in discovery as `revocation_endpoint`.

### RFC 7662 - Token Introspection

**Status:** Implemented.

Endpoint: `TokenIntrospectionEndpoint.php`, advertised in discovery as `introspection_endpoint`.

### RFC 6749 - OAuth 2.0 Authorization Framework

**Status:** Implemented (foundation of the entire system).

Grant types: Authorization Code, Implicit, Client Credentials, Refresh Token, plus custom Passwordless grant type.

---

## 9. Summary Matrix

| Specification | Status | Notes |
|---------------|--------|-------|
| [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html) | Mostly Implemented | Missing: Request Object, Dynamic Registration, claims parameter |
| [OAuth 2.0 Multiple Response Types 1.0](https://openid.net/specs/oauth-v2-multiple-response-types-1_0.html) | Fully Implemented | All 7 response types defined, 6 actively routed. `none` defined but not routed. |
| [OAuth 2.0 Form Post Response Mode 1.0](https://openid.net/specs/oauth-v2-form-post-response-mode-1_0.html) | Fully Implemented | |
| [OpenID Connect Discovery 1.0](https://openid.net/specs/openid-connect-discovery-1_0.html) | Mostly Implemented | Missing: WebFinger (Section 2), several optional metadata fields not populated |
| [OpenID Connect Session Management 1.0](https://openid.net/specs/openid-connect-session-1_0.html) | Fully Implemented | OP iframe, session_state computation, op_bs cookie lifecycle |
| [OpenID Connect RP-Initiated Logout 1.0](https://openid.net/specs/openid-connect-rpinitiated-1_0.html) | Implemented (with deviation) | `client_id` required (spec says SHOULD). `ui_locales` not supported. |
| [OpenID Connect Front-Channel Logout 1.0](https://openid.net/specs/openid-connect-frontchannel-1_0.html) | Not Implemented | Config fields exist on Client model but no notification logic |
| [OpenID Connect Back-Channel Logout 1.0](https://openid.net/specs/openid-connect-backchannel-1_0.html) | Not Implemented | Config fields exist on Client model but no notification logic |
| [OpenID Connect Dynamic Registration 1.0](https://openid.net/specs/openid-connect-registration-1_0.html) | Not Implemented | Clients registered via admin UI/API |
| [RFC 7636 - PKCE](https://tools.ietf.org/html/rfc7636) | Fully Implemented | `plain` and `S256` methods |
| [RFC 7009 - Token Revocation](https://tools.ietf.org/html/rfc7009) | Fully Implemented | |
| [RFC 7662 - Token Introspection](https://tools.ietf.org/html/rfc7662) | Fully Implemented | |
| [RFC 6749 - OAuth 2.0](https://tools.ietf.org/html/rfc6749) | Fully Implemented | Auth Code, Implicit, Client Credentials, Refresh Token |
