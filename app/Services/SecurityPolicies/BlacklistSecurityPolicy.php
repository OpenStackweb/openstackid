<?php namespace Services\SecurityPolicies;
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

use App\Http\Utils\IUserIPHelperProvider;
use App\libs\Auth\Repositories\IBannedIPRepository;
use App\libs\Auth\Repositories\IUserExceptionTrailRepository;
use Auth\Exceptions\AuthenticationException;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\libs\Auth\Repositories\IWhiteListedIPRepository;
use OAuth2\Exceptions\BearerTokenDisclosureAttemptException;
use OAuth2\Exceptions\InvalidAuthorizationCodeException;
use OAuth2\Repositories\IResourceServerRepository;
use OpenId\Exceptions\InvalidAssociation;
use OpenId\Exceptions\InvalidNonce;
use OpenId\Exceptions\InvalidOpenIdAuthenticationRequestMode;
use OpenId\Exceptions\InvalidOpenIdMessageException;
use OpenId\Exceptions\InvalidOpenIdMessageMode;
use OpenId\Exceptions\OpenIdInvalidRealmException;
use OpenId\Exceptions\ReplayAttackException;
use Utils\Db\ITransactionService;
use Utils\Exceptions\UnacquiredLockException;
use Utils\Services\ICacheService;
use Utils\Services\ILockManagerService;
use Utils\Services\IServerConfigurationService;
/**
 * Class BlacklistSecurityPolicy
 * implements check point security pattern
 * @package Services\SecurityPolicies
 */
final class BlacklistSecurityPolicy extends AbstractBlacklistSecurityPolicy
{

    /**
     * @var array
     */
    private $exception_dictionary = [];

    /**
     * @var IResourceServerRepository
     */
    private $resource_server_repository;

    /**
     * @var IWhiteListedIPRepository
     */
    private $white_listed_ip_repository;

    /**
     * @var IUserExceptionTrailRepository
     */
    private $user_exception_trail_repository;

    /**
     * BlacklistSecurityPolicy constructor.
     * @param IWhiteListedIPRepository $white_listed_ip_repository
     * @param IServerConfigurationService $server_configuration_service
     * @param ILockManagerService $lock_manager_service
     * @param ICacheService $cache_service
     * @param IResourceServerRepository $resource_server_repository
     * @param IBannedIPRepository $banned_ip_repository
     * @param IUserExceptionTrailRepository $user_exception_trail_repository
     * @param IUserIPHelperProvider $ip_helper
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IWhiteListedIPRepository      $white_listed_ip_repository,
        IServerConfigurationService   $server_configuration_service,
        ILockManagerService           $lock_manager_service,
        ICacheService                 $cache_service,
        IResourceServerRepository     $resource_server_repository,
        IBannedIPRepository           $banned_ip_repository,
        IUserExceptionTrailRepository $user_exception_trail_repository,
        IUserIPHelperProvider         $ip_helper,
        ITransactionService           $tx_service
    )
    {
        parent::__construct
        (
            $banned_ip_repository,
            $server_configuration_service,
            $lock_manager_service,
            $cache_service,
            $ip_helper,
            $tx_service
        );

        $this->resource_server_repository = $resource_server_repository;

        $this->user_exception_trail_repository = $user_exception_trail_repository;
        // here we configure on which exceptions are we interested and the max occurrence attempts and initial delay on tar pit for
        // offending IP address
        $this->exception_dictionary = [
            ReplayAttackException::class => array(
                'BlacklistSecurityPolicy.MaxReplayAttackExceptionAttempts',
                'BlacklistSecurityPolicy.ReplayAttackExceptionInitialDelay'
            ),
            InvalidNonce::class => array(
                'BlacklistSecurityPolicy.MaxInvalidNonceAttempts',
                'BlacklistSecurityPolicy.InvalidNonceInitialDelay'
            ),
            InvalidOpenIdMessageException::class => array(
                'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageExceptionAttempts',
                'BlacklistSecurityPolicy.InvalidOpenIdMessageExceptionInitialDelay'
            ),
            OpenIdInvalidRealmException::class => array(
                'BlacklistSecurityPolicy.MaxOpenIdInvalidRealmExceptionAttempts',
                'BlacklistSecurityPolicy.OpenIdInvalidRealmExceptionInitialDelay'
            ),
            InvalidOpenIdMessageMode::class => array(
                'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageModeAttempts',
                'BlacklistSecurityPolicy.InvalidOpenIdMessageModeInitialDelay'
            ),
            InvalidOpenIdAuthenticationRequestMode::class => array(
                'BlacklistSecurityPolicy.MaxInvalidOpenIdAuthenticationRequestModeAttempts',
                'BlacklistSecurityPolicy.InvalidOpenIdAuthenticationRequestModeInitialDelay'
            ),
            InvalidAssociation::class => array(
                'BlacklistSecurityPolicy.MaxInvalidAssociationAttempts',
                'BlacklistSecurityPolicy.InvalidAssociationInitialDelay'
            ),
            AuthenticationException::class => array(
                'BlacklistSecurityPolicy.MaxAuthenticationExceptionAttempts',
                'BlacklistSecurityPolicy.AuthenticationExceptionInitialDelay'
            ),
            \OAuth2\Exceptions\ReplayAttackException::class => [
                'BlacklistSecurityPolicy.OAuth2.MaxAuthCodeReplayAttackAttempts',
                'BlacklistSecurityPolicy.OAuth2.AuthCodeReplayAttackInitialDelay'
            ],
            InvalidAuthorizationCodeException::class => [
                'BlacklistSecurityPolicy.OAuth2.MaxInvalidAuthorizationCodeAttempts',
                'BlacklistSecurityPolicy.OAuth2.InvalidAuthorizationCodeInitialDelay'
            ],
            BearerTokenDisclosureAttemptException::class => [
                'BlacklistSecurityPolicy.OAuth2.MaxInvalidBearerTokenDisclosureAttempt',
                'BlacklistSecurityPolicy.OAuth2.BearerTokenDisclosureAttemptInitialDelay'
            ],
        ];
        $this->white_listed_ip_repository = $white_listed_ip_repository;
    }

    /**
     * Check policy
     * @return bool
     */
    public function check()
    {

        return $this->tx_service->transaction(function(){
            $res            = true;
            $remote_address = $this->ip_helper->getCurrentUserIpAddress();
            try {

                if($this->isIPAddressWhitelisted($remote_address)) return true;

                //check if banned ip is on cache ...
                if ($this->cache_service->incCounterIfExists($remote_address)) {
                    $this->counter_measure->trigger();
                    return false;
                }
                //check on db
                if (!is_null($banned_ip = $this->banned_ip_repository->getByIp($remote_address))) {
                    // banned ip exists on DB, set lock
                    $this->lock_manager_service->acquireLock("lock.ip." . $remote_address);
                    try {
                        //check lifetime
                        $issued = $banned_ip->getCreatedAt();
                        $utc_now = gmdate("Y-m-d H:i:s", time());
                        $utc_now = DateTime::createFromFormat("Y-m-d H:i:s", $utc_now);
                        //get time lived on seconds
                        $time_lived_seconds = abs($utc_now->getTimestamp() - $issued->getTimestamp());
                        if ($time_lived_seconds >= intval($this->server_configuration_service->getConfigValue("BlacklistSecurityPolicy.BannedIpLifeTimeSeconds"))) {
                            //void banned ip
                            $this->banned_ip_repository->delete($banned_ip);
                            return true;
                        }

                        $banned_ip->updateHits();
                        //save ip on cache
                        $this->cache_service->addSingleValue($banned_ip->getIp(), $banned_ip->getHits(),
                            intval($this->server_configuration_service->getConfigValue("BlacklistSecurityPolicy.BannedIpLifeTimeSeconds") - $time_lived_seconds));
                    } catch (Exception $ex) {
                        Log::error($ex);
                    }
                    //release lock
                    $this->lock_manager_service->releaseLock("lock.ip." . $remote_address);
                    $this->counter_measure->trigger();

                    return false;
                }
            } catch (UnacquiredLockException $ex1) {
                Log::error($ex1);
                $res = false;
            } catch (Exception $ex) {
                Log::error($ex);
                $res = false;
            }
            return $res;
        });

    }

