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
use App\libs\OAuth2\Repositories\IOAuth2TrailExceptionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use OAuth2\Exceptions\BearerTokenDisclosureAttemptException;
use OAuth2\Exceptions\InvalidClientCredentials;
use OAuth2\Exceptions\InvalidClientException;
use OAuth2\Exceptions\InvalidRedeemAuthCodeException;
use OAuth2\Exceptions\OAuth2ClientBaseException;
use OAuth2\Repositories\IClientRepository;
use Utils\Db\ITransactionService;
use Utils\Services\ISecurityPolicy;
use Utils\Services\ISecurityPolicyCounterMeasure;
use Models\OAuth2\OAuth2TrailException;
use Utils\Services\IServerConfigurationService;
/**
 * Class OAuth2SecurityPolicy
 * @package Services\SecurityPolicies
 */
class OAuth2SecurityPolicy implements ISecurityPolicy {

    /**
     * @var ISecurityPolicyCounterMeasure
     */
    protected $counter_measure;
    /**
     * @var array
     */
    private $exception_dictionary = [];
    /**
     * @var IServerConfigurationService
     */
    private $server_configuration_service;
    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IUserIPHelperProvider
     */
    private $ip_helper;

    /**
     * @var IOAuth2TrailExceptionRepository
     */
    private $user_exception_trail_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * OAuth2SecurityPolicy constructor.
     * @param IUserIPHelperProvider $ip_helper
     * @param IServerConfigurationService $server_configuration_service
     * @param IClientRepository $client_repository
     * @param IOAuth2TrailExceptionRepository $user_exception_trail_repository
     */
    public function __construct
    (
        IUserIPHelperProvider $ip_helper,
        IServerConfigurationService $server_configuration_service,
        IClientRepository $client_repository,
        IOAuth2TrailExceptionRepository $user_exception_trail_repository,
        ITransactionService $tx_service
    )
    {
        $this->server_configuration_service    = $server_configuration_service;
	    $this->client_repository               = $client_repository;
	    $this->ip_helper                       = $ip_helper;
	    $this->user_exception_trail_repository = $user_exception_trail_repository;
	    $this->tx_service                      = $tx_service;

        $this->exception_dictionary = [
            BearerTokenDisclosureAttemptException::class => ['OAuth2SecurityPolicy.MaxBearerTokenDisclosureAttempts'],
            InvalidClientException::class                => ['OAuth2SecurityPolicy.MaxInvalidClientExceptionAttempts'],
            InvalidRedeemAuthCodeException::class        => ['OAuth2SecurityPolicy.MaxInvalidRedeemAuthCodeAttempts'],
            InvalidClientCredentials::class              => ['OAuth2SecurityPolicy.MaxInvalidClientCredentialsAttempts'],
        ];
    }
    /**
     * Check if current security policy is meet or not
     * @return boolean
     */
    public function check()
    {
        return true;
    }

    /**
     * Apply security policy on a exception
     * @param Exception $ex
     * @return $this
     */
    public function apply(Exception $ex)
    {
        return $this->tx_service->transaction(function () use ($ex) {
            try {
                if($ex instanceof OAuth2ClientBaseException){
                    $client_id = $ex->getClientId();
                    //save oauth2 exception by client id
                    if (!is_null($client_id) && !empty($client_id)){
                        $client = $this->client_repository->getClientById($client_id);
                        if(!is_null($client)) {
                            $exception_class       = get_class($ex);
                            $trail                 = new OAuth2TrailException();
                            $trail->setFromIp($this->ip_helper->getCurrentUserIpAddress());
                            $trail->setExceptionType($exception_class);
                            $trail->setClient($client);

                            $this->user_exception_trail_repository->add($trail, true);
                            //check exception count by type on last "MinutesWithoutExceptions" minutes...
                            $exception_count =  $this->user_exception_trail_repository->getCountByIPTypeOfLatestUserExceptions
                            (
                                $client,
                                $exception_class,
                                $this->server_configuration_service->getConfigValue("OAuth2SecurityPolicy.MinutesWithoutExceptions")
                            );

                            if(array_key_exists($exception_class,$this->exception_dictionary)){
                                $params                   = $this->exception_dictionary[$exception_class];
                                $max_attempts             = !is_null($params[0]) && !empty($params[0])? intval($this->server_configuration_service->getConfigValue($params[0])):0;
                                if ($exception_count >= $max_attempts)
                                    $this->counter_measure->trigger(['client_id' => $client->getId()]);
                            }
                        }
                    }
                }

            } catch (Exception $ex) {
                Log::error($ex);
            }
            return $this;
        });
    }

    /**
     * @param ISecurityPolicyCounterMeasure $counter_measure
     * @return $this
     */
    public function setCounterMeasure(ISecurityPolicyCounterMeasure $counter_measure)
    {
        $this->counter_measure = $counter_measure;
        return $this;
    }
}