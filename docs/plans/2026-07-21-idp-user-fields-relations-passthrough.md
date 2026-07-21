# GET /api/v2/users/{id} fields/relations passthrough Implementation Plan

Created: 2026-07-21
Author: smarcet@gmail.com
Agent: Claude Code
Status: COMPLETE
Approved: Yes
Iterations: 1
Worktree: No
Type: Feature

## Summary

**Goal:** `GET /api/v2/users/{id}` accepts `fields` and `relations` query params (matching the framework's existing pass-through convention), so a service-scoped caller can request exactly `fields=public_profile_allow_chat_with_me,first_name,last_name,pic` without pulling the rest of the private profile — unblocking `ftn-docsnsklz` PR #76 (SDS `ftn-attendee-native-realtime-comms.md`, D38): `attendee-networking-api`'s IDP `user_updated` consumer is explicitly blocked until this lands, because today the endpoint only wires `expand`.

## Out of Scope

- Building the RabbitMQ consumer / `attendee-networking-api` side (SDS §4.8) — that is a separate cross-repo PR against `attendee-networking-api`, not this repo.
- Any change to `PublicUserSerializer` — no consumer needs the public payload narrowed.
- Adding `service.account` middleware to v1 `GET /api/v1/users/{id}` — that is a separate access-control decision (v1 today is reachable by any OAuth2 client with `users-read-all` scope, not just SERVICE-type clients) and is not part of this field-narrowing parity change (Task 2).
- Any change to v1's other actions (`update()`, `getAll()`, `create()`) — Task 2 only touches `get()` (the by-ID GET, the direct v1 counterpart of Task 1's v2 endpoint).
- A generic "empty relations" query syntax — the framework's existing convention (shared by `ApiScopeGroupSerializer`, `ResourceServerSerializer`, `ApiEndpointSerializer`) has no way to distinguish "no relations param sent" from "explicitly zero relations"; both fall back to the serializer's default allowed relations. This plan follows that existing convention as-is rather than changing it.

## Approach

**Chosen:** Reuse `SerializerUtils::getExpand()/getFields()/getRelations()` in `OAuth2UserApiController::getV2()` — the exact helper `ParametrizedGetAll` already uses for list endpoints — and make `PrivateUserSerializer` treat `groups` as a **relation** (gated by `in_array('groups', $relations)`, defaulted via `$allowed_relations`), mirroring the identical pattern already used by `ApiScopeGroupSerializer`/`ResourceServerSerializer`/`ApiEndpointSerializer` for their own related collections.

**Why:** `AbstractSerializer::serialize($expand, $fields, $relations, $params)` (`AbstractSerializer.php:134-144`) already implements `fields` narrowing for every plain mapped attribute (id/created_at/updated_at/first_name/last_name/pic/public_profile_allow_chat_with_me/...) — the only gap is that `getV2()` never populates `$fields`/`$relations`, and `PrivateUserSerializer` currently appends `$values['groups']` unconditionally, bypassing both. The controller's job is purely to forward the three HTTP params (`fields`, `relations`, `expand`) to `serialize()`; the decision of what each param does belongs entirely to the serializer, per the codebase's own convention — this plan does not introduce a new pattern, it applies the existing one to `PrivateUserSerializer`, which is the only serializer in this framework that was still appending a relation-shaped field unconditionally.

## Context for Implementer

`GET /api/v2/users/{id}` is service-scoped (`users-read-all`, `x-required-client-type: SERVICE`) and always resolves `PrivateUserSerializer` (`SerializerRegistry::SerializerType_Private`) — that is intentional and unchanged. Two independent query-param axes apply on top of it:

- **`fields`** narrows which *scalar* mapped attributes appear (id/timestamps/first_name/last_name/pic/public_profile_allow_chat_with_me/etc.) — already implemented in `AbstractSerializer`, just needs wiring.
- **`relations`** (plus `expand` for full sub-object expansion) controls whether the `groups` array appears at all, and in what shape — currently missing on `PrivateUserSerializer`; every sibling serializer with a related collection (`ApiScopeGroupSerializer`, `ResourceServerSerializer`, `ApiEndpointSerializer`) already follows this exact `if(!count($relations)) $relations = $this->getAllowedRelations(); ... if(in_array('X', $relations)) {...}` shape.

