<?php namespace Auth;
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
use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\InvalidOTPException;
use OAuth2\Models\IClient;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IUserService;
use App\Services\Auth\IUserService as IAuthUserService;
use utils\Base64UrlRepresentation;
use Utils\Db\ITransactionService;
use Utils\Services\IAuthService;
use Utils\Services\ICacheService;
use jwe\compression_algorithms\CompressionAlgorithms_Registry;
use jwe\compression_algorithms\CompressionAlgorithmsNames;
use Exception;
use Illuminate\Support\Facades\Log;

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
     * @param ICacheService $cache_service
     * @param IAuthUserService $auth_user_service
     * @params ISecurityContextService $security_context_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository         $user_repository,
        IOAuth2OTPRepository    $otp_repository,
        IPrincipalService       $principal_service,
        IUserService            $user_service,
        ICacheService           $cache_service,
        IAuthUserService        $auth_user_service,
        ISecurityContextService $security_context_service,
        ITransactionService     $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
        $this->principal_service = $principal_service;
        $this->user_service = $user_service;
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
     * @param string $username
     * @param string $password
     * @param bool $remember_me
     * @return bool
     * @throws AuthenticationException
     */
    public function login(string $username, string $password, bool $remember_me): bool
    {
        Log::debug("AuthService::login");

        $this->last_login_error = "";
        if (!Auth::attempt(['username' => $username, 'password' => $password], $remember_me)) {
            throw new AuthenticationException("We are sorry, your username or password does not match an existing record.");
        }

        Log::debug("AuthService::login: clearing principal");
        $this->principal_service->clear();
        $this->principal_service->register
        (
            $this->getCurrentUser()->getId(),
            time()
        );

        return true;
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

        $otp = $this->tx_service->transaction(function () use ($otpClaim, $client, $remember) {

            // find by value, connection and user name
            $otp = $this->otp_repository->getByValueConnectionAndUserName(
                $otpClaim->getValue(),
                $otpClaim->getConnection(),
                $otpClaim->getUserName(),
                $client
            );

            if (is_null($otp)) {
                // otp no emitted
                Log::warning
                (
                    sprintf
                    (
                        "AuthService::loginWithOTP otp %s user %s grant not found",
                        $otpClaim->getValue(),
                        $otpClaim->getUserName()
                    )
                );

                throw new AuthenticationException("Non existent OTP.");
            }

            $otp->logRedeemAttempt();
            return $otp;
        });

        return $this->tx_service->transaction(function () use ($otp, $otpClaim, $client, $remember) {

            if (!$otp->isAlive()) {
                throw new AuthenticationException("OTP is expired.");
            }

            if (!$otp->isValid()) {
                throw new AuthenticationException("OTP is not valid.");
            }

            if ($otp->getValue() != $otpClaim->getValue()) {
                throw new AuthenticationException("OTP mismatch.");
            }

            if(!empty($otpClaim->getScope()) && !$otp->allowScope($otpClaim->getScope()))
                throw new InvalidOTPException("OTP Requested scopes escalates former scopes.");

            if (($otp->hasClient() && is_null($client)) ||
                ($otp->hasClient() && !is_null($client) && $client->getClientId() != $otp->getClient()->getClientId())) {
                throw new AuthenticationException("OTP audience mismatch.");
            }

            $user = $this->getUserByUsername($otp->getUserName());

            if (is_null($user)) {
                // we need to create a new one ( auto register)

                Log::debug(sprintf("AuthService::loginWithOTP user %s does not exists ...", $otp->getUserName()));

                $user = $this->auth_user_service->registerUser
                (
                    [
                        'email' => $otp->getUserName(),
                        'email_verified' => true,
                        // dont send email
                        'send_email_verified_notice' => false
                    ],
                    $otp
                );
            }

            $otp->setAuthTime(time());
            $otp->setUserId($user->getId());
            $otp->redeem();

            // revoke former ones
            $grants2Revoke = $this->otp_repository->getByUserNameNotRedeemed
            (
                $otpClaim->getUserName(),
                $client
            );

            foreach ($grants2Revoke as $otp2Revoke){
                try {
                    Log::debug(sprintf("AuthService::loginWithOTP revoking otp %s ", $otp2Revoke->getValue()));
                    if($otp2Revoke->getValue() !== $otpClaim->getValue())
                        $otp2Revoke->redeem();
                }
                catch (Exception $ex){
                    Log::warning($ex);
                }
            }

            Auth::login($user, $remember);

            return $otp;
        });
    }

    public function logout()
    {
        Log::debug("AuthService::logout");
        $this->invalidateSession();
        $this->principal_service->clear();
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

    /**
     * @param string $openid
     * @return User|null
     */
    public function getUserByOpenId(string $openid): ?User
    {
        return $this->user_repository->getByIdentifier($openid);
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
     * @param int $id
     * @return null|User
     */
    public function getUserById(int $id): ?User
    {
        return $this->user_repository->getById($id);
    }

    // Authentication

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
        }
        catch (Exception $ex){
            Log::warning($ex);
        }
        return $user_id;
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
     * @param string $value
     * @return String
     */
    private function encrypt(string $value): string
    {
        return base64_encode(Crypt::encrypt($value));
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
            if (is_null($rps)) $rps = "";

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
        }
        catch (Exception $ex){
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

    public function invalidateSession(): void
    {
        $session_id = Crypt::encrypt(Session::getId());
        $this->cache_service->addSingleValue($session_id . "invalid", $session_id);
    }

}