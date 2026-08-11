<?php namespace Auth;
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

/**
 * Wire-level error_code values emitted by the MFA endpoints
 * (UserController::verify2FA / verify2FARecovery / resend2FA) and consumed by
 * TwoFactorRateLimitMiddleware's failure counting and the login SPA
 * (resources/js/login/constants.js MFA_ERROR_CODE) - keep both in sync.
 *
 * Related codes that already live with their owning feature:
 * ILoginStrategy::MFA_REQUIRED and
 * ITwoFactorRateLimitService::RATE_LIMIT_ERROR_CODE.
 *
 * @package Auth
 */
final class MFAConstants
{
    public const ERROR_CODE_VERIFICATION_FAILED = 'mfa_verification_failed';
    public const ERROR_CODE_INVALID_RECOVERY    = 'mfa_invalid_recovery';
    public const ERROR_CODE_SESSION_EXPIRED     = 'mfa_session_expired';
}
