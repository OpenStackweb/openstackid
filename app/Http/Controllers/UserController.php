<?php namespace App\Http\Controllers;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Http\Controllers\OpenId\DiscoveryController;
use App\Http\Controllers\OpenId\OpenIdController;
use App\Http\Controllers\Traits\JsonResponses;
use App\Http\Controllers\Traits\MFACookieManager;
use App\Http\Utils\CountryList;
use App\libs\Auth\Models\TwoFactorAuditLog;
use App\libs\OAuth2\Strategies\LoginHintProcessStrategy;
use App\ModelSerializers\SerializerRegistry;
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\ITwoFactorAuditService;
use App\Services\Auth\ITwoFactorGateService;
use App\Services\Auth\ITwoFactorRateLimitService;
use App\Services\Auth\IUserService as AuthUserService;
use Auth\Exceptions\AuthenticationException;
use Auth\Exceptions\UnverifiedEmailMemberException;
use Auth\User;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IResourceServerService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\ITokenService;
use OpenId\Services\IMementoOpenIdSerializerService;
use OpenId\Services\ITrustedSitesService;
use OpenId\Services\IUserService;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;
use Services\IUserActionService;
use Sokil\IsoCodes\IsoCodesFactory;
use Strategies\DefaultLoginStrategy;
use Strategies\IConsentStrategy;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Strategies\OAuth2ConsentStrategy;
use Strategies\OAuth2LoginStrategy;
use Strategies\OpenIdConsentStrategy;
use Strategies\OpenIdLoginStrategy;
use Utils\IPHelper;
use Utils\Services\IAuthService;
use Utils\Services\IServerConfigurationService;
use Utils\Services\IServerConfigurationService as IUtilsServerConfigurationService;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
final class UserController extends OpenIdController
{
    /**
     * @var IMementoOpenIdSerializerService
     */
    private $openid_memento_service;
    /**
     * @var IMementoOAuth2SerializerService
     */
    private $oauth2_memento_service;
    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IServerConfigurationService
     */
    private $server_configuration_service;
    /**
     * @var DiscoveryController
     */
    private $discovery;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var AuthUserService
     */
    private $auth_user_service;
    /**
     * @var IUserActionService
     */
    private $user_action_service;
    /**
     * @var DefaultLoginStrategy
     */
    private $login_strategy;
    /**
     * @var IConsentStrategy
     */
    private $consent_strategy;
    /**
     * @var IClientRepository
     */
    private $client_repository;
    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;
    /**
     * @var ITokenService
     */
    private $token_service;
    /**
     * @var IResourceServerService
     */
    private $resource_server_service;
    /**
     * @var IUtilsServerConfigurationService
     */
    private $utils_configuration_service;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @var IDeviceTrustService
     */
    private $device_trust_service;

    /**
     * @var ITwoFactorAuditService
     */
    private $two_factor_audit_service;

    /**
     * @var ITwoFactorGateService
     */
    private $mfa_gate_service;

    /**
     * @var ITwoFactorRateLimitService
     */
    private $two_factor_rate_limit_service;

