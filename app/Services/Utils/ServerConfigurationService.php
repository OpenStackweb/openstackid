<?php namespace Services\Utils;
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
use App\Repositories\IServerConfigurationRepository;
use App\Services\AbstractService;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Models\ServerConfiguration;
use OpenId\Services\IServerConfigurationService;
use Utils\Db\ITransactionService;
use Utils\Services\ICacheService;
use Utils\Services\IServerConfigurationService as IOpenIdServerConfigurationService;
/**
 * Class ServerConfigurationService
 * @package Services\Utils
 */
class ServerConfigurationService
    extends AbstractService
    implements IOpenIdServerConfigurationService,
    IServerConfigurationService
{

    const DefaultAssetsUrl                          = 'https://www.openstack.org/';
    const DefaultPrivateAssociationLifetime         = 120;
    const DefaultSessionAssociationLifetime         = 21600;
    const DefaultMaxFailedLoginAttempts             = 10;
    const DefaultMaxFailedLoginAttempts2ShowCaptcha = 3;
    const DefaultNonceLifetime                      = 360;

    /**
     * @var array
     */
    private $default_config_params;

    /**
     * @var IServerConfigurationRepository
     */
    private $server_config_repository;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * ServerConfigurationService constructor.
     * @param IServerConfigurationRepository $server_config_repository
     * @param ICacheService $cache_service
     * @param ITransactionService $tx_service
     */
    public function __construct(
        IServerConfigurationRepository $server_config_repository,
        ICacheService $cache_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->cache_service = $cache_service;
        $this->server_config_repository = $server_config_repository;
        $this->default_config_params = [];
        //default config values

        //general
        $this->default_config_params["MaxFailed.Login.Attempts"] = Config::get('server.MaxFailed_Login_Attempts', 10);
        $this->default_config_params["SupportEmail"]             = Config::get('server.support_email', 'info@openstack.org');

        $this->default_config_params["MaxFailed.LoginAttempts.2ShowCaptcha"] = Config::get('server.MaxFailed_LoginAttempts_2ShowCaptcha',
            3);
        $this->default_config_params["Assets.Url"] = Config::get('server.assets_base_url', self::DefaultAssetsUrl );
        // remember me cookie lifetime (minutes)
        $this->default_config_params["Remember.ExpirationTime"] = Config::get('Remember.ExpirationTime', 120);

        //openid
        $this->default_config_params["OpenId.Private.Association.Lifetime"] = Config::get('server.OpenId_Private_Association_Lifetime',
            240);
        $this->default_config_params["OpenId.Session.Association.Lifetime"] = Config::get('server.OpenId_Session_Association_Lifetime',
            21600);
        $this->default_config_params["OpenId.Nonce.Lifetime"] = Config::get('server.OpenId_Nonce_Lifetime', 360);

        //policies
        $this->default_config_params["BlacklistSecurityPolicy.BannedIpLifeTimeSeconds"] = Config::get('server.BlacklistSecurityPolicy_BannedIpLifeTimeSeconds',
            21600);
        $this->default_config_params["BlacklistSecurityPolicy.MinutesWithoutExceptions"] = Config::get('server.BlacklistSecurityPolicy_MinutesWithoutExceptions',
            5);;
        $this->default_config_params["BlacklistSecurityPolicy.ReplayAttackExceptionInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_ReplayAttackExceptionInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxInvalidNonceAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxInvalidNonceAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.InvalidNonceInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_InvalidNonceInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxInvalidOpenIdMessageExceptionAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxInvalidOpenIdMessageExceptionAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.InvalidOpenIdMessageExceptionInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_InvalidOpenIdMessageExceptionInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxOpenIdInvalidRealmExceptionAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxOpenIdInvalidRealmExceptionAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.OpenIdInvalidRealmExceptionInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_OpenIdInvalidRealmExceptionInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxInvalidOpenIdMessageModeAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxInvalidOpenIdMessageModeAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.InvalidOpenIdMessageModeInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_InvalidOpenIdMessageModeInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxInvalidOpenIdAuthenticationRequestModeAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxInvalidOpenIdAuthenticationRequestModeAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.InvalidOpenIdAuthenticationRequestModeInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_InvalidOpenIdAuthenticationRequestModeInitialDelay',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.MaxAuthenticationExceptionAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxAuthenticationExceptionAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.AuthenticationExceptionInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_AuthenticationExceptionInitialDelay',
            20);
        $this->default_config_params["BlacklistSecurityPolicy.MaxInvalidAssociationAttempts"] = Config::get('server.BlacklistSecurityPolicy_MaxInvalidAssociationAttempts',
            10);
        $this->default_config_params["BlacklistSecurityPolicy.InvalidAssociationInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_InvalidAssociationInitialDelay',
            20);

        //oauth2
        $this->default_config_params["OAuth2.Enable"] = Config::get('server.OAuth2_Enable', false);
        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.MaxAuthCodeReplayAttackAttempts"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_MaxAuthCodeReplayAttackAttempts',
            3);
        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.AuthCodeReplayAttackInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_AuthCodeReplayAttackInitialDelay',
            10);

        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.MaxInvalidAuthorizationCodeAttempts"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_MaxInvalidAuthorizationCodeAttempts',
            3);
        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.InvalidAuthorizationCodeInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_InvalidAuthorizationCodeInitialDelay',
            10);

        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.MaxInvalidBearerTokenDisclosureAttempt"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_MaxInvalidBearerTokenDisclosureAttempt',
            3);
        $this->default_config_params["BlacklistSecurityPolicy.OAuth2.BearerTokenDisclosureAttemptInitialDelay"] = Config::get('server.BlacklistSecurityPolicy_OAuth2_BearerTokenDisclosureAttemptInitialDelay',
            10);


        $this->default_config_params["OAuth2.AuthorizationCode.Lifetime"] = Config::get('server.OAuth2_AuthorizationCode_Lifetime', 240);
        $this->default_config_params["OAuth2.AccessToken.Lifetime"] = Config::get('server.OAuth2_AccessToken_Lifetime', 3600);
        $this->default_config_params["OAuth2.IdToken.Lifetime"] = Config::get('server.OAuth2_IdToken_Lifetime', 3600);
        //infinite by default
        $this->default_config_params["OAuth2.RefreshToken.Lifetime"] = Config::get('server.OAuth2_RefreshToken_Lifetime', 0);
        //revoked lifetimes
        $this->default_config_params["OAuth2.AccessToken.Revoked.Lifetime"] = Config::get('server.OAuth2_AccessToken_Revoked_Lifetime', 3600);
        $this->default_config_params["OAuth2.AccessToken.Void.Lifetime"] = Config::get('server.OAuth2_AccessToken_Void_Lifetime', 3600);
        $this->default_config_params["OAuth2.RefreshToken.Revoked.Lifetime"] = Config::get('server.OAuth2_RefreshToken_Revoked_Lifetime', 3600);

        //oauth2 policy defaults
        $this->default_config_params["OAuth2SecurityPolicy.MinutesWithoutExceptions"]            = Config::get('server.OAuth2SecurityPolicy_MinutesWithoutExceptions', 2);
        $this->default_config_params["OAuth2SecurityPolicy.MaxBearerTokenDisclosureAttempts"]    = Config::get('server.OAuth2SecurityPolicy_MaxBearerTokenDisclosureAttempts', 5);
        $this->default_config_params["OAuth2SecurityPolicy.MaxInvalidClientExceptionAttempts"]   = Config::get('server.OAuth2SecurityPolicy_MaxInvalidClientExceptionAttempts', 10);
        $this->default_config_params["OAuth2SecurityPolicy.MaxInvalidRedeemAuthCodeAttempts"]    = Config::get('server.OAuth2SecurityPolicy_MaxInvalidRedeemAuthCodeAttempts', 10);
        $this->default_config_params["OAuth2SecurityPolicy.MaxInvalidClientCredentialsAttempts"] = Config::get('server.OAuth2SecurityPolicy_MaxInvalidClientCredentialsAttempts', 5);
        //ssl
        $this->default_config_params["SSL.Enable"] = Config::get('server.SSL_Enable', true);
    }

    public function getUserIdentityEndpointURL($identifier)
    {
        return action("UserController@getIdentity", array("identifier" => $identifier));
    }

    public function getOPEndpointURL()
    {
        return action("OpenId\OpenIdProviderController@endpoint");
    }

    /**
     * get config value from cache and if not in cache check for it on table server_configuration
     * @param $key
     * @return mixed
     */
    public function getConfigValue($key)
    {

        return $this->tx_service->transaction(function () use ($key) {
            $res = null;
            try {
                if (!$this->cache_service->exists($key)) {
                    if (!is_null($conf = $this->server_config_repository->getByKey($key))) {
                        $this->cache_service->addSingleValue($key, $conf->getValue());
                        return $conf->getValue();
                    }

                    if (isset($this->default_config_params[$key])) {
                        $this->cache_service->addSingleValue($key, $this->default_config_params[$key]);
                        return $this->default_config_params[$key];
                    }

                    return null;
                }

                $res = $this->cache_service->getSingleValue($key);

            }
            catch (Exception $ex) {
                Log::error($ex);
                if (isset($this->default_config_params[$key])) {
                    $res = $this->default_config_params[$key];
                }
            }

            return $res;
        });

    }

    public function getAllConfigValues()
    {
        // TODO: Implement getAllConfigValues() method.
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed|void
     * @throws Exception
     */
    public function saveConfigValue(string $key, $value)
    {
        $this->tx_service->transaction(function () use ($key, $value) {

            $conf = $this->server_config_repository->getByKey($key);

            if (is_null($conf)) {
                $conf = new ServerConfiguration();
                $conf->setKey($key);
                $conf->setValue($value);
                $this->server_config_repository->add($conf);
            } else {
                $conf->setValue($value);
            }

            $this->cache_service->delete($key);
        });
    }

    /**
     * @return string
     */
    public function getSiteUrl():string
    {
        $request = request();
        if(!is_null($request))
            return $request->getSchemeAndHttpHost();
        return Config::get('app.url');
    }
}