These two axes are independent by design: a caller that sends only `fields=...` (no `relations` override) still gets `groups` by default, because `$allowed_relations` defaults it on — that mirrors how every other relation-gated serializer in this codebase already behaves, and preserves the current response shape for every existing caller (v1 `get()`, `me()`, and v2 `getV2()` with no query params) with zero query params sent.

## Implementation Tasks

### Task 1: Wire fields/relations passthrough on GET /api/v2/users/{id} and make PrivateUserSerializer treat groups as a gated relation

**Objective:** `OAuth2UserApiController::getV2()` forwards `fields`/`relations`/`expand` to the serializer via `SerializerUtils` (no serializer decisions in the controller). `PrivateUserSerializer::serialize()` stops appending `groups` unconditionally and instead gates it behind `in_array('groups', $relations)` with `$allowed_relations = ['groups']` as the default (identical shape to `ApiScopeGroupSerializer`), so a `fields=`+`relations=`-narrowed request can omit `groups` while a no-params request keeps today's response shape.

**Files:**
- Modify: `app/Http/Controllers/Api/OAuth2/OAuth2UserApiController.php`
- Modify: `app/ModelSerializers/Auth/UserSerializer.php`
- Test: `tests/OAuth2UserApiTest.php`

**Key Decisions / Notes:**
- `getV2()` (`OAuth2UserApiController.php:740-753`) currently calls `->serialize(Request::input("expand", ''))`. Change to `->serialize(SerializerUtils::getExpand(), SerializerUtils::getFields(), SerializerUtils::getRelations())` — the identical 3-arg call `ParametrizedGetAll.php:143-153` already uses for list endpoints. Add `use App\ModelSerializers\SerializerUtils;` to the controller's `use` block (alongside the existing `use App\ModelSerializers\SerializerRegistry;` at line 22). The controller does not gain any `fields`/`relations` logic of its own — it only forwards the three HTTP params.
- Add two `new OA\Parameter(...)` entries to `getV2`'s OpenAPI attribute (after the existing `expand` one, `OAuth2UserApiController.php:712-718`): `fields` (`"Comma-separated list of scalar fields to return, e.g. first_name,last_name,pic,public_profile_allow_chat_with_me"`) and `relations` (`"Comma-separated list of relations to include (supported: groups)"`), both `in: 'query', required: false, schema: new OA\Schema(type: 'string')`.
- `PrivateUserSerializer` (`UserSerializer.php:46-125`): add `protected static $allowed_relations = ['groups'];`; at the top of `serialize()` add `if (!count($relations)) $relations = $this->getAllowedRelations();`; replace the unconditional `$values['groups'] = $groups;` block with `if (in_array('groups', $relations)) { ...same slug-array logic..., $values['groups'] = $groups; }`. Leave the existing `expand=groups` full-expansion block untouched (it already unsets/replaces `$values['groups']` when present) — this exactly mirrors `ApiScopeGroupSerializer::serialize()` (`app/ModelSerializers/OAuth2/ApiScopeGroupSerializer.php:41-93`), which is the established precedent for this shape in this codebase.
- Do NOT touch `PublicUserSerializer` or the v1 `get()` action — out of scope per the SDS (only the v2 by-ID endpoint is the named blocker).
- No `$allowed_fields` change needed on any User serializer — they are all still `[]`, so `fields` passthrough is purely additive (confirmed: `AbstractSerializer::serialize()` line 138 only substitutes `getAllowedFields()` when `$fields` is empty, and line 144's filter is a no-op when the resolved `$fields` array is still empty).
- New test methods in `tests/OAuth2UserApiTest.php` (alongside the existing `testGetUserByIdV2`, lines 98-120), each a thin variant of the same `$this->action("GET", "Api\OAuth2\OAuth2UserApiController@getV2", $params, ...)` call with `access_token_service_app_type`:
  - `testGetUserByIdV2WithNoParamsReturnsSameShapeAsBefore` — `params` has only `id` (no `fields`/`relations`/`expand` at all); assert the decoded response has the exact same 42-key full private field set (including `groups`) as before this plan — added post-code-review-gate to directly answer "does a zero-param request return the same response as before?" with hard evidence instead of code-trace reasoning alone.
  - `testGetUserByIdV2WithFieldsPassthrough` — `params['fields'] = 'public_profile_allow_chat_with_me,first_name,last_name,pic'` (no `relations`); assert the decoded response has exactly those 4 keys plus `groups` (relations default still applies).
  - `testGetUserByIdV2WithFieldsAndRelationsNone` — same `fields`, plus `params['relations'] = 'none'`; assert the decoded response has **exactly** those 4 keys — no `id`, `created_at`, `updated_at`, or `groups`. `'none'` is an arbitrary non-matching relation name (there is no dedicated empty-relations syntax in this framework, see Out of Scope) — add an inline comment on that line saying so.
  - `testGetUserByIdV2RelationsGroupsWithExpand` — `params['relations'] = 'groups'`, `params['expand'] = 'groups'`; assert `groups` is present as an array of full group objects (existing `expand=groups` shape, unchanged).
  - `testGetUserByIdV2ExpandOverridesRelationsNone` — same `fields`+`relations=none` as above, plus `params['expand'] = 'groups'`; assert `groups` is still present, proving `expand` overrides a `relations`-based omission (added post-changes-review to directly cover the DoD bullet below, which was previously only implied by code inspection).

**Definition of Done:**
- [x] `GET /api/v2/users/{id}` with no query params (existing behavior) returns the same shape as before — full private field set including `groups` as a slug array — no regression for the existing `testGetUserByIdV2` (`expand=groups`).
- [x] `GET /api/v2/users/{id}?fields=public_profile_allow_chat_with_me,first_name,last_name,pic` (no `relations` override) returns those 4 keys **plus** `groups` (relations default still applies — fields and relations are independent axes, per Context for Implementer). Covered by `testGetUserByIdV2WithFieldsPassthrough`.
- [x] `GET /api/v2/users/{id}?fields=public_profile_allow_chat_with_me,first_name,last_name,pic&relations=none` returns **exactly** those 4 keys — no `id`, `created_at`, `updated_at`, or `groups` (`none` is a placeholder relation name that matches nothing, proving the `relations` override suppresses the default). Covered by `testGetUserByIdV2WithFieldsAndRelationsNone`.
- [x] `GET /api/v2/users/{id}?relations=groups&expand=groups` still returns fully-expanded group objects (existing `expand=groups` behavior unchanged). Covered by `testGetUserByIdV2RelationsGroupsWithExpand`.
- [x] `GET /api/v2/users/{id}?fields=public_profile_allow_chat_with_me,first_name,last_name,pic&relations=none&expand=groups` still returns fully-expanded `groups` — `expand` takes precedence over a `relations`-based omission, matching the pre-existing `ApiScopeGroupSerializer` semantics (not a new behavior introduced here; documented so nobody later assumes `relations` is a hard boundary against `expand`).
- [x] Verify: `vendor/bin/phpunit tests/OAuth2UserApiTest.php`

### Task 2: Wire fields/relations/expand passthrough on GET /api/v1/users/{id} and migrate its error handling to processRequest()

**Objective:** Mirror Task 1 for the v1 by-ID endpoint (user-requested parity, added in Iteration 1): `OAuth2UserApiController::get()` gains `fields`/`relations`/`expand` support via the same `SerializerUtils` calls as `getV2()`, and its manual `try/catch` block is replaced with the shared `processRequest()` wrapper (matching `getV2()`'s style). `PrivateUserSerializer`'s `groups`-as-relation gate (Task 1) already applies here automatically — same serializer class, no further serializer change needed.

**Files:**
- Modify: `app/Http/Controllers/Api/OAuth2/OAuth2UserApiController.php` (`get()` method, lines 620-683, and its `#[OA\Get(...)]` attribute)
- Test: `tests/OAuth2UserApiTest.php`

**Key Decisions / Notes:**
- `get()` (`OAuth2UserApiController.php:665-683`, pre-Task-2 line numbers) currently calls `->serialize()` with **zero** args (line 672) — change to `->serialize(SerializerUtils::getExpand(), SerializerUtils::getFields(), SerializerUtils::getRelations())`, identical to Task 1's `getV2()` change.
- Replace the manual `try { ... } catch (ValidationException...) catch (EntityNotFoundException...) catch (Exception...)` block (lines 667-682) with `return $this->processRequest(function () use ($id) { ...same body... });` (the same wrapper `getV2()` already uses, from the `RequestProcessor` trait already imported by this class via `use App\Http\Controllers\Traits\RequestProcessor;`, line 17).
- Confirmed byte-identical observable behavior for the 3 exception types v1 currently handles: `RequestProcessor::processRequest()` (`app/Http/Controllers/Traits/RequestProcessor.php:35-72`) catches `ValidationException` → `error412($ex->getMessages())` (same call v1 makes today); `EntityNotFoundException` → `error404($ex->getMessage())`, and `error404()` (`app/Http/Controllers/Traits/JsonResponses.php:67-72`) normalizes a non-array argument into `['message' => $data]` internally, producing the **exact same JSON shape** as v1's current `error404(['message' => $ex2->getMessage()])`; generic `Exception` → `Log::error($ex); error500($ex)`, same as v1 today. `processRequest()` also now catches `AuthzException`/`InvalidArgumentException`/`HTTP401`/`HTTP403`/`HTTP400` exceptions that `get()`'s body never throws today — added robustness, not an observable behavior change for this method.
- Add `fields`/`relations` `OA\Parameter` entries to `get()`'s OpenAPI attribute (after the existing `id` parameter, mirroring Task 1's additions to `getV2`'s doc block) — same descriptions as Task 1.
- **Blast radius note:** unlike v2, v1's route (`routes/api.php:32`) has **no** `service.account` middleware — it's reachable by any OAuth2 client holding `users-read-all` scope, not just SERVICE-type clients (see Out of Scope — adding that middleware is explicitly not part of this task). The field-narrowing itself is purely additive (same proof as Task 1: `AbstractSerializer` only filters when `$fields` is non-empty), so no existing v1 caller sees a different response unless it starts sending the new params.
- New test methods in `tests/OAuth2UserApiTest.php`, mirroring Task 1's v2 tests but against `Api\OAuth2\OAuth2UserApiController@get` (v1) instead of `@getV2`:
  - `testGetUserByIdV1WithFieldsPassthrough` — `fields=public_profile_allow_chat_with_me,first_name,last_name,pic`; assert those 4 keys + `groups` (relations default).
  - `testGetUserByIdV1WithFieldsAndRelationsNone` — same `fields` + `relations=none`; assert exactly those 4 keys.
  - `testGetUserByIdV1NotFoundStillReturns404` — a non-existent id; assert HTTP 404 with a `message` key in the JSON body, proving the `processRequest()` migration preserves `EntityNotFoundException` handling.

**Definition of Done:**
- [x] `GET /api/v1/users/{id}` with no query params (existing behavior, covered by unchanged `testGetUserByIdV1`) still returns the same full private payload shape as before.
- [x] `GET /api/v1/users/{id}?fields=public_profile_allow_chat_with_me,first_name,last_name,pic` returns those 4 keys plus `groups`. Covered by `testGetUserByIdV1WithFieldsPassthrough`.
- [x] `GET /api/v1/users/{id}?fields=public_profile_allow_chat_with_me,first_name,last_name,pic&relations=none` returns exactly those 4 keys. Covered by `testGetUserByIdV1WithFieldsAndRelationsNone`.
- [x] `GET /api/v1/users/{non-existent-id}` returns HTTP 404 with `{"message": ...}` — proves the `processRequest()` migration didn't change error-handling behavior. Covered by `testGetUserByIdV1NotFoundStillReturns404`.
- [x] Verify: `vendor/bin/phpunit tests/OAuth2UserApiTest.php`

## Progress Tracking

- [x] Task 1: Wire fields/relations passthrough on GET /api/v2/users/{id} and make PrivateUserSerializer treat groups as a gated relation
- [x] Task 2: Wire fields/relations/expand passthrough on GET /api/v1/users/{id} and migrate its error handling to processRequest()

## Verification Gaps (Iteration 1)

| Gap | Type | Severity | Affected Files | Fix Description |
|-----|------|----------|-----------------|------------------|
| v1 `GET /api/v1/users/{id}` has no `fields`/`relations`/`expand` support and uses manual try/catch instead of `processRequest()` | Scope addition (user-requested at Code Review Gate, not a bug in Task 1) | should_fix | `app/Http/Controllers/Api/OAuth2/OAuth2UserApiController.php`, `tests/OAuth2UserApiTest.php` | Task 2 above — mirrors Task 1's v2 fix for the v1 endpoint |