    /**
     * @param IMementoOpenIdSerializerService $openid_memento_service
     * @param IMementoOAuth2SerializerService $oauth2_memento_service
     * @param IAuthService $auth_service
     * @param IUtilsServerConfigurationService $server_configuration_service
     * @param ITrustedSitesService $trusted_sites_service
     * @param DiscoveryController $discovery
     * @param IUserService $user_service
     * @param AuthUserService $auth_user_service
     * @param IUserActionService $user_action_service
     * @param IClientRepository $client_repository
     * @param IApiScopeRepository $scope_repository
     * @param ITokenService $token_service
     * @param IResourceServerService $resource_server_service
     * @param IUtilsServerConfigurationService $utils_configuration_service
     * @param ISecurityContextService $security_context_service
     * @param LoginHintProcessStrategy $login_hint_process_strategy
     */
    public function __construct
    (
        IMementoOpenIdSerializerService $openid_memento_service,
        IMementoOAuth2SerializerService $oauth2_memento_service,
        IAuthService $auth_service,
        IServerConfigurationService $server_configuration_service,
        ITrustedSitesService $trusted_sites_service,
        DiscoveryController $discovery,
        IUserService $user_service,
        AuthUserService $auth_user_service,
        IUserActionService $user_action_service,
        IClientRepository $client_repository,
        IApiScopeRepository $scope_repository,
        ITokenService $token_service,
        IResourceServerService $resource_server_service,
        IUtilsServerConfigurationService $utils_configuration_service,
        ISecurityContextService $security_context_service,
        LoginHintProcessStrategy $login_hint_process_strategy,
        IDeviceTrustService $device_trust_service,
        ITwoFactorAuditService $two_factor_audit_service,
        ITwoFactorGateService $mfa_gate_service,
        ITwoFactorRateLimitService $two_factor_rate_limit_service,
    )
    {
        $this->openid_memento_service = $openid_memento_service;
        $this->oauth2_memento_service = $oauth2_memento_service;
        $this->auth_service = $auth_service;
        $this->server_configuration_service = $server_configuration_service;
        $this->trusted_sites_service = $trusted_sites_service;
        $this->discovery = $discovery;
        $this->user_service = $user_service;
        $this->auth_user_service = $auth_user_service;
        $this->user_action_service = $user_action_service;
        $this->client_repository = $client_repository;
        $this->scope_repository = $scope_repository;
        $this->token_service = $token_service;
        $this->resource_server_service = $resource_server_service;
        $this->utils_configuration_service = $utils_configuration_service;
        $this->security_context_service = $security_context_service;
        $this->device_trust_service = $device_trust_service;
        $this->two_factor_audit_service = $two_factor_audit_service;
        $this->mfa_gate_service = $mfa_gate_service;
        $this->two_factor_rate_limit_service = $two_factor_rate_limit_service;

        $this->middleware(function ($request, $next) use($login_hint_process_strategy){

            Log::debug(sprintf("UserController::middleware route %s %s", $request->getMethod(), $request->getRequestUri()));

            if ($this->openid_memento_service->exists()) {
                //openid stuff
                Log::debug(sprintf("UserController::middleware OIDC"));
                $this->login_strategy = new OpenIdLoginStrategy
                (
                    $this->openid_memento_service,
                    $this->user_action_service,
                    $this->auth_service,
                    $login_hint_process_strategy
                );

                $this->consent_strategy = new OpenIdConsentStrategy
                (
                    $this->openid_memento_service,
                    $this->auth_service,
                    $this->server_configuration_service,
                    $this->user_action_service
                );

            } else if ($this->oauth2_memento_service->exists()) {
                Log::debug(sprintf("UserController::middleware OAUTH2"));
                $this->login_strategy = new OAuth2LoginStrategy
                (
                    $this->auth_service,
                    $this->oauth2_memento_service,
                    $this->user_action_service,
                    $login_hint_process_strategy
                );

                $this->consent_strategy = new OAuth2ConsentStrategy
                (
                    $this->auth_service,
                    $this->oauth2_memento_service,
                    $this->scope_repository,
                    $this->client_repository
                );
            } else {
                //default stuff
                Log::debug(sprintf("UserController::middleware DEFAULT"));
                $this->login_strategy = new DefaultLoginStrategy
                (
                    $this->user_action_service,
                    $this->auth_service,
                    $login_hint_process_strategy
                );
                $this->consent_strategy = null;
            }

            return $next($request);
        });
    }

    public function getLogin()
    {
        return $this->login_strategy->getLogin();
    }

    public function cancelLogin()
    {
        // A cancelled login must invalidate any pending MFA challenge server-side,
        // not just reset the client's view of things - otherwise an OTP issued
        // before cancel can still complete a login the user explicitly abandoned.
        $method = Session::get('mfa_method');
        if (!is_null($method)) {
            MFAChallengeStrategyFactory::create($method)->clearPendingState();
        }
        $this->clearMFAUISessionState();

        return $this->login_strategy->cancelLogin();
    }