    /**
     * @param Exception $ex
     * @throws Exception
     * @return $this
     */
    public function apply(Exception $ex)
    {
        return $this->tx_service->transaction(function() use($ex){
            try
            {
                $remote_ip       = $this->ip_helper->getCurrentUserIpAddress();
                $exception_class = get_class($ex);

                //check exception count by type on last "MinutesWithoutExceptions" minutes...
                $exception_count = $this->user_exception_trail_repository->getCountByIPTypeOfLatestUserExceptions
                (
                    $remote_ip,
                    $exception_class,
                    $this->server_configuration_service->getConfigValue("BlacklistSecurityPolicy.MinutesWithoutExceptions")
                );

                if (array_key_exists($exception_class, $this->exception_dictionary))
                {
                    $params = $this->exception_dictionary[$exception_class];
                    $max_attempts = !is_null($params[0]) && !empty($params[0]) ? intval($this->server_configuration_service->getConfigValue($params[0])) : 0;

                    Log::info
                    (
                        sprintf
                        (
                            'IP %s, - exception_class %s - exception_count %s - max allowed attempts %s',
                            $remote_ip,
                            $exception_class,
                            $exception_count,
                            $max_attempts
                        )
                    );

                    $initial_delay_on_tar_pit = intval($this->server_configuration_service->getConfigValue($params[1]));
                    if (!$this->isIPAddressWhiteListed($remote_ip) && $exception_count >= $max_attempts)
                    {
                        Log::warning
                        (
                            sprintf
                            (
                                'banning IP %s, - exception_class %s - exception_count %s - max allowed attempts %s',
                                $remote_ip,
                                $exception_class,
                                $exception_count,
                                $max_attempts
                            )
                        );

                        $this->createBannedIP($initial_delay_on_tar_pit, $exception_class);
                    }
                }
            }
            catch (Exception $ex)
            {
                Log::error($ex);
                throw $ex;
            }
            return $this;
        });
    }

    /**
     * @param string $ip
     * @return bool
     */
    private function isIPAddressWhiteListed(string $ip): bool
    {
        $banning_enable = boolval(Config::get("server.banning_enable", true));
        if(!$banning_enable) return true;
        $cache_value = $this->cache_service->getSingleValue($ip.".whitelisted");
        if(!empty($cache_value)) return true;

        $resource_server = $this->resource_server_repository->getByIp($ip);
        $white_listed_ip = $this->white_listed_ip_repository->getByIp($ip);

        $white_listed    = !is_null($resource_server) || !is_null($white_listed_ip);
        if($white_listed)
            $this->cache_service->setSingleValue($ip.".whitelisted", $ip.".whitelisted");

        return $white_listed;
    }

}




