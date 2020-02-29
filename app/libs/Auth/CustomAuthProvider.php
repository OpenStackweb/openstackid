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
use Auth\Exceptions\UnverifiedEmailMemberException;
use Auth\Repositories\IUserRepository;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Log;
use OpenId\Services\IUserService;
use Utils\Db\ITransactionService;
use Utils\Services\ICheckPointService;
use Auth\Exceptions\AuthenticationException;
use Auth\Exceptions\AuthenticationInvalidPasswordAttemptException;
use Auth\Exceptions\AuthenticationLockedUserLoginAttempt;
/**
 * Class CustomAuthProvider
 * Custom Authentication Provider against SS DB
 * @package Auth
 */
class CustomAuthProvider implements UserProvider
{

    /**
     * @var IAuthenticationExtensionService
     */
    private $auth_extension_service;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var ICheckPointService
     */
    private $checkpoint_service;
    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @param IUserRepository $user_repository
     * @param IAuthenticationExtensionService $auth_extension_service
     * @param IUserService $user_service
     * @param ICheckPointService $checkpoint_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IAuthenticationExtensionService $auth_extension_service,
        IUserService $user_service,
        ICheckPointService $checkpoint_service,
        ITransactionService $tx_service
    ) {

        $this->auth_extension_service = $auth_extension_service;
        $this->user_service = $user_service;
        $this->checkpoint_service = $checkpoint_service;
        $this->user_repository = $user_repository;
        $this->tx_service = $tx_service;
    }

    /**
     * Retrieve a user by their unique identifier.
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        try {
            $user = $this->user_repository->getById($identifier);
            if (!is_null($user)) {
                return $user;
            }

        } catch (Exception $ex) {
            Log::warning($ex);
            return null;
        }

        return null;
    }

    /**
     * Retrieve a user by the given credentials.
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        return $this->tx_service->transaction(function () use ($credentials) {

            $user = null;

            try
            {

                if (!isset($credentials['username']) || !isset($credentials['password']))
                {
                    throw new AuthenticationException("invalid crendentials");
                }

                $email    = $credentials['username'];
                $password = $credentials['password'];
                $user     = $this->user_repository->getByEmailOrName(trim($email));

                if (is_null($user)) //user must exists
                {
                    throw new AuthenticationException(sprintf("user %s does not exists!", $email));
                }

                if(!$user->canLogin())
                {
                    if(!$user->isEmailVerified())
                        throw new UnverifiedEmailMemberException(sprintf("user %s is not verified yet!", $email));
                    throw new AuthenticationException(sprintf("user %s does not exists!", $email));
                }

                $valid_password = $user->checkPassword($password);

                if (!$valid_password)
                {
                    throw new AuthenticationInvalidPasswordAttemptException($user->getId(),
                        sprintf("invalid login attempt for user %s ", $email));
                }

                //check user status...
                if (!$user->isActive()) {
                    Log::warning(sprintf("user %s is on lock state", $email));
                    throw new AuthenticationLockedUserLoginAttempt($email,
                        sprintf("user %s is on lock state", $email));
                }

                //update user fields
                $user->setLastLoginDate(new \DateTime('now', new \DateTimeZone('UTC')));
                $user->setLoginFailedAttempt(0);
                $user->setActive(true);
                $user->clearResetPasswordRequests();

                $auth_extensions = $this->auth_extension_service->getExtensions();

                foreach ($auth_extensions as $auth_extension)
                {
                    if(!$auth_extension instanceof IAuthenticationExtension) continue;
                    $auth_extension->process($user);
                }
            }
            catch(UnverifiedEmailMemberException $ex){
                $this->checkpoint_service->trackException($ex);
                Log::warning($ex);
                throw $ex;
            }
            catch (Exception $ex)
            {
                $this->checkpoint_service->trackException($ex);
                Log::warning($ex);
                $user = null;
            }

            return $user;
        });

    }

    /**
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     * @throws AuthenticationException
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!isset($credentials['username']) || !isset($credentials['password'])) {
            throw new AuthenticationException("invalid crendentials");
        }
        try {
            $email = $credentials['username'];
            $password = $credentials['password'];

            $user = $this->user_repository->getByEmailOrName(trim($email));

            if (!$user || !$user->canLogin() || !$user->checkPassword($password)) {
                return false;
            }

            if (is_null($user) || !$user->isActive()) {
                return false;
            }
        } catch (Exception $ex) {
            Log::warning($ex);
            return false;
        }
        return true;
    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        return $this->user_repository->getByToken($token);
    }

    /**
     * @param Authenticatable $user
     * @param string $token
     * @throws Exception
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->tx_service->transaction(function () use ($user, $token) {
            $dbUser = $this->user_repository->getById($user->getAuthIdentifier());
            if(is_null($dbUser)) return;
            $dbUser->setRememberToken($user->getRememberToken());
        });
    }
}