    use JsonResponses;

    use MFACookieManager;

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAccount()
    {
        try {
            $email = Request::input("email", "");
            if (empty($email)) {
                throw new ValidationException("empty email.");
            }

            $user = $this->auth_service->getUserByUsername($email);

            if (is_null($user))
                throw new EntityNotFoundException();

            return $this->ok(
                [
                    'is_active' => $user->isActive(),
                    'is_verified' => $user->isEmailVerified(),
                    'pic' => $user->getPic(),
                    'full_name' => $user->getFullName(),
                    'has_password_set' => $user->hasPasswordSet(),
                ]
            );
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function emitOTP()
    {
        try {

            $username = Request::input("username", "");
            $connection = Request::input("connection", "");
            $send = Request::input("send", "");

            if (empty($username)) {
                throw new ValidationException("empty username param.");
            }

            if (empty($connection)) {
                throw new ValidationException("empty connectin param.");
            }

            if (empty($send)) {
                throw new ValidationException("empty send param.");
            }

            $client = null;

            // check if we have a former oauth2 request
            if ($this->oauth2_memento_service->exists()) {

                Log::debug("UserController::getOTP exist a oauth auth request on session");

                $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    OAuth2Message::buildFromMemento($this->oauth2_memento_service->load())
                );

                if ($oauth_auth_request->isValid()) {

                    $client_id = $oauth_auth_request->getClientId();

                    $client = $this->client_repository->getClientById($client_id);
                    if (is_null($client))
                        throw new ValidationException("Client does not exists.");

                    $this->oauth2_memento_service->serialize($oauth_auth_request->getMessage()->createMemento());
                }
            }

            $otp = $this->token_service->createOTPFromPayload([
                OAuth2Protocol::OAuth2PasswordlessConnection => $connection,
                OAuth2Protocol::OAuth2PasswordlessSend => $send,
                OAuth2Protocol::OAuth2PasswordlessEmail => ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionEmail) ? $username : null,
                OAuth2Protocol::OAuth2PasswordlessPhoneNumber => ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionSMS) ? $username : null
            ], $client);

            return $this->created([
                'otp_length' => $otp->getLength(),
                'otp_lifetime' => $otp->getLifetime(),
            ]);
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function resendVerificationEmail(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = Validator::make($payload, [
                'email' => 'required|string|email|max:255'
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }
            $this->auth_user_service->resendVerificationEmail($payload);
            return $this->ok();
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function postLogin()
    {
        $max_login_attempts_2_show_captcha = $this->server_configuration_service->getConfigValue("MaxFailed.LoginAttempts.2ShowCaptcha");
        $max_login_failed_attempts = intval($this->server_configuration_service->getConfigValue("MaxFailed.Login.Attempts"));
        $login_attempts                    = 0;
        $username                          = '';
        $user = null;

        try
        {

            $data = Request::all();

            if (isset($data['username']))
                $data['username'] = trim($data['username']);

            if (isset($data['password']))
                $data['password'] = trim($data['password']);

            $login_attempts = intval(Request::input('login_attempts'));
            // Build the validation constraint set.
            $rules = [
                'username' => 'required|email',
                'password' => 'required',
                'flow' => 'required|in:otp,password',
                'connection' => 'sometimes|string|in:sms,email',
            ];

            if ($login_attempts >= $max_login_attempts_2_show_captcha) {
                $rules['cf-turnstile-response'] = ['required', new Turnstile()];
            }
            // Create a new validator instance.
            $validator = Validator::make($data, $rules);

            if ($validator->passes()) {

                $username = $data['username'];
                $password = $data['password'];
                $flow     = $data['flow'];
                $remember = Request::input("remember");
                $remember = !is_null($remember);
                $connection = $data['connection'] ?? null;

                try {
                    if ($flow == IAuthService::AuthenticationFlowPassword) {
                        // Validate credentials WITHOUT establishing a session, so the
                        // MFA gate can run before the user is authenticated.
                        $user = $this->auth_service->validateCredentials($username, $password);

                        $cookieToken = $this->getCookieToken();

                        if ($this->mfa_gate_service->requiresChallenge($user, $cookieToken)) {
                            // Initial issuance shares the resend rate-limit window
                            // (SDS idp-mfa.md §4.12) - without this, this route
                            // would be an unthrottled way to mail-bomb the account
                            // owner with OTP codes.
                            if ($this->two_factor_rate_limit_service->isRateLimited(
                                ITwoFactorRateLimitService::ActionResend,
                                $user->getId()
                            )) {
                                throw new AuthenticationException(ITwoFactorRateLimitService::RATE_LIMIT_MESSAGE);
                            }

                            // Issue a challenge and stop short of session creation.
                            $client   = $this->resolveClientFromMemento();
                            $method   = $user->getTwoFactorMethod();
                            $strategy = MFAChallengeStrategyFactory::create($method);
                            $payload = $this->auth_service->issueMFAChallenge($user, $strategy, $client, $remember);
                            $this->two_factor_rate_limit_service->increment(ITwoFactorRateLimitService::ActionResend, $user->getId());

                            // Best-effort: the challenge was already issued and the OTP
                            // sent, so an audit-logging failure must not 500 the user
                            // out of the mfa_required response they need to proceed.
                            try {
                                $this->two_factor_audit_service->log(
                                    $user,
                                    TwoFactorAuditLog::EventChallengeIssued,
                                    $method,
                                    IPHelper::getUserIp()
                                );
                            } catch (\Throwable $ex) {
                                Log::warning($ex);
                            }

                            // Restore-on-refresh: a subsequent GET /login can rehydrate
                            // the 2FA screen from session instead of dropping back to the
                            // password form. otp_length/otp_lifetime (part of $payload)
                            // are flashed by challengeRequired() itself; flow/mfa_method
                            // aren't part of the challenge payload, so they're set here.
                            Session::put('flow', IAuthService::AuthenticationFlowMFA);
                            Session::put('mfa_method', $method);

                            return $this->login_strategy->challengeRequired($payload);
                        }

                        // No challenge required: establish the session and continue.
                        $this->auth_service->loginUser($user, $remember);
                        return $this->login_strategy->postLogin();
                    }

                    if ($flow == IAuthService::AuthenticationFlowPasswordless) {

                        // Passwordless login is single-factor (email access only) and
                        // must not be usable to satisfy MFA enforcement (SDS idp-mfa.md
                        // §7.4 / Open Question #3).
                        $existing_user = $this->auth_service->getUserByUsername($username);
                        if (!is_null($existing_user) && $existing_user->shouldRequire2FA()) {
                            throw new AuthenticationException(
                                "This account requires password and two-factor authentication. Please use the password login option."
                            );
                        }

                        $client = $this->resolveClientFromMemento();

                        $otpClaim = OAuth2OTP::fromParams($username, $connection, $password);
                        $this->auth_service->loginWithOTP($otpClaim, $client);
                        return $this->login_strategy->postLogin();
                    }
                } catch (AuthenticationException $ex) {
                    // failed login attempt...

                    $user = $this->auth_service->getUserByUsername($username);
                    if (!is_null($user)) {
                        $login_attempts = $user->getLoginFailedAttempt();
                    }

                    return $this->login_strategy->errorLogin
                    (
                        [
                            'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                            'max_login_failed_attempts' => $max_login_failed_attempts,
                            'login_attempts' => $login_attempts,
                            'error_message' => $ex->getMessage(),
                            'user_fullname' => !is_null($user) ? $user->getFullName() : "",
                            'user_pic' => !is_null($user) ? $user->getPic(): "",
                            'user_verified' => true,
                            'username' => $username,
                            'flow' => $flow,
                            'user_is_active' => !is_null($user) ? ($user->isActive() ? 1 : 0) : 0
                        ]
                    );
                }

            }

            // validator errors
            $response_data =    [
                'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                'max_login_failed_attempts'         => $max_login_failed_attempts,
                'login_attempts'                    => $login_attempts,
                'validator'                         => $validator,
            ];

            if (is_null($user) && isset($data['username'])) {
                $user = $this->auth_service->getUserByUsername($data['username']);
            }

            if(!is_null($user)){
                $response_data['user_fullname'] = $user->getFullName();
                $response_data['user_pic'] = $user->getPic();
                $response_data['user_verified'] = 1;
                $response_data['user_is_active'] = $user->isActive() ? 1 : 0;
            }

            return $this->login_strategy->errorLogin
            (
                $response_data
            );

        } catch (UnverifiedEmailMemberException $ex1) {
            Log::warning($ex1);

            $user = $this->auth_service->getUserByUsername($username);

            $response_data =    [
                'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                'max_login_failed_attempts'         => $max_login_failed_attempts,
                'login_attempts'                    => $login_attempts,
                'username'                          => $username,
                'error_message'                     => $ex1->getMessage(),
            ];

            if (is_null($user) && isset($data['username'])) {
                $user = $this->auth_service->getUserByUsername($data['username']);
            }

            if(!is_null($user)){
                $response_data['user_fullname'] = $user->getFullName();
                $response_data['user_pic'] = $user->getPic();
                $response_data['user_verified'] = 1;
                $response_data['user_is_active'] = $user->isActive() ? 1 : 0;
            }

            return $this->login_strategy->errorLogin
            (
                $response_data
            );
        } catch (AuthenticationException $ex2) {
            Log::warning($ex2);
            return Redirect::action('UserController@getLogin');
        } catch (Exception $ex) {
            Log::error($ex);
            return Redirect::action('UserController@getLogin');
        }
    }

    /**
     * Resolves the OAuth2 client from a former authorization request stored in
     * the session memento, if any. Returns null when there is no pending OAuth2
     * request (e.g. plain IdP login).
     *
     * @return Client|null
     * @throws ValidationException
     */
    private function resolveClientFromMemento(): ?Client
    {
        if (!$this->oauth2_memento_service->exists()) {
            return null;
        }

        Log::debug("UserController::resolveClientFromMemento exist a oauth auth request on session");

        $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
        (
            OAuth2Message::buildFromMemento($this->oauth2_memento_service->load())
        );

        if (!$oauth_auth_request->isValid()) {
            return null;
        }

        $client = $this->client_repository->getClientById($oauth_auth_request->getClientId());
        if (is_null($client))
            throw new ValidationException("client does not exists");

        $this->oauth2_memento_service->serialize($oauth_auth_request->getMessage()->createMemento());

        return $client;
    }

    /**
     * Verifies a 2FA OTP challenge and, on success, establishes the session.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function verify2FA()
    {
        try {
            $data = Request::all();
            $validator = Validator::make($data, [
                'otp_value'    => 'required|string',
                'method'       => 'required|string|in:' . implode(',', User::ValidMFAMethods),
                'trust_device' => 'sometimes|boolean',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $method       = $data['method'];
            $otp_value     = $data['otp_value'];
            $trust_device  = Request::boolean('trust_device');

            $strategy = MFAChallengeStrategyFactory::create($method);
            $pending  = $strategy->getPendingState();

            if (is_null($pending)) {
                return $this->mfaSessionExpired();
            }

            $user = $this->auth_service->getUserById((int) $pending['user_id']);
            if (is_null($user) || !$user->isTwoFactorMethodEnabled($method)) {
                $strategy->clearPendingState();
                return $this->mfaSessionExpired();
            }

            // Scope verification to the client the challenge was issued for.
            $client = $this->resolveClientFromMemento();

            try {
                // Commits the OTP redeem (+ sibling revoke) in its own tx. The
                // session, trusted-device enrollment and audit are applied below
                // as separate post-verification steps.
                $this->auth_service->verifyMFAChallenge(
                    $user,
                    $strategy,
                    $otp_value,
                    $client
                );
            } catch (AuthenticationException $ex) {
                Log::warning($ex);
                // Re-fetch user: the tx wrapper closed/reset the EM on failure, detaching the entity.
                $userId = (int) $pending['user_id'];
                $user = $this->auth_service->getUserById($userId) ?? $user;
                // Best-effort: an audit-logging failure here must not turn a
                // clean 401 into a 500 (which would also drop the error_code
                // the rate-limit middleware keys its failure count on).
                try {
                    $this->two_factor_audit_service->log(
                        $user,
                        TwoFactorAuditLog::EventChallengeFailed,
                        $method,
                        IPHelper::getUserIp()
                    );
                } catch (\Throwable $auditEx) {
                    Log::warning($auditEx);
                }
                return $this->unauthorized(['error_code' => 'mfa_verification_failed']);
            }

            // Second factor verified: establish the session.
            $this->auth_service->loginUser($user, (bool) $pending['remember']);

            if ($trust_device) {
                // Best-effort: the OTP is already redeemed and the session
                // established, so a trusted-device enrollment failure must not
                // 500 the user (which would lock them out on retry against a
                // burned OTP). Log and continue; the device just isn't remembered.
                try {
                    $this->queueDeviceTrustCookie($user);
                } catch (\Throwable $ex) {
                    Log::warning($ex);
                }
            }

            $strategy->clearPendingState();
            $this->clearMFAUISessionState();

            try {
                $this->two_factor_audit_service->log(
                    $user,
                    TwoFactorAuditLog::EventChallengeSucceeded,
                    $method,
                    IPHelper::getUserIp()
                );
            } catch (\Throwable $ex) {
                Log::warning($ex);
            }

            // Return the same-origin post-login destination as data instead of a raw
            // redirect for this XHR to follow: postLogin() can chain into a cross-origin
            // hop (authorization code delivery to an already-consented OAuth2 client),
            // which no XHR/fetch can read past - and per InteractiveGrantType::handle()'s
            // consent-bypass branch, that hop also consumes the OAuth2 memento as a side
            // effect, so a silently-failed XHR follow-through burns the authorization
            // code with no way to recover it client-side. A real top-level navigation to
            // this URL lets the browser complete that chain natively instead - CORS never
            // applies to page navigations, only to XHR/fetch.
            $redirect = $this->login_strategy->postLogin();
            return $this->ok(['redirect_url' => $redirect->getTargetUrl()]);
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * Verifies a 2FA recovery code and, on success, establishes the session.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function verify2FARecovery()
    {
        try {
            $data = Request::all();
            $validator = Validator::make($data, [
                'recovery_code' => 'required|string',
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $recovery_code = $data['recovery_code'];

            // Recovery-code handling lives in the base strategy; session keys are
            // method-agnostic, so any concrete strategy can read the pending state.
            $strategy = MFAChallengeStrategyFactory::create(User::MFAMethod_OTP);
            $pending  = $strategy->getPendingState();

            if (is_null($pending)) {
                return $this->mfaSessionExpired();
            }

            $user = $this->auth_service->getUserById((int) $pending['user_id']);
            if (is_null($user)) {
                $strategy->clearPendingState();
                return $this->mfaSessionExpired();
            }

            try {
                $this->auth_service->verifyMFARecoveryCode($user, $strategy, $recovery_code);
            } catch (AuthenticationException $ex) {
                Log::warning($ex);
                // Re-fetch user: the tx wrapper closed/reset the EM on failure, detaching the entity.
                $userId = (int) $pending['user_id'];
                $user = $this->auth_service->getUserById($userId) ?? $user;
                // Best-effort: see verify2FA() for rationale.
                try {
                    $this->two_factor_audit_service->log(
                        $user,
                        TwoFactorAuditLog::EventChallengeFailed,
                        TwoFactorAuditLog::MethodRecovery,
                        IPHelper::getUserIp()
                    );
                } catch (\Throwable $auditEx) {
                    Log::warning($auditEx);
                }
                return $this->unauthorized(['error_code' => 'mfa_invalid_recovery']);
            }

            $this->auth_service->loginUser($user, (bool) $pending['remember']);
            $strategy->clearPendingState();
            $this->clearMFAUISessionState();

            // Best-effort: the recovery code is already redeemed and the session
            // established, so an audit-logging failure must not 500 the user
            // (which would strand them after burning their last-resort code).
            try {
                $this->two_factor_audit_service->log(
                    $user,
                    TwoFactorAuditLog::EventRecoveryUsed,
                    TwoFactorAuditLog::MethodRecovery,
                    IPHelper::getUserIp()
                );
            } catch (\Throwable $ex) {
                Log::warning($ex);
            }

            // See verify2FA() for rationale: return the destination as data so a real
            // top-level navigation (not this XHR) performs any cross-origin hop.
            $redirect = $this->login_strategy->postLogin();
            return $this->ok(['redirect_url' => $redirect->getTargetUrl()]);
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * Re-issues a 2FA challenge for the pending login and returns the challenge payload.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function resend2FA()
    {
        try {
            $data = Request::all();
            $validator = Validator::make($data, [
                'method' => 'required|string|in:' . implode(',', User::ValidMFAMethods),
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }

            $method   = $data['method'];
            $strategy = MFAChallengeStrategyFactory::create($method);
            $pending  = $strategy->getPendingState();

            if (is_null($pending)) {
                return $this->mfaSessionExpired();
            }

            $user = $this->auth_service->getUserById((int) $pending['user_id']);
            if (is_null($user) || !$user->isTwoFactorMethodEnabled($method)) {
                $strategy->clearPendingState();
                return $this->mfaSessionExpired();
            }

            $payload = $this->auth_service->resendMFAChallenge($user, $strategy, $this->resolveClientFromMemento(), (bool) $pending['remember']);

            // Keep the refresh-restorable session state in sync with the
            // fresh challenge (e.g. otp_lifetime countdown resets on resend,
            // mfa_method changes if this resend is actually a method switch).
            Session::put('mfa_method', $method);
            if (isset($payload['otp_length'])) {
                Session::put('otp_length', $payload['otp_length']);
            }
            if (isset($payload['otp_lifetime'])) {
                Session::put('otp_lifetime', $payload['otp_lifetime']);
            }

            // Best-effort: the challenge was already re-issued and the OTP
            // sent, so an audit-logging failure must not 500 the user out of
            // the payload they need to complete verification.
            try {
                $this->two_factor_audit_service->log(
                    $user,
                    TwoFactorAuditLog::EventChallengeIssued,
                    $method,
                    IPHelper::getUserIp()
                );
            } catch (\Throwable $ex) {
                Log::warning($ex);
            }

            return $this->ok($payload);
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function mfaSessionExpired()
    {
        $this->clearMFAUISessionState();
        return $this->unauthorized(['error_code' => 'mfa_session_expired']);
    }

    /**
     * Clears the UI-restoration session keys written when a challenge is
     * issued (see postLogin()'s mfa_required branch). Companion to
     * IMFAChallengeStrategy::clearPendingState(), which only owns the
     * 2fa_* pending-state keys.
     *
     * @return void
     */
    private function clearMFAUISessionState(): void
    {
        Session::forget('flow');
        Session::forget('mfa_method');
        Session::forget('otp_length');
        Session::forget('otp_lifetime');
        Session::forget('error_code');
    }

    /**
     * @return \Illuminate\Http\Response|mixed
     */
    public function getConsent()
    {
        Log::debug("UserController::getConsent");
        if (is_null($this->consent_strategy)) {

            Log::warning(sprintf("UserController::getConsent consent strategy is null. request %s %s", Request::method(), Request::path()));
            return Response::view
            (
                'errors.400',
                [
                    'error' => "Bad Request",
                    'error_description' => "Generic Error"
                ],
                400
            );
        }
        return $this->consent_strategy->getConsent();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
     */
    public function postConsent()
    {
        try {
            $data = Request::all();
            $rules = array
            (
                'trust' => 'required|oauth2_trust_response',
            );
            // Create a new validator instance.
            $validator = Validator::make($data, $rules);
            if ($validator->passes()) {
                if (is_null($this->consent_strategy)) {

                    Log::error(sprintf("UserController::postConsent consent strategy is null. request %s %s", Request::method(), Request::path()));

                    return Response::view
                    (
                        'errors.400',
                        [
                            'error' => "Bad Request",
                            'error_description' => "Generic Error"
                        ],
                        400
                    );
                }

                return $this->consent_strategy->postConsent(Request::input("trust"));
            }
            return Redirect::action('UserController@getConsent')->withErrors($validator);
        } catch (Exception $ex) {
            Log::error($ex);
            return Redirect::action('UserController@getConsent');
        }
    }

    public function getIdentity($identifier)
    {
        try {
            $user = $this->auth_service->getUserByOpenId($identifier);
            if (is_null($user)) {
                return View::make("errors.404");
            }

            if ($this->isDiscoveryRequest()) {
                /*
                * If the Claimed Identifier was not previously discovered by the Relying Party
                * (the "openid.identity" in the request was "http://specs.openid.net/auth/2.0/identifier_select"
                * or a different Identifier, or if the OP is sending an unsolicited positive assertion),
                * the Relying Party MUST perform discovery on the Claimed Identifier in
                * the response to make sure that the OP is authorized to make assertions about the Claimed Identifier.
                */
                return $this->discovery->user($identifier);
            }

            $redirect = Session::get('backurl');
            if (!empty($redirect)) {
                Session::forget('backurl');
                Session::save();
                return Redirect::to($redirect);
            }

            $current_user = $this->auth_service->getCurrentUser();
            $another_user = false;
            if ($current_user && $current_user->getIdentifier() != $user->getIdentifier()) {
                $another_user = true;
            }

            $assets_url = $this->utils_configuration_service->getConfigValue("Assets.Url");
            $pic_url = $user->getPic();
            $pic_url = str_contains($pic_url, 'http') ? $pic_url : $assets_url . $pic_url;

            $params = [
                'show_fullname' => $user->getShowProfileFullName(),
                'username' => $user->getFullName(),
                'show_email' => $user->getShowProfileEmail(),
                'email' => $user->getEmail(),
                'identifier' => $user->getIdentifier(),
                'show_pic' => $user->getShowProfilePic(),
                'pic' => $pic_url,
                'another_user' => $another_user,
            ];

            return View::make("identity", $params);
        } catch (Exception $ex) {
            Log::error($ex);
            return View::make("errors.404");
        }
    }

    public function logout()
    {
        $user = $this->auth_service->getCurrentUser();
        // RevokeUserGrantsOnExplicitLogout::dispatch($user)->afterResponse();
        $this->auth_service->logout();
        return Redirect::action("UserController@getLogin");
    }

    public function getProfile()
    {
        $user = $this->auth_service->getCurrentUser();
        $sites = $user->getTrustedSites();
        $actions = $user->getLatestNActions(10);

        // init database
        $isoCodes = new IsoCodesFactory();

        // get languages database
        $languages = $isoCodes->getLanguages()->toArray();
        $lang2Code = [];
        foreach ($languages as $lang) {
            if (!empty($lang->getAlpha2()))
                $lang2Code[] = $lang;
        }

        return View::make("profile", [
            'user' => json_encode(SerializerRegistry::getInstance()->getSerializer(
                $user, SerializerRegistry::SerializerType_Private)->serialize()),
            "openid_url" => $this->server_configuration_service->getUserIdentityEndpointURL($user->getIdentifier()),
            "sites" => $sites,
            'actions' => $actions,
            'countries' => CountryList::getCountries(),
            'languages' => $lang2Code,
        ]);
    }

    public function deleteTrustedSite($id)
    {
        $this->trusted_sites_service->delete($id);
        return Redirect::action("UserController@getProfile");
    }

}