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
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use OAuth2\Models\IClient;
use OAuth2\Services\IPrincipalService;
use OpenId\Models\IOpenIdUser;
use OpenId\Services\IUserService;
use utils\Base64UrlRepresentation;
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
final class AuthService implements IAuthService
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
     * AuthService constructor.
     * @param IUserRepository $user_repository
     * @param IPrincipalService $principal_service
     * @param IUserService $user_service
     * @param ICacheService $cache_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IPrincipalService $principal_service,
        IUserService $user_service,
        ICacheService $cache_service
    )
    {
        $this->user_repository   = $user_repository;
        $this->principal_service = $principal_service;
        $this->user_service      = $user_service;
        $this->cache_service     = $cache_service;
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
    public function getCurrentUser():?User
    {
        return Auth::user();
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool $remember_me
     * @return mixed
     */
    public function login($username, $password, $remember_me)
    {
        Log::debug("AuthService::login");
        $res = Auth::attempt(['username' => $username, 'password' => $password], $remember_me);

        if ($res)
        {
            Log::debug("AuthService::login: clearing principal");
            $this->principal_service->clear();
            $this->principal_service->register
            (
                $this->getCurrentUser()->getId(),
                time()
            );
        }

        return $res;
    }

    public function logout()
    {
        $this->invalidateSession();
        Auth::logout();
        $this->principal_service->clear();
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
        if (Session::has("openid.authorization.response"))
        {
            $value = Session::get("openid.authorization.response");

            return $value;
        }

        return IAuthService::AuthorizationResponse_None;
    }


    public function clearUserAuthorizationResponse()
    {
        if (Session::has("openid.authorization.response"))
        {
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
    public function getUserByOpenId(string $openid):?User
    {
        return $this->user_repository->getByIdentifier($openid);
    }

    /**
     * @param string $username
     * @return null|User
     */
    public function getUserByUsername(string $username):?User
    {
        return $this->user_repository->getByEmailOrName($username);
    }

    /**
     * @param int $id
     * @return null|User
     */
    public function getUserById(int $id):?User
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
        if (Session::has("openstackid.authentication.response"))
        {
            Session::remove("openstackid.authentication.response");
            Session::save();
        }
    }

    /**
     * @param  string $user_id
     * @return string
     */
    public function unwrapUserId(string $user_id):string
    {
        // first try to get user by raw id
        $user = $this->getUserById(intval($user_id));

        if(!is_null($user))
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
    public function wrapUserId(int $user_id, IClient $client):string
    {
       if($client->getSubjectType() === IClient::SubjectType_Public)
           return $user_id;

        $wrapped_name = sprintf('%s:%s', $client->getClientId(), $user_id);
        return $this->encrypt($wrapped_name);
    }

    /**
     * @param string $value
     * @return String
     */
    private function encrypt(string $value):string
    {
        return base64_encode(Crypt::encrypt($value));
    }

    /**
     * @param string $value
     * @return String
     */
    private function decrypt(string $value):string
    {
        $value = base64_decode($value);
        return Crypt::decrypt($value);
    }

    /**
     * @return string
     */
    public function getSessionId():string
    {
       return Session::getId();
    }

    /**
     * @param $client_id
     * @return void
     */
    public function registerRPLogin(string $client_id):void
    {

        try {
            $rps = Cookie::get(IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME, "");
            $zlib = CompressionAlgorithms_Registry::getInstance()->get(CompressionAlgorithmsNames::ZLib);

            if (!empty($rps)) {
                $rps = $this->decrypt($rps);
                $rps = $zlib->uncompress($rps);
                $rps .= '|';
            }
            if(is_null($rps)) $rps = "";

            if (!str_contains($rps, $client_id))
                $rps .= $client_id;

            $rps = $zlib->compress($rps);
            $rps = $this->encrypt($rps);
        }
        catch(Exception $ex){
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
    public function getLoggedRPs():array
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
    public function reloadSession(string $jti):void
    {
        Log::debug(sprintf("AuthService::reloadSession jti %s", $jti ));
        $session_id = $this->cache_service->getSingleValue($jti);

        Log::debug(sprintf("AuthService::reloadSession session_id %s", $session_id ));
        if(empty($session_id))
            throw new ReloadSessionException('session not found!');

        if($this->cache_service->exists($session_id."invalid")){
            // session was marked as void, check if we are authenticated
            if(!Auth::check())
                throw new ReloadSessionException('user not found!');
        }

        Session::setId(Crypt::decrypt($session_id));
        Session::start();
        if(!Auth::check())
        {
            $user_id      = $this->principal_service->get()->getUserId();
            Log::debug(sprintf("AuthService::reloadSession user_id %s", $user_id ));
            $user         = $this->getUserById($user_id);
            if(is_null($user))
                throw new ReloadSessionException('user not found!');
            Auth::login($user);
        }
    }

    /**
     * @param string $client_id
     * @param int $id_token_lifetime
     * @return string
     */
    public function generateJTI(string $client_id, int $id_token_lifetime):string {
        $session_id  = Crypt::encrypt(Session::getId());
        $encoder     = new Base64UrlRepresentation();
        $jti         = $encoder->encode(hash('sha512', $session_id.$client_id, true));

        $this->cache_service->addSingleValue($jti, $session_id, $id_token_lifetime);

        return $jti;
    }


    public function invalidateSession():void {
        $session_id  = Crypt::encrypt(Session::getId());
        $this->cache_service->addSingleValue($session_id."invalid", $session_id);
    }
}