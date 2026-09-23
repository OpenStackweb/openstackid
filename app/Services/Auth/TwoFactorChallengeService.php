<?php

namespace App\Services\Auth;

/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\libs\Auth\Models\TwoFactorAuditLog;
use Auth\Exceptions\AuthenticationException;
use Auth\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use models\exceptions\ValidationException;
use Models\OAuth2\Client;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Utils\IPHelper;
use Utils\Services\IAuthService;

/**
 * Class TwoFactorChallengeService
 * @package App\Services\Auth
 */
final class TwoFactorChallengeService implements ITwoFactorChallengeService
{
    public function __construct(
        private readonly ITwoFactorGateService $mfa_gate_service,
        private readonly ITwoFactorRateLimitService $rate_limit_service,
        private readonly ITwoFactorAuditService $audit_service,
        private readonly IAuthService $auth_service,
        private readonly IMementoOAuth2SerializerService $oauth2_memento_service,
        private readonly IClientRepository $client_repository
    ) {
    }

    public function issueChallengeIfRequired(User $user, ?string $cookieToken, bool $remember): ?array
    {
        if (!$this->mfa_gate_service->requiresChallenge($user, $cookieToken)) {
            return null;
        }

        // Initial issuance shares the resend rate-limit window
        // (SDS idp-mfa.md §4.12) - without this, this would be an
        // unthrottled way to mail-bomb the account owner with OTP codes.
        if ($this->rate_limit_service->isRateLimited(
            ITwoFactorRateLimitService::ActionResend,
            $user->getId()
        )) {
            throw new AuthenticationException(ITwoFactorRateLimitService::RATE_LIMIT_MESSAGE);
        }

        // Issue a challenge and stop short of session creation.
        $client   = $this->resolveClientFromMemento();
        $method   = $user->getTwoFactorMethod();
        $strategy = MFAChallengeStrategyFactory::create($method);
        $payload  = $this->auth_service->issueMFAChallenge($user, $strategy, $client, $remember);
        $this->rate_limit_service->increment(ITwoFactorRateLimitService::ActionResend, $user->getId());

        // Best-effort: the challenge was already issued and the OTP sent, so
        // an audit-logging failure must not fail the caller out of the
        // mfa_required response the user needs to proceed.
        try {
            $this->audit_service->log(
                $user,
                TwoFactorAuditLog::EventChallengeIssued,
                $method,
                IPHelper::getUserIp()
            );
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }

        // Restore-on-refresh: a subsequent GET /login can rehydrate the 2FA
        // screen from session instead of dropping back to the primary-auth
        // screen. otp_length/otp_lifetime (part of $payload) are flashed by
        // challengeRequired() itself; flow/mfa_method aren't part of the
        // challenge payload, so they're set here.
        Session::put('flow', IAuthService::AuthenticationFlowMFA);
        Session::put('mfa_method', $method);

        return $payload;
    }

    /**
     * Own copy of UserController::resolveClientFromMemento() - resolves the
     * OAuth2 client for a pending authorization request, if any, so the
     * challenge (and its later verification) can be scoped to that client
     * the same way the password flow already does.
     */
    private function resolveClientFromMemento(): ?Client
    {
        if (!$this->oauth2_memento_service->exists()) {
            return null;
        }

        $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build(
            OAuth2Message::buildFromMemento($this->oauth2_memento_service->load())
        );

        if (!$oauth_auth_request->isValid()) {
            return null;
        }

        $client = $this->client_repository->getClientById($oauth_auth_request->getClientId());
        if (is_null($client)) {
            throw new ValidationException("client does not exists");
        }

        $this->oauth2_memento_service->serialize($oauth_auth_request->getMessage()->createMemento());

        return $client;
    }
}
