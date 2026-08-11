<?php
namespace Auth;
/**
 * Copyright 2016 OpenStack Foundation
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

use App\libs\OAuth2\Exceptions\ReloadSessionException;
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use App\Services\AbstractService;
use App\Services\Auth\IUserService as IAuthUserService;
use Auth\Exceptions\AuthenticationException;
use Auth\Exceptions\AuthenticationLockedUserLoginAttempt;
use Auth\Repositories\IUserRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use jwe\compression_algorithms\CompressionAlgorithms_Registry;
use jwe\compression_algorithms\CompressionAlgorithmsNames;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\InvalidOTPException;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IUserService;
use Services\IUserActionService;
use Strategies\MFA\IMFAChallengeStrategy;
use utils\Base64UrlRepresentation;
use Utils\Db\ITransactionService;
use Utils\IPHelper;
use Utils\Services\IAuthService;
use Utils\Services\ICacheService;

/**
 * Class AuthService
 * @package Auth
 */
final class AuthService extends AbstractService implements IAuthService
{
    /**
     * @var IPrincipalService
     */
    private $principal_service;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var IUserActionService
     */
    private $user_action_service;
    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IAuthUserService
     */
    private $auth_user_service;

    /**
     * @var IOAuth2OTPRepository
     */
    private $otp_repository;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * AuthService constructor.
     * @param IUserRepository $user_repository
     * @param IOAuth2OTPRepository $otp_repository
     * @param IPrincipalService $principal_service
     * @param IUserService $user_service
     * @param IUserActionService $user_action_service
     * @param ICacheService $cache_service
     * @param IAuthUserService $auth_user_service
     * @param ISecurityContextService $security_context_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IOAuth2OTPRepository $otp_repository,
        IPrincipalService $principal_service,
        IUserService $user_service,
        IUserActionService $user_action_service,
        ICacheService $cache_service,
        IAuthUserService $auth_user_service,
        ISecurityContextService $security_context_service,
        ITransactionService $tx_service
    ) {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
        $this->principal_service = $principal_service;
        $this->user_service = $user_service;
        $this->user_action_service = $user_action_service;
        $this->cache_service = $cache_service;
        $this->auth_user_service = $auth_user_service;
        $this->otp_repository = $otp_repository;
        $this->security_context_service = $security_context_service;
    }

    /**
     * @return bool
     */
    public function isUserLogged()
    {
        return Auth::check();
    }

    /**
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Finds the OTP by value/connection/username, logs the redeem attempt (TX-A),
     * then validates lifecycle / value / scope / audience (TX-B).
     * TX-A is committed independently so the brute-force counter increments even when TX-B throws.
     *
     * @throws AuthenticationException
     * @throws InvalidOTPException
     */
    private function findAndValidateOTP(
        string $otp_value,
        string $user_name,
        string $otp_conn,
        ?string $otp_required_scopes,
        ?Client $client
    ): OAuth2OTP {
        // TX-A: find + log attempt (committed before any validation can throw)
        $otp = $this->tx_service->transaction(function () use ($otp_value, $otp_conn, $user_name, $client) {

            $otp = $this->otp_repository->getByValueConnectionAndUserName(
                $otp_value,
                $otp_conn,
                $user_name,
                $client
            );

            if (is_null($otp)) {
                Log::warning(sprintf(
                    "AuthService::findAndValidateOTP otp %s user %s grant not found",
                    $otp_value,
                    $user_name
                ));
                throw new AuthenticationException("Non existent single-use code.");
            }

            $otp->logRedeemAttempt();
            return $otp;
        });

        // TX-B: lifecycle / value / scope / audience checks
        return $this->tx_service->transaction(function () use ($otp, $otp_value, $otp_required_scopes, $client) {

            if (!$otp->isAlive()) {
                throw new AuthenticationException("Single-use code is expired.");
            }

            if (!$otp->isValid()) {
                throw new AuthenticationException("Single-use code is not valid.");
            }

            if ($otp->getValue() != $otp_value) {
                throw new AuthenticationException("Single-use code mismatch.");
            }

            if (!empty($otp_required_scopes) && !$otp->allowScope($otp_required_scopes)) {
                throw new InvalidOTPException("Single-use code requested scopes escalates former scopes.");
            }

            if (
                ($otp->hasClient() && is_null($client)) ||
                ($otp->hasClient() && !is_null($client) && $client->getClientId() != $otp->getClient()->getClientId())
            ) {
                throw new AuthenticationException("Single-use code audience mismatch.");
            }

            return $otp;
        });
    }

    /**
     * Marks the OTP redeemed, stores the resolved user id, and revokes sibling
     * pending OTPs. Entity methods short-circuit for inline OTPs — no special-
     * casing needed here.
     *
     * Concurrency: acquires a PESSIMISTIC_WRITE row lock and re-hydrates state
     * before checking redemption. This closes the validate→redeem race window:
     * a second concurrent submitter blocks on the lock and, on resume, sees the
     * row already redeemed and gets a clean AuthenticationException instead of
     * silently double-redeeming.
     *
     * Inline OTPs are intentionally not locked — they are non-redeemable by
     * design (see OAuth2OTP::redeem()) and there is no race to close.
     */
    private function finalizeRedemption(OAuth2OTP $otp, User $user, ?Client $client): void
    {
        if ($otp->getConnection() !== OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            $this->otp_repository->refreshExclusiveLock($otp);

            if ($otp->isRedeemed()) {
                Log::warning(sprintf(
                    "AuthService::finalizeRedemption otp %s already redeemed (concurrent submission).",
                    $otp->getValue()
                ));
                throw new AuthenticationException("Single-use code is already redeemed.");
            }
        }

        $otp->setAuthTime(time());
        $otp->setUserId($user->getId());
        $otp->redeem();

        $grants2Revoke = $this->otp_repository->getByUserNameNotRedeemed($otp->getUserName(), $client);
        foreach ($grants2Revoke as $otp2Revoke) {
            try {
                Log::debug(sprintf("AuthService::finalizeRedemption revoking otp %s", $otp2Revoke->getValue()));
                if ($otp2Revoke->getId() !== $otp->getId())
                    $otp2Revoke->redeem();
            } catch (Exception $ex) {
                Log::warning($ex);
            }
        }
    }

    /**
     * @param OAuth2OTP $otpClaim
     * @param Client|null $client
     * @param bool $remember
     * @return OAuth2OTP|null
     * @throws Exception
     */
    public function loginWithOTP(OAuth2OTP $otpClaim, ?Client $client = null, bool $remember = false): ?OAuth2OTP
    {
        Log::debug(sprintf("AuthService::loginWithOTP otp %s user %s", $otpClaim->getValue(), $otpClaim->getUserName()));

        $otp = $this->findAndValidateOTP(
            $otpClaim->getValue(),
            $otpClaim->getUserName(),
            $otpClaim->getConnection(),
            $otpClaim->getScope(),
            $client
        );

        // TX-C: resolve or create user, finalize, login
        return $this->tx_service->transaction(function () use ($otp, $otpClaim, $client, $remember) {

            $user = $this->getUserByUsername($otp->getUserName());

            if (is_null($user)) {
                Log::debug(sprintf("AuthService::loginWithOTP user %s does not exist; auto-registering.", $otp->getUserName()));
                $user = $this->auth_user_service->registerUser(
                    [
                        'email' => $otp->getUserName(),
                        'email_verified' => true,
                        'send_email_verified_notice' => false,
                        'active' => true,
                    ],
                    $otp
                );
            } else if ($user->isActive()) {
                $user->verifyEmail(false);
            }

            if (!$user->canLogin()) {
                Log::warning(sprintf("AuthService::loginWithOTP user %s cannot login (not active).", $user->getId()));
                throw new AuthenticationException("We are sorry, your username or password does not match an existing record.");
            }

            $this->finalizeRedemption($otp, $user, $client);

            Auth::login($user, $remember);
            Log::debug(sprintf("AuthService::loginWithOTP user %s logged in.", $user->getId()));
            return $otp;
        });
    }

    /**
     * Verifies an OTP against an already-authenticated session user (MFA primitive).
     *
     * The OTP is resolved by its own claim (value + connection + user_name) but the
     * redemption is only finalized if the resolved user matches $sessionUser. This
     * binding is what makes the method safe to use as an MFA second factor: a stolen
     * OTP for account B cannot satisfy MFA for an in-session user A.
     *
     * On user mismatch, the method throws BEFORE finalizeRedemption — the OTP is NOT
     * consumed, so an attacker probing OTPs across accounts cannot burn down legitimate
     * codes by guessing. The redeem-attempt counter (committed in TX-A) still increments,
     * so brute-force tracking is preserved.
     *
     * Does NOT auto-register users and does NOT call Auth::login — the caller is
     * responsible for the session state changes that follow a successful MFA.
     *
     * @throws AuthenticationException  when the OTP is invalid, the resolved user is
     *                                  missing/inactive, or the OTP does not belong
     *                                  to $sessionUser.
     * @throws InvalidOTPException      when the OTP requested scopes escalate.
     */
    public function verifyOTPChallenge(
        OAuth2OTP $otpClaim,
        User $sessionUser,
        ?Client $client = null
    ): OAuth2OTP {
        Log::debug(sprintf(
            "AuthService::verifyOTPChallenge otp %s session user %s",
            $otpClaim->getValue(),
            $sessionUser->getId()
        ));

        $otp = $this->findAndValidateOTP(
            $otpClaim->getValue(),
            $otpClaim->getUserName(),
            $otpClaim->getConnection(),
            $otpClaim->getScope(),
            $client
        );

        // TX-C: resolve OTP's user, enforce session-user binding, then finalize.
        return $this->tx_service->transaction(function () use ($otp, $sessionUser, $client) {

            $user = $this->getUserByUsername($otp->getUserName());

            if (is_null($user) || !$user->canLogin()) {
                Log::warning(sprintf(
                    "AuthService::verifyOTPChallenge otp user %s not found or cannot login.",
                    $otp->getUserName()
                ));
                throw new AuthenticationException("We are sorry, your username or password does not match an existing record.");
            }

            if ($user->getId() !== $sessionUser->getId()) {
                Log::warning(sprintf(
                    "AuthService::verifyOTPChallenge MFA mismatch: otp user %s != session user %s",
                    $user->getId(),
                    $sessionUser->getId()
                ));
                throw new AuthenticationException("Single-use code does not belong to the authenticated user.");
            }

            $this->finalizeRedemption($otp, $user, $client);

            Log::debug(sprintf("AuthService::verifyOTPChallenge session user %s verified.", $sessionUser->getId()));
            return $otp;
        });
    }

    /**
     * @param string $username
     * @return null|User
     */
    public function getUserByUsername(string $username): ?User
    {
        return $this->user_repository->getByEmailOrName($username);
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool $remember_me
     * @return bool
     * @throws AuthenticationException
     */
    public function login(string $username, string $password, bool $remember_me): bool
    {
        Log::debug("AuthService::login");

        if (!Auth::attempt(['username' => $username, 'password' => $password], $remember_me)) {
            throw new AuthenticationException
            (
                "We are sorry, your username or password does not match an existing record."
            );
        }
        Log::debug("AuthService::login: clearing principal");
        $this->principal_service->clear();
        $current_user = $this->getCurrentUser();
        if (is_null($current_user) || !$current_user->canLogin())
            throw new AuthenticationException
            (
                "We are sorry, your username or password does not match an existing record."
            );
        $this->principal_service->register
        (
            $current_user->getId(),
            time()
        );

        return true;
    }

    /**
     * @param string $username
     * @param string $password
     * @return User|null
     * @throws AuthenticationException
     */
    public function validateCredentials(string $username, string $password): User
    {
        Log::debug("AuthService::validateCredentials");

        /**
         * @var User|null $user
         */
        $user = Auth::getProvider()->retrieveByCredentials(['username' => $username, 'password' => $password]);
        if (is_null($user) || !$user instanceof User || !$user->canLogin()) {
            throw new AuthenticationException("We are sorry, your username or password does not match an existing record.");
        }
        return $user;
    }

    /**
     * @param User $user
     * @param bool $remember
     * @return void
     */
    public function loginUser(User $user, bool $remember): void
    {
        Log::debug("AuthService::loginUser");
        if (!$user->canLogin())
            throw new AuthenticationException("User is not active or cannot login.");

        // Auth::login() first: Laravel's SessionGuard::login() already
        // regenerates the session ID internally (session->migrate(true)),
        // closing the pre-auth session-fixation window. Principal bookkeeping
        // runs AFTER so register()'s op_browser_state hash (used for OIDC
        // Session Management) is derived from the FINAL id, not one that
        // Auth::login() is about to invalidate.
        Auth::login($user, $remember);

        $this->principal_service->clear();
        $this->principal_service->register
        (
            $user->getId(),
            time()
        );
    }

    /**
     * @param bool $clear_security_ctx
     * @return void
     */
    public function logout(bool $clear_security_ctx = true): void
    {
        Log::debug("AuthService::logout");
        $current_user = $this->getCurrentUser();
        // check if we have user on session
        if (!is_null($current_user)) {
            $ip = IPHelper::getUserIp();
            Log::debug(sprintf("AuthService::logout we have user %s from ip %s", $current_user->getId(), $ip));
            $this->user_action_service->addUserAction
            (
                $current_user->getId(),
                $ip,
                IUserActionService::LogoutAction
            );
        }

        // regular flow
        $this->invalidateSession();
        $this->principal_service->clear();
        if ($clear_security_ctx)
            $this->security_context_service->clear();
        Auth::logout();
        // put in past
        Cookie::queue
        (
            IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME,
            null,
            $minutes = -2628000,
            $path = Config::get("session.path"),
            $domain = Config::get("session.domain"),
            $secure = true,
            $httpOnly = true,
            $raw = false,
            $sameSite = 'none'
        );

        // Flush all session data and regenerate the session ID to ensure no stale
        // data survives (OAuth2 memento, OpenID auth context, authorization responses, etc.)
        Session::flush();
        Session::regenerate();
    }

    public function invalidateSession(): void
    {
        $session_id = Crypt::encrypt(Session::getId());
        $this->cache_service->addSingleValue($session_id . "invalid", $session_id);
    }

    /**
     * @param string $value
     * @return String
     */
    private function encrypt(string $value): string
    {
        return base64_encode(Crypt::encrypt($value));
    }

    /**
     * @return string
     */
    public function getUserAuthorizationResponse()
    {
        if (Session::has("openid.authorization.response")) {
            $value = Session::get("openid.authorization.response");

            return $value;
        }

        return IAuthService::AuthorizationResponse_None;
    }

    public function clearUserAuthorizationResponse()
    {
        if (Session::has("openid.authorization.response")) {
            Session::remove("openid.authorization.response");
            Session::save();
        }
    }

    public function setUserAuthorizationResponse($auth_response)
    {
        Session::put("openid.authorization.response", $auth_response);
        Session::save();
    }

    // Authentication

    /**
     * @param string $openid
     * @return User|null
     */
    public function getUserByOpenId(string $openid): ?User
    {
        return $this->user_repository->getByIdentifier($openid);
    }

    public function getUserAuthenticationResponse()
    {
        if (Session::has("openstackid.authentication.response")) {
            $value = Session::get("openstackid.authentication.response");
            return $value;
        }
        return IAuthService::AuthenticationResponse_None;
    }

    public function setUserAuthenticationResponse($auth_response)
    {
        Session::put("openstackid.authentication.response", $auth_response);
        Session::save();
    }

    public function clearUserAuthenticationResponse()
    {
        if (Session::has("openstackid.authentication.response")) {
            Session::remove("openstackid.authentication.response");
            Session::save();
        }
    }

    /**
     * @param string $user_id
     * @return string
     */
    public function unwrapUserId(string $user_id): string
    {
        // first try to get user by raw id
        $user = $this->getUserById(intval($user_id));

        if (!is_null($user))
            return $user_id;
        // check if we have a wrapped user id
        try {
            $unwrapped_name = $this->decrypt($user_id);
            $parts = explode(':', $unwrapped_name);
            return intval($parts[1]);
        } catch (Exception $ex) {
            Log::warning($ex);
        }
        return $user_id;
    }

    /**
     * @param int $id
     * @return null|User
     */
    public function getUserById(int $id): ?User
    {
        return $this->user_repository->getByIdWithGroups($id);
    }

    /**
     * @param string $value
     * @return String
     */
    private function decrypt(string $value): string
    {
        $value = base64_decode($value);
        return Crypt::decrypt($value);
    }

    /**
     * @param int $user_id
     * @param IClient $client
     * @return string
     */
    public function wrapUserId(int $user_id, IClient $client): string
    {
        if ($client->getSubjectType() === IClient::SubjectType_Public)
            return $user_id;

        $wrapped_name = sprintf('%s:%s', $client->getClientId(), $user_id);
        return $this->encrypt($wrapped_name);
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return Session::getId();
    }

    /**
     * @param $client_id
     * @return void
     */
    public function registerRPLogin(string $client_id): void
    {

        try {
            $rps = Cookie::get(IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME, "");
            $zlib = CompressionAlgorithms_Registry::getInstance()->get(CompressionAlgorithmsNames::ZLib);

            if (!empty($rps)) {
                $rps = $this->decrypt($rps);
                $rps = $zlib->uncompress($rps);
                $rps .= '|';
            }
            if (is_null($rps))
                $rps = "";

            if (!str_contains($rps, $client_id))
                $rps .= $client_id;

            $rps = $zlib->compress($rps);
            $rps = $this->encrypt($rps);
        } catch (Exception $ex) {
            Log::warning($ex);
            $rps = "";
        }

        Cookie::queue
        (
            IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME,
            $rps,
            Config::get("session.lifetime", 120),
            $path = Config::get("session.path"),
            $domain = Config::get("session.domain"),
            $secure = true,
            $httpOnly = true,
            $raw = false,
            $sameSite = 'none'
        );
    }

    /**
     * @return string[]
     */
    public function getLoggedRPs(): array
    {
        try {
            $rps = Cookie::get(IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME);
            $zlib = CompressionAlgorithms_Registry::getInstance()->get(CompressionAlgorithmsNames::ZLib);
            if (!empty($rps)) {
                $rps = $this->decrypt($rps);
                $rps = $zlib->uncompress($rps);
                return explode('|', $rps);
            }
        } catch (Exception $ex) {
            Log::warning($ex);
        }
        return [];
    }

    /**
     * @param string $jti
     * @throws Exception
     */
    public function reloadSession(string $jti): void
    {
        Log::debug(sprintf("AuthService::reloadSession jti %s", $jti));
        $session_id = $this->cache_service->getSingleValue($jti);

        Log::debug(sprintf("AuthService::reloadSession session_id %s", $session_id));
        if (empty($session_id))
            throw new ReloadSessionException('session not found!');

        if ($this->cache_service->exists($session_id . "invalid")) {
            // session was marked as void, check if we are authenticated
            if (!Auth::check())
                throw new ReloadSessionException('user not found!');
        }

        Session::setId(Crypt::decrypt($session_id));
        Session::start();
        if (!Auth::check()) {
            $user_id = $this->principal_service->get()->getUserId();
            Log::debug(sprintf("AuthService::reloadSession user_id %s", $user_id));
            $user = $this->getUserById($user_id);
            if (is_null($user))
                throw new ReloadSessionException('user not found!');
            Auth::login($user);
        }
    }

    /**
     * @param string $client_id
     * @param int $id_token_lifetime
     * @return string
     */
    public function generateJTI(string $client_id, int $id_token_lifetime): string
    {
        $session_id = Crypt::encrypt(Session::getId());
        $encoder = new Base64UrlRepresentation();
        $jti = $encoder->encode(hash('sha512', $session_id . $client_id, true));

        $this->cache_service->addSingleValue($jti, $session_id, $id_token_lifetime);

        return $jti;
    }

    /**
     * @param int $user_id
     * @return void
     * @throws Exception
     */
    public function postLoginUserActions(int $user_id): void
    {
        Log::debug(sprintf("AuthService::postLoginUserActions user %s", $user_id));
        $this->tx_service->transaction(function () use ($user_id) {
            $user = $this->user_repository->getById($user_id);
            if (!$user instanceof User)
                return;

            if (!$user->isActive()) {
                Log::warning(sprintf("AuthService::postLoginUserActions user %s is not active.", $user_id));
                throw new AuthenticationLockedUserLoginAttempt(
                    $user->getEmail(),
                    sprintf("User %s is locked.", $user->getEmail())
                );
            }

            //update user fields
            $user->setLastLoginDate(new \DateTime('now', new \DateTimeZone('UTC')));
            $user->resetLoginFailedAttempts();
            $user->activate();
            $user->clearResetPasswordRequests();

        });
    }

    public function issueMFAChallenge(
        User $user,
        IMFAChallengeStrategy $strategy,
        ?Client $client = null,
        bool $remember = false
    ): array {
        return $this->tx_service->transaction(function () use ($user, $strategy, $client, $remember) {
            return $strategy->issueChallenge($user, $client, $remember);
        });
    }

    public function verifyMFAChallenge(
        User $user,
        IMFAChallengeStrategy $strategy,
        string $value,
        ?Client $client = null
    ): void {
        $this->tx_service->transaction(function () use ($user, $strategy, $value, $client) {
            $strategy->verifyChallenge($user, $value, $client);
        });
    }

    public function verifyMFARecoveryCode(
        User $user,
        IMFAChallengeStrategy $strategy,
        string $inputCode
    ): void {
        $this->tx_service->transaction(function () use ($user, $strategy, $inputCode) {
            $strategy->verifyRecoveryCode($user, $inputCode);
        });
    }

    public function resendMFAChallenge(
        User $user,
        IMFAChallengeStrategy $strategy,
        ?Client $client = null,
        bool $remember = false
    ): array {
        return $this->tx_service->transaction(function () use ($user, $strategy, $client, $remember) {
            return $strategy->resendChallenge($user, $client, $remember);
        });
    }
}