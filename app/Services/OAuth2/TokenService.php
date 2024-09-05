<?php namespace Services\OAuth2;
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
use App\Jobs\AddUserAction;
use App\libs\Auth\Models\IGroupSlugs;
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use App\Models\OAuth2\Factories\OTPFactory;
use App\Services\AbstractService;
use App\Services\Auth\IUserService;
use App\Strategies\OTP\OTPChannelStrategyFactory;
use App\Strategies\OTP\OTPTypeBuilderStrategyFactory;
use Auth\Exceptions\AuthenticationException;
use Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use jwa\cryptographic_algorithms\HashFunctionAlgorithm;
use jwt\IBasicJWT;
use jwt\impl\JWTClaimSet;
use jwt\JWTClaim;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\InvalidOTPException;
use OAuth2\Models\AccessToken;
use Models\OAuth2\AccessToken as AccessTokenDB;
use Models\OAuth2\RefreshToken as RefreshTokenDB;
use OAuth2\Builders\IdTokenBuilder;
use OAuth2\Exceptions\AbsentClientException;
use OAuth2\Exceptions\AbsentCurrentUserException;
use OAuth2\Exceptions\ExpiredAccessTokenException;
use OAuth2\Exceptions\InvalidAccessTokenException;
use OAuth2\Exceptions\InvalidAuthorizationCodeException;
use OAuth2\Exceptions\InvalidClientCredentials;
use OAuth2\Exceptions\InvalidGrantTypeException;
use OAuth2\Exceptions\ReplayAttackAuthCodeException;
use OAuth2\Exceptions\ReplayAttackException;
use OAuth2\Exceptions\ReplayAttackRefreshTokenException;
use OAuth2\Exceptions\RevokedAccessTokenException;
use OAuth2\Exceptions\RevokedRefreshTokenException;
use OAuth2\Models\AuthorizationCode;
use OAuth2\Models\IClient;
use OAuth2\Models\RefreshToken;
use OAuth2\Repositories\IAccessTokenRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IRefreshTokenRepository;
use OAuth2\Repositories\IResourceServerRepository;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2PasswordlessAuthenticationRequest;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\ITokenService;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\IUserConsentService;
use Services\OAuth2\ResourceServer\UserService;
use utils\Base64UrlRepresentation;
use utils\ByteUtil;
use Utils\Db\ITransactionService;
use Utils\Exceptions\ConfigurationException;
use Utils\Exceptions\UnacquiredLockException;
use Utils\IPHelper;
use utils\json_types\JsonValue;
use utils\json_types\NumericDate;
use utils\json_types\StringOrURI;
use Utils\Model\AbstractIdentifier;
use Utils\Services\IAuthService;
use Utils\Services\ICacheService;
use Utils\Services\IdentifierGenerator;
use Utils\Services\ILockManagerService;
use Utils\Services\IServerConfigurationService;
use Zend\Crypt\Hash;
use Exception;

/**
 * Class TokenService
 * Provides all Tokens related operations (create, get and revoke)
 * @package Services\OAuth2
 */
final class TokenService extends AbstractService implements ITokenService
{
    const ClientAccessTokenPrefixList = '.atokens';
    const ClientAuthCodePrefixList = '.acodes';

    const ClientAuthCodeQty = '.acodes.qty';
    const ClientAuthCodeQtyLifetime = 86400;

    const ClientAccessTokensQty = '.atokens.qty';
    const ClientAccessTokensQtyLifetime = 86400;

    const ClientRefreshTokensQty = '.rtokens.qty';
    const ClientRefreshTokensQtyLifetime = 86400;

    //services

    /**
     * @var IClientService
     */
    private $client_service;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var ILockManagerService
     */
    private $lock_manager_service;
    /**
     * @var IServerConfigurationService
     */
    private $configuration_service;
    /**
     * @var ICacheService
     */
    private $cache_service;
    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IUserConsentService
     */
    private $user_consent_service;
    /**
     * @var IdentifierGenerator
     */
    private $identifier_generator;

    /**
     * @var IServerPrivateKeyRepository
     */
    private $server_private_key_repository;

    /**
     * @var IClientJWKSetReader
     */
    private $jwk_set_reader_service;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @var IPrincipalService
     */
    private $principal_service;

    /**
     * @var IdTokenBuilder
     */
    private $id_token_builder;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IAccessTokenRepository
     */
    private $access_token_repository;

    /**
     * @var IRefreshTokenRepository
     */
    private $refresh_token_repository;

    /**
     * @var IResourceServerRepository
     */
    private $resource_server_repository;
    /**
     * @var IApiScopeService
     */
    private $scope_service;

    /**
     * @var IUserIPHelperProvider
     */
    private $ip_helper;

    /**
     * @var IOAuth2OTPRepository
     */
    private $otp_repository;

    /**
     * TokenService constructor.
     * @param IClientService $client_service
     * @param ILockManagerService $lock_manager_service
     * @param IServerConfigurationService $configuration_service
     * @param ICacheService $cache_service
     * @param IAuthService $auth_service
     * @param IUserConsentService $user_consent_service
     * @param IdentifierGenerator $identifier_generator
     * @param IServerPrivateKeyRepository $server_private_key_repository
     * @param IClientJWKSetReader $jwk_set_reader_service
     * @param ISecurityContextService $security_context_service
     * @param IPrincipalService $principal_service
     * @param IdTokenBuilder $id_token_builder
     * @param IClientRepository $client_repository
     * @param IAccessTokenRepository $access_token_repository
     * @param IRefreshTokenRepository $refresh_token_repository
     * @param IResourceServerRepository $resource_server_repository
     * @param IUserIPHelperProvider $ip_helper
     * @param IApiScopeService $scope_service
     * @param IUserService $user_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IClientService $client_service,
        ILockManagerService $lock_manager_service,
        IServerConfigurationService $configuration_service,
        ICacheService $cache_service,
        IAuthService $auth_service,
        IUserConsentService $user_consent_service,
        IdentifierGenerator $identifier_generator,
        IServerPrivateKeyRepository $server_private_key_repository,
        IClientJWKSetReader $jwk_set_reader_service,
        ISecurityContextService $security_context_service,
        IPrincipalService $principal_service,
        IdTokenBuilder $id_token_builder,
        IClientRepository $client_repository,
        IAccessTokenRepository $access_token_repository,
        IRefreshTokenRepository $refresh_token_repository,
        IResourceServerRepository $resource_server_repository,
        IOAuth2OTPRepository $otp_repository,
        IUserIPHelperProvider $ip_helper,
        IApiScopeService $scope_service,
        IUserService $user_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);

        $this->client_service = $client_service;
        $this->lock_manager_service = $lock_manager_service;
        $this->configuration_service = $configuration_service;
        $this->cache_service = $cache_service;
        $this->auth_service = $auth_service;
        $this->user_consent_service = $user_consent_service;
        $this->identifier_generator = $identifier_generator;
        $this->server_private_key_repository = $server_private_key_repository;
        $this->jwk_set_reader_service = $jwk_set_reader_service;
        $this->security_context_service = $security_context_service;
        $this->principal_service = $principal_service;
        $this->id_token_builder = $id_token_builder;
        $this->client_repository = $client_repository;
        $this->access_token_repository = $access_token_repository;
        $this->refresh_token_repository = $refresh_token_repository;
        $this->resource_server_repository = $resource_server_repository;
        $this->ip_helper = $ip_helper;
        $this->scope_service = $scope_service;
        $this->user_service = $user_service;
        $this->otp_repository = $otp_repository;

        Event::listen('oauth2.client.delete', function ($client_id) {
            $this->revokeClientRelatedTokens($client_id);
        });

        Event::listen('oauth2.client.regenerate.secret', function ($client_id) {
            $this->revokeClientRelatedTokens($client_id);
        });
    }

    /**
     * Creates a brand new authorization code
     * @param OAuth2AuthorizationRequest $request
     * @param bool $has_previous_user_consent
     * @return AbstractIdentifier
     */
    public function createAuthorizationCode
    (
        OAuth2AuthorizationRequest $request,
        bool $has_previous_user_consent = false
    ): AbstractIdentifier
    {

        $user = $this->auth_service->getCurrentUser();
        // build current audience ...
        $audience = $this->scope_service->getStrAudienceByScopeNames
        (
            explode
            (
                OAuth2Protocol::OAuth2Protocol_Scope_Delimiter,
                $request->getScope()
            )
        );

        $nonce = null;
        $prompt = null;

        if ($request instanceof OAuth2AuthenticationRequest) {
            $nonce = $request->getNonce();
            $prompt = $request->getPrompt(true);
        }

        $code = $this->identifier_generator->generate
        (
            AuthorizationCode::create
            (
                $user->getId(),
                $request->getClientId(),
                $request->getScope(),
                $audience,
                $request->getRedirectUri(),
                $request->getAccessType(),
                $request->getApprovalPrompt(),
                $has_previous_user_consent,
                $this->configuration_service->getConfigValue('OAuth2.AuthorizationCode.Lifetime'),
                $request->getState(),
                $nonce,
                $request->getResponseType(),
                $this->security_context_service->get()->isAuthTimeRequired(),
                $this->principal_service->get()->getAuthTime(),
                $prompt,
                $request->getCodeChallenge(),
                $request->getCodeChallengeMethod()
            )
        );

        $hashed_value = Hash::compute('sha256', $code->getValue());
        //stores on cache
        $this->cache_service->storeHash($hashed_value, $code->toArray(), intval($code->getLifetime()));

        //stores brand new auth code hash value on a set by client id...
        $this->cache_service->addMemberSet($request->getClientId() . self::ClientAuthCodePrefixList, $hashed_value);

        $this->cache_service->incCounter($request->getClientId() . self::ClientAuthCodeQty, self::ClientAuthCodeQtyLifetime);

        return $code;
    }

    /**
     * @param $value
     * @return AuthorizationCode
     * @throws ReplayAttackException
     * @throws InvalidAuthorizationCodeException
     */
    public function getAuthorizationCode($value)
    {

        $hashed_value = Hash::compute('sha256', $value);

        if (!$this->cache_service->exists($hashed_value)) {
            throw new InvalidAuthorizationCodeException(sprintf("auth_code %s ", $value));
        }
        try {

            $this->lock_manager_service->acquireLock('lock.get.authcode.' . $hashed_value);
            $payload = $this->cache_service->getHash($hashed_value, AuthorizationCode::getKeys());
            $payload['value'] = $value;
            return AuthorizationCode::load($payload);
        } catch (UnacquiredLockException $ex1) {
            throw new ReplayAttackAuthCodeException
            (
                $value,
                sprintf
                (
                    "Code was already redeemed %s.",
                    $value
                )
            );
        }
    }

    /**
     * Creates a brand new access token from a give auth code
     * @param AuthorizationCode $auth_code
     * @param null $redirect_uri
     * @return AccessToken
     */
    public function createAccessToken(AuthorizationCode $auth_code, $redirect_uri = null)
    {

        $access_token = $this->identifier_generator->generate
        (
            AccessToken::create
            (
                $auth_code,
                $this->configuration_service->getConfigValue('OAuth2.AccessToken.Lifetime')
            )
        );

        return $this->tx_service->transaction(function () use (
            $auth_code,
            $redirect_uri,
            $access_token
        ) {

            $value = $access_token->getValue();
            $hashed_value = Hash::compute('sha256', $value);
            //oauth2 client id
            $client_id = $access_token->getClientId();
            $user_id = $access_token->getUserId();
            $client = $this->client_repository->getClientById($client_id);
            $user = $this->auth_service->getUserById($user_id);

            // TODO; move to a factory

            $access_token_db = new AccessTokenDB();
            $access_token_db->setValue($hashed_value);
            $access_token_db->setFromIp($this->ip_helper->getCurrentUserIpAddress());
            $access_token_db->setAssociatedAuthorizationCode(Hash::compute('sha256', $auth_code->getValue()));
            $access_token_db->setLifetime($access_token->getLifetime());
            $access_token_db->setScope($access_token->getScope());
            $access_token_db->setAudience($access_token->getAudience());
            $access_token_db->setClient($client);
            $access_token_db->setOwner($user);

            $this->access_token_repository->add($access_token_db);

            //check if use refresh tokens...
            Log::debug
            (
                sprintf
                (
                    'TokenService::createAccessToken use_refresh_token: %s - app_type: %s - scopes: %s - auth_code_access_type: %s - prompt: %s - approval_prompt: %s pkce %s.',
                    $client->useRefreshToken(),
                    $client->getApplicationType(),
                    $auth_code->getScope(),
                    $auth_code->getAccessType(),
                    $auth_code->getPrompt(),
                    $auth_code->getApprovalPrompt(),
                    $client->isPKCEEnabled()
                )
            );

            if
            (
                $client->useRefreshToken() &&
                (
                    $client->getApplicationType() == IClient::ApplicationType_Web_App ||
                    $client->getApplicationType() == IClient::ApplicationType_Native ||
                    $client->isPKCEEnabled()
                ) &&
                (
                    $auth_code->getAccessType() == OAuth2Protocol::OAuth2Protocol_AccessType_Offline ||
                    //OIDC: http://openid.net/specs/openid-connect-core-1_0.html#OfflineAccess
                    str_contains($auth_code->getScope(), OAuth2Protocol::OfflineAccess_Scope)
                )
            ) {
                //but only the first time (approval_prompt == force || not exists previous consent)
                if
                (
                    !$auth_code->getHasPreviousUserConsent() ||
                    // google oauth2 protocol
                    strpos($auth_code->getApprovalPrompt(), OAuth2Protocol::OAuth2Protocol_Approval_Prompt_Force) !== false ||
                    // http://openid.net/specs/openid-connect-core-1_0.html#OfflineAccess
                    strpos($auth_code->getPrompt(), OAuth2Protocol::OAuth2Protocol_Prompt_Consent) !== false
                ) {
                    Log::debug('TokenService::createAccessToken  creating refresh token ....');
                    $this->createRefreshToken($access_token);
                }
            }

            $this->storesAccessTokenOnCache($access_token);
            //stores brand new access token hash value on a set by client id...
            $this->cache_service->addMemberSet($client_id . TokenService::ClientAccessTokenPrefixList, $hashed_value);

            $this->cache_service->incCounter
            (
                $client_id . TokenService::ClientAccessTokensQty,
                TokenService::ClientAccessTokensQtyLifetime
            );

            return $access_token;
        });


    }

    /**
     * Create a brand new Access Token by params
     * @param $client_id
     * @param $scope
     * @param $audience
     * @param null $user_id
     * @return AccessToken
     */
    public function createAccessTokenFromParams($client_id, $scope, $audience, $user_id = null)
    {

        $access_token = $this->identifier_generator->generate(AccessToken::createFromParams
        (
            $scope,
            $client_id,
            $audience,
            $user_id,
            $this->configuration_service->getConfigValue('OAuth2.AccessToken.Lifetime')
        )
        );


        return $this->tx_service->transaction(function () use (
            $client_id,
            $scope,
            $audience,
            $user_id,
            $access_token
        ) {


            $value = $access_token->getValue();
            $hashed_value = Hash::compute('sha256', $value);

            $this->storesAccessTokenOnCache($access_token);

            $client_id = $access_token->getClientId();
            $client = $this->client_repository->getClientById($client_id);

            // todo: move to a factory

            $access_token_db = new AccessTokenDB();
            $access_token_db->setValue($hashed_value);
            $access_token_db->setFromIp($this->ip_helper->getCurrentUserIpAddress());
            $access_token_db->setLifetime($access_token->getLifetime());
            $access_token_db->setScope($access_token->getScope());
            $access_token_db->setAudience($access_token->getAudience());

            $access_token_db->setClient($client);

            if (!is_null($user_id)) {
                $user = $this->auth_service->getUserById($user_id);
                $access_token_db->setOwner($user);
            }

            $this->access_token_repository->add($access_token_db);

            //stores brand new access token hash value on a set by client id...
            $this->cache_service->addMemberSet($client_id . TokenService::ClientAccessTokenPrefixList, $hashed_value);
            $this->cache_service->incCounter($client_id . TokenService::ClientAccessTokensQty, TokenService::ClientAccessTokensQtyLifetime);
            return $access_token;
        });

    }

    /**
     * @param RefreshToken $refresh_token
     * @param null $scope
     * @return AccessToken|void
     */
    public function createAccessTokenFromRefreshToken(RefreshToken $refresh_token, $scope = null)
    {

        //preserve entire operation on db transaction...
        return $this->tx_service->transaction(function () use (
            $refresh_token,
            $scope
        ) {

            $refresh_token_value = $refresh_token->getValue();
            $refresh_token_hashed_value = Hash::compute('sha256', $refresh_token_value);
            //clear current access tokens as invalid
            $this->clearAccessTokensForRefreshToken($refresh_token->getValue());

            //validate scope if present...
            if (!is_null($scope) && empty($scope)) {
                $original_scope = $refresh_token->getScope();
                $aux_original_scope = explode(OAuth2Protocol::OAuth2Protocol_Scope_Delimiter, $original_scope);
                $aux_scope = explode(OAuth2Protocol::OAuth2Protocol_Scope_Delimiter, $scope);
                //compare original scope with given one, and validate if its included on original one
                //or not
                if (count(array_diff($aux_scope, $aux_original_scope)) !== 0) {
                    throw new InvalidGrantTypeException
                    (
                        sprintf
                        (
                            "requested scope %s is not contained on original one %s",
                            $scope,
                            $original_scope
                        )
                    );
                }
            } else {
                //get original scope
                $scope = $refresh_token->getScope();
            }

            //create new access token
            $access_token = $this->identifier_generator->generate
            (
                AccessToken::createFromRefreshToken
                (
                    $refresh_token,
                    $scope,
                    $this->configuration_service->getConfigValue('OAuth2.AccessToken.Lifetime')
                )
            );

            $value = $access_token->getValue();
            $hashed_value = Hash::compute('sha256', $value);

            $this->storesAccessTokenOnCache($access_token);

            //get user id
            $user_id = $access_token->getUserId();
            //get current client
            $client_id = $access_token->getClientId();
            $client = $this->client_repository->getClientById($client_id);

            //todo : move to a factory

            $access_token_db = new AccessTokenDB();
            $access_token_db->setValue($hashed_value);
            $access_token_db->setFromIp($this->ip_helper->getCurrentUserIpAddress());
            $access_token_db->setLifetime($access_token->getLifetime());
            $access_token_db->setScope($access_token->getScope());
            $access_token_db->setAudience($access_token->getAudience());

            //save relationships
            $refresh_token_db = $this->refresh_token_repository->getByValue($refresh_token_hashed_value);
            $access_token_db->setRefreshToken($refresh_token_db);
            $access_token_db->setClient($client);

            if (!is_null($user_id)) {
                $user = $this->auth_service->getUserById($user_id);
                $access_token_db->setOwner($user);
            }

            $this->access_token_repository->add($access_token_db);

            //stores brand new access token hash value on a set by client id...
            $this->cache_service->addMemberSet($client_id . TokenService::ClientAccessTokenPrefixList, $hashed_value);
            $this->cache_service->incCounter
            (
                $client_id . TokenService::ClientAccessTokensQty,
                TokenService::ClientAccessTokensQtyLifetime
            );
            return $access_token;
        });
    }

    /**
     * @param AccessToken $access_token
     * @return bool
     */
    private function clearAccessTokenOnCache(AccessToken $access_token)
    {
        $value = $access_token->getValue();
        $hashed_value = Hash::compute('sha256', $value);

        if ($this->cache_service->exists($hashed_value)) {
            $this->cache_service->delete($hashed_value);
            return true;
        }
        return false;
    }

    /**
     * @param AccessToken $access_token
     * @throws InvalidAccessTokenException
     */
    private function storesAccessTokenOnCache(AccessToken $access_token)
    {
        //stores in REDIS

        $value = $access_token->getValue();
        $hashed_value = Hash::compute('sha256', $value);

        if ($this->cache_service->exists($hashed_value)) {
            throw new InvalidAccessTokenException;
        }

        $auth_code = !is_null($access_token->getAuthCode()) ? Hash::compute('sha256',
            $access_token->getAuthCode()) : '';

        $refresh_token_value = !is_null($access_token->getRefreshToken()) ? Hash::compute('sha256',
            $access_token->getRefreshToken()->getValue()) : '';

        $user_id = !is_null($access_token->getUserId()) ? $access_token->getUserId() : 0;

        $this->cache_service->storeHash($hashed_value, [
            'user_id' => $user_id,
            'client_id' => $access_token->getClientId(),
            'scope' => $access_token->getScope(),
            'auth_code' => $auth_code,
            'issued' => $access_token->getIssued(),
            'lifetime' => $access_token->getLifetime(),
            'audience' => $access_token->getAudience(),
            'from_ip' => $this->ip_helper->getCurrentUserIpAddress(),
            'refresh_token' => $refresh_token_value
        ], intval($access_token->getLifetime()));
    }

    /**
     * @param AccessTokenDB $access_token
     * @return bool
     */
    private function clearAccessTokenDBOnCache(AccessTokenDB $access_token)
    {

        if ($this->cache_service->exists($access_token->getValue())) {
            $this->cache_service->delete($access_token->getValue());
            return true;
        }
        return false;
    }

    /**
     * @param AccessTokenDB $access_token
     * @throws InvalidAccessTokenException
     */
    private function storeAccessTokenDBOnCache(AccessTokenDB $access_token)
    {
        //stores in Cache

        if ($this->cache_service->exists($access_token->getValue())) {
            throw new InvalidAccessTokenException;
        }

        $refresh_token_value = '';
        $refresh_token_db = $access_token->getRefreshToken();

        if (!is_null($refresh_token_db)) {
            $refresh_token_value = $refresh_token_db->getValue();
        }

        $user_id = $access_token->getOwnerId();
        $client = $access_token->getClient();

        $this->cache_service->storeHash($access_token->getValue(), [
            'user_id' => $user_id,
            'client_id' => $client->getClientId(),
            'scope' => $access_token->getScope(),
            'auth_code' => $access_token->getAssociatedAuthorizationCode(),
            'issued' => $access_token->getCreatedAt()->format("Y-m-d H:i:s"),
            'lifetime' => $access_token->getLifetime(),
            'from_ip' => $access_token->getFromIp(),
            'audience' => $access_token->getAudience(),
            'refresh_token' => $refresh_token_value
        ], intval($access_token->getLifetime()));
    }

    /**
     * @param $value
     * @param bool $is_hashed
     * @return AccessToken
     * @throws InvalidAccessTokenException
     * @throws Exception
     */
    public function getAccessToken($value, $is_hashed = false)
    {

        return $this->tx_service->transaction(function () use (
            $value,
            $is_hashed
        ) {
            //hash the given value, bc tokens values are stored hashed on DB
            $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;
            $access_token = null;

            try {
                // check cache ...
                if (!$this->cache_service->exists($hashed_value)) {
                    $this->lock_manager_service->lock('lock.get.accesstoken.' . $hashed_value, function () use ($value, $hashed_value) {
                        // check on DB...
                        $access_token_db = $this->access_token_repository->getByValueCacheable($hashed_value);
                        if (is_null($access_token_db)) {
                            if ($this->isAccessTokenRevoked($hashed_value)) {
                                throw new RevokedAccessTokenException(sprintf('Access token %s is revoked!', $value));
                            } else if ($this->isAccessTokenVoid($hashed_value)) // check if its marked on cache as expired ...
                            {
                                throw new ExpiredAccessTokenException(sprintf('Access token %s is expired!', $value));
                            } else {
                                throw new InvalidGrantTypeException(sprintf("Access token %s is invalid!", $value));
                            }
                        }

                        if ($access_token_db->isVoid()) {
                            // invalid one ...
                            throw new ExpiredAccessTokenException(sprintf('Access token %s is expired!', $value));
                        }
                        //reload on cache
                        $this->storeAccessTokenDBOnCache($access_token_db);
                    });
                }

                $payload = $this->cache_service->getHash($hashed_value, [
                        'user_id',
                        'client_id',
                        'scope',
                        'auth_code',
                        'issued',
                        'lifetime',
                        'from_ip',
                        'audience',
                        'refresh_token'
                ]);

                // reload auth code ...
                $payload['value'] = $payload['auth_code'];

                $payload['user_id'] = intval($payload['user_id']) == 0 ? null : intval($payload['user_id']);
                $payload['lifetime'] = $this->configuration_service->getConfigValue('OAuth2.AuthorizationCode.Lifetime');
                $payload['access_type'] = OAuth2Protocol::OAuth2Protocol_AccessType_Online;
                $payload['approval_prompt'] = OAuth2Protocol::OAuth2Protocol_Approval_Prompt_Auto;
                $payload['has_previous_user_consent'] = false;
                $payload['is_hashed'] = true;
                $auth_code = AuthorizationCode::load($payload);

                // reload access token ...
                $access_token = AccessToken::load
                (
                    $value,
                    $auth_code,
                    $payload['issued'],
                    $payload['lifetime']
                );

                $refresh_token_value = $payload['refresh_token'];

                if (!empty($refresh_token_value)) {
                    $refresh_token = $this->getRefreshToken($refresh_token_value, true);
                    $access_token->setRefreshToken($refresh_token);
                }
            } catch (UnacquiredLockException $ex1) {
                throw new InvalidAccessTokenException(sprintf("Access token %s. ", $value));
            }

            return $access_token;
        });
    }

    /**
     * Checks if current_ip has access rights on the given $access_token
     * @param AccessToken $access_token
     * @param $current_ip
     * @return bool
     */
    public function checkAccessTokenAudience(AccessToken $access_token, $current_ip)
    {

        $current_audience = $access_token->getAudience();

        Log::debug
        (
            sprintf
            (
                "TokenService::checkAccessTokenAudience access_token %s current_ip %s current_audience %s",
                $access_token->getValue(),
                $current_ip,
                $current_audience
            )
        );

        $current_audience = explode(' ', $current_audience);
        if (!is_array($current_audience)) {
            $current_audience = array($current_audience);
        }

        $resource_server = $this->resource_server_repository->getByAudienceAndIpAndActive($current_audience, $current_ip);

        // check audience
        if(is_null($resource_server)){
            Log::warning(sprintf("TokenService::checkAccessTokenAudience not found resource server for ip %s", $current_ip));
            return false;
        }

        Log::debug
        (
            sprintf
            (
                "TokenService::checkAccessTokenAudience found resource server %s (%s)",
                $resource_server->getId(),
                $resource_server->getHost()
            )
        );

        $hosts = explode(',', $resource_server->getHost());
        $res = count(array_intersect($hosts, $current_audience));

        Log::debug
        (
            sprintf
            (
                "TokenService::checkAccessTokenAudience audience %s hosts %s res %s",
                json_encode($current_audience),
                json_encode($hosts),
                $res,
            )
        );

        return $res > 0;
    }


    /**
     * Creates a new refresh token and associate it with given access token
     * @param AccessToken $access_token
     * @param boolean $refresh_cache
     * @return RefreshToken
     */
    public function createRefreshToken(AccessToken &$access_token, $refresh_cache = false)
    {
        $refresh_token = $this->identifier_generator->generate(
            RefreshToken::create(
                $access_token,
                $this->configuration_service->getConfigValue('OAuth2.RefreshToken.Lifetime')
            )
        );

        return $this->tx_service->transaction(function () use (
            $refresh_token,
            $access_token,
            $refresh_cache
        ) {
            $value = $refresh_token->getValue();
            //hash the given value, bc tokens values are stored hashed on DB
            $hashed_value = Hash::compute('sha256', $value);
            $client_id = $refresh_token->getClientId();
            $user_id = $refresh_token->getUserId();
            $client = $this->client_repository->getClientById($client_id);
            $user = $this->auth_service->getUserById($user_id);

            // todo: move to a factory
            $refresh_token_db = new RefreshTokenDB;
            $refresh_token_db->setValue($hashed_value);
            $refresh_token_db->setLifetime($refresh_token->getLifetime());
            $refresh_token_db->setScope($refresh_token->getScope());
            $refresh_token_db->setAudience($access_token->getAudience());
            $refresh_token_db->setFromIp($this->ip_helper->getCurrentUserIpAddress());

            $access_token_db = $this->access_token_repository->getByValue(Hash::compute('sha256', $access_token->getValue()));
            $refresh_token_db->setClient($client);
            $refresh_token_db->setOwner($user);
            $refresh_token_db->addAccessToken($access_token_db);

            $this->refresh_token_repository->add($refresh_token_db);

            $access_token->setRefreshToken($refresh_token);
            // bc refresh token could change
            if ($refresh_cache) {
                if ($this->clearAccessTokenOnCache($access_token))
                    $this->storesAccessTokenOnCache($access_token);
                if ($this->clearAccessTokenDBOnCache($access_token_db))
                    $this->storeAccessTokenDBOnCache($access_token_db);
            }

            $this->cache_service->incCounter
            (
                $client_id . TokenService::ClientRefreshTokensQty,
                TokenService::ClientRefreshTokensQtyLifetime
            );

            return $refresh_token;
        });

    }

    /**
     * @param string $value
     * @param false $is_hashed
     * @return RefreshToken
     * @throws InvalidGrantTypeException
     * @throws ReplayAttackRefreshTokenException
     * @throws RevokedRefreshTokenException
     */
    public function getRefreshToken($value, $is_hashed = false)
    {
        //hash the given value, bc tokens values are stored hashed on DB
        $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;

        $refresh_token_db = $this->refresh_token_repository->getByValueCacheable($hashed_value);

        if (is_null($refresh_token_db)) {
            if ($this->isRefreshTokenRevoked($hashed_value))
                throw new RevokedRefreshTokenException(sprintf("revoked refresh token %s !", $value));

            throw new InvalidGrantTypeException(sprintf("refresh token %s does not exists!", $value));
        }

        if ($refresh_token_db->isVoid()) {
            throw new ReplayAttackRefreshTokenException
            (
                $value,
                sprintf
                (
                    "refresh token %s is void",
                    $value
                )
            );
        }

        //check is refresh token is stills alive... (ZERO is infinite lifetime)
        if ($refresh_token_db->isVoid()) {
            throw new InvalidGrantTypeException(sprintf("refresh token %s is expired!", $value));
        }

        $client = $refresh_token_db->getClient();

        $refresh_token = RefreshToken::load
        (
            [
                'value' => $value,
                'scope' => $refresh_token_db->getScope(),
                'client_id' => $client->getClientId(),
                'user_id' => $refresh_token_db->getOwnerId(),
                'audience' => $refresh_token_db->getAudience(),
                'from_ip' => $refresh_token_db->getFromIp(),
                'issued' => $refresh_token_db->getCreatedAt()->format("Y-m-d H:i:s"),
                'is_hashed' => $is_hashed
            ],
            intval($refresh_token_db->getLifetime())
        );

        return $refresh_token;
    }

    /**
     * Revokes all related tokens to a specific auth code
     * @param $auth_code Authorization Code
     * @return mixed
     */
    public function revokeAuthCodeRelatedTokens($auth_code)
    {
        $auth_code_hashed_value = Hash::compute('sha256', $auth_code);

        $this->tx_service->transaction(function () use (
            $auth_code_hashed_value
        ) {
            //get related access tokens
            $db_access_token = $this->access_token_repository->getByAuthCode($auth_code_hashed_value);
            if (is_null($db_access_token)) return;

            $client = $db_access_token->getClient();
            $access_token_value = $db_access_token->getValue();
            $refresh_token_db = $db_access_token->getRefreshToken();
            //remove auth code from client list on cache
            $this->cache_service->deleteMemberSet
            (
                $client->getClientId() . TokenService::ClientAuthCodePrefixList,
                $auth_code_hashed_value
            );
            //remove access token from client list on cache
            $this->cache_service->deleteMemberSet
            (
                $client->getClientId() . TokenService::ClientAccessTokenPrefixList,
                $access_token_value
            );

            $this->cache_service->delete($access_token_value);

            $this->access_token_repository->delete($db_access_token);

            if (!is_null($refresh_token_db)) {
                $this->revokeRefreshToken($refresh_token_db->getValue(), true);
            }

        });
    }

    /**
     * Revokes a given access token
     * @param $value
     * @param bool $is_hashed
     * @param User $current_user
     * @return bool
     */
    public function revokeAccessToken($value, $is_hashed = false, ?User $current_user = null)
    {

        Log::debug
        (
            sprintf
            (
                "TokenService::revokeAccessToken value %s is_hashed %b",
                $value,
                $is_hashed
            )
        );

        return $this->tx_service->transaction(function () use (
            $value,
            $is_hashed,
            $current_user
        ) {

            //hash the given value, bc tokens values are stored hashed on DB
            $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;

            $access_token_db = $this->access_token_repository->getByValue($hashed_value);

            if (is_null($access_token_db)){
                Log::debug(sprintf("TokenService::revokeAccessToken access token %s not found", $value));
                return false;
            }

            if (!is_null($current_user) && !$current_user->belongToGroup(IGroupSlugs::SuperAdminGroup) && $access_token_db->hasOwner() && $access_token_db->getOwnerId() != $current_user->getId()) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Access Token %s does not belongs to user id %s.",
                        $value,
                        $current_user->getId()
                    )
                );
            }

            $client = $access_token_db->getClient();
            //delete from cache
            $this->cache_service->delete($hashed_value);
            $this->cache_service->deleteMemberSet
            (
                $client->getClientId() . TokenService::ClientAccessTokenPrefixList,
                $access_token_db->getValue()
            );

            //check on DB... and delete it
            $this->access_token_repository->delete($access_token_db);

            $this->markAccessTokenAsRevoked($hashed_value);

            return true;
        });

    }

    /**
     * @param $value
     * @param bool|false $is_hashed
     * @return bool
     */
    public function expireAccessToken($value, $is_hashed = false)
    {
        return $this->tx_service->transaction(function () use (
            $value,
            $is_hashed
        ) {
            //hash the given value, bc tokens values are stored hashed on DB
            $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;

            $access_token_db = $this->access_token_repository->getByValue($hashed_value);

            if (is_null($access_token_db)) return false;

            $client = $access_token_db->getClient();
            //delete from cache
            $this->cache_service->delete($hashed_value);
            $this->cache_service->deleteMemberSet
            (
                $client->getClientId() . TokenService::ClientAccessTokenPrefixList,
                $access_token_db->getValue()
            );

            $this->access_token_repository->delete($access_token_db);

            $this->markAccessTokenAsVoid($hashed_value);

            return true;
        });
    }

    /**
     * @param int $user_id
     * @return void
     * @throws Exception
     */
    public function revokeUsersToken(int $user_id):void{
        Log::debug(sprintf("TokenService::revokeUsersToken user_id %s", $user_id));

        $this->tx_service->transaction(function () use (
            $user_id
        ) {

            $user = $this->auth_service->getUserById($user_id);
            if(is_null($user))
                throw new EntityNotFoundException("User not found");

            foreach($user->getRefreshTokens() as $refreshToken){
                Log::debug
                (
                    sprintf
                    (
                        "TokenService::revokeUsersToken revoking refresh token %s (%s)",
                        $refreshToken->getId(),
                        $refreshToken->getValue()
                    )
                );

                $this->revokeRefreshToken($refreshToken->getValue(), true, $user);
            }

            foreach($user->getAccessTokens() as $accessToken){
                Log::debug
                (
                    sprintf
                    (
                        "TokenService::revokeUsersToken revoking access token %s (%s)",
                        $accessToken->getId(),
                        $accessToken->getValue()
                    )
                );

                $this->revokeAccessToken($accessToken->getValue(), true, $user);
            }
        });
    }
    /**
     * Revokes all related tokens to a specific client id
     * @param $client_id
     */
    public function revokeClientRelatedTokens($client_id)
    {

        Log::debug(sprintf("TokenService::revokeClientRelatedTokens client_id %s", $client_id));

        $this->tx_service->transaction(function () use (
            $client_id
        ) {
            //get client auth codes
            $auth_codes = $this->cache_service->getSet($client_id . self::ClientAuthCodePrefixList);
            //get client access tokens
            $access_tokens = $this->cache_service->getSet($client_id . self::ClientAccessTokenPrefixList);

            $client = $this->client_repository->getClientById($client_id);

            if (is_null($client)) {
                return;
            }
            //revoke on cache
            $this->cache_service->deleteArray($auth_codes);
            $this->cache_service->deleteArray($access_tokens);
            //revoke on db
            foreach ($client->getValidAccessTokens() as $at) {
                $this->markAccessTokenAsRevoked($at->getValue());
            }

            foreach ($client->getRefreshTokens() as $rt) {
                $this->markRefreshTokenAsRevoked($rt->getValue());
            }

            $client->removeAllAccessTokens();
            $client->removeAllRefreshTokens();
            //delete client list (auth codes and access tokens)
            $this->cache_service->delete($client_id . TokenService::ClientAuthCodePrefixList);
            $this->cache_service->delete($client_id . TokenService::ClientAccessTokenPrefixList);
        });
    }

    /**
     * @param string $at_hash
     */
    public function markAccessTokenAsRevoked($at_hash)
    {
        $this->cache_service->addSingleValue
        (
            'access.token:revoked:' . $at_hash,
            'access.token:revoked:' . $at_hash,
            $this->configuration_service->getConfigValue('OAuth2.AccessToken.Revoked.Lifetime')
        );
    }

    /**
     * @param string $at_hash
     */
    public function markAccessTokenAsVoid($at_hash)
    {
        $this->cache_service->addSingleValue
        (
            'access.token:void:' . $at_hash,
            'access.token:void:' . $at_hash,
            $this->configuration_service->getConfigValue('OAuth2.AccessToken.Void.Lifetime')
        );
    }

    /**
     * @param string $rt_hash
     */
    public function markRefreshTokenAsRevoked($rt_hash)
    {
        $this->cache_service->addSingleValue
        (
            'refresh.token:revoked:' . $rt_hash,
            'refresh.token:revoked:' . $rt_hash,
            $this->configuration_service->getConfigValue('OAuth2.RefreshToken.Revoked.Lifetime')
        );
    }

    /**
     * @param string $at_hash
     * @return bool
     */
    public function isAccessTokenRevoked($at_hash)
    {
        return $this->cache_service->exists('access.token:revoked:' . $at_hash);
    }

    /**
     * @param string $at_hash
     * @return bool
     */
    public function isAccessTokenVoid($at_hash)
    {
        return $this->cache_service->exists('access.token:void:' . $at_hash);
    }

    /**
     * @param string $rt_hash
     * @return bool
     */
    public function isRefreshTokenRevoked($rt_hash)
    {
        return $this->cache_service->exists('refresh.token:revoked:' . $rt_hash);
    }

    /**
     * Mark a given refresh token as void
     * @param string $value
     * @param bool $is_hashed
     * @parama User $current_user
     * @return bool
     */
    public function invalidateRefreshToken(string $value, bool $is_hashed = false, ?User $current_user = null)
    {
        return $this->tx_service->transaction(function () use ($value, $is_hashed, $current_user) {
            Log::debug(sprintf("TokenService::invalidateRefreshToken value %s is_hashed %b", $value, $is_hashed));
            $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;
            $refresh_token = $this->refresh_token_repository->getByValue($hashed_value);
            if (is_null($refresh_token)){
                Log::debug(sprintf("TokenService::invalidateRefreshToken refresh token %s not found", $value));
                return false;
            }
            if (!is_null($current_user) && !$current_user->belongToGroup(IGroupSlugs::SuperAdminGroup) && $refresh_token->hasOwner() && $refresh_token->getOwnerId() != $current_user->getId()) {
                throw new ValidationException(sprintf("Refresh Token %s does not belongs to user id %s.", $value, $current_user->getId()));
            }

            $refresh_token->setVoid();
            $this->refresh_token_repository->add($refresh_token);
            return true;
        });


    }

    /**
     * Revokes a give refresh token and all related access tokens
     * @param $value
     * @param bool $is_hashed
     * @param User $current_user
     * @return mixed
     */
    public function revokeRefreshToken(string $value, bool $is_hashed = false, ?User $current_user = null)
    {
        return $this->tx_service->transaction(function () use ($value, $is_hashed, $current_user) {
            Log::debug(sprintf("TokenService::revokeRefreshToken value %s is_hashed %b", $value, $is_hashed));
            $res = $this->invalidateRefreshToken($value, $is_hashed, $current_user);
            return $res && $this->clearAccessTokensForRefreshToken($value, $is_hashed);
        });

    }

    /**
     * Revokes all access tokens for a give refresh token
     * @param string $value refresh token value
     * @param bool $is_hashed
     * @return bool|void
     */
    public function clearAccessTokensForRefreshToken($value, $is_hashed = false)
    {

        $hashed_value = !$is_hashed ? Hash::compute('sha256', $value) : $value;

        return $this->tx_service->transaction(function () use (
            $hashed_value
        ) {

            $refresh_token_db = $this->refresh_token_repository->getByValue($hashed_value);

            if (!is_null($refresh_token_db)) {
                $access_tokens_db = $this->access_token_repository->getByRefreshToken($refresh_token_db->getId());

                if (count($access_tokens_db) == 0) return false;

                foreach ($access_tokens_db as $access_token_db) {

                    $this->cache_service->delete($access_token_db->getValue());
                    $client = $access_token_db->getClient();
                    $this->cache_service->deleteMemberSet
                    (
                        $client->getClientId() . TokenService::ClientAccessTokenPrefixList,
                        $access_token_db->getValue()
                    );

                    $this->markAccessTokenAsRevoked($access_token_db->getValue());


                    $this->access_token_repository->delete($access_token_db);
                }
            }

            return true;
        });
    }

    /**
     * @param string $client_id
     * @param AccessToken|null $access_token
     * @param string|null $nonce
     * @param AuthorizationCode|null $auth_code
     * @return IBasicJWT
     * @throws AbsentClientException
     * @throws AbsentCurrentUserException
     * @throws ConfigurationException
     * @throws InvalidClientCredentials
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    public function createIdToken
    (
        string $client_id,
        AccessToken $access_token = null,
        ?string $nonce = null,
        ?AuthorizationCode $auth_code = null
    ):IBasicJWT
    {
        Log::debug(sprintf("TokenService::createIdToken client id is %s", $client_id));

        $issuer = $this->configuration_service->getSiteUrl();
        if (empty($issuer)) throw new ConfigurationException('Missing IDP URL.');

        $client = $this->client_repository->getClientById($client_id);
        $id_token_lifetime = $this->configuration_service->getConfigValue('OAuth2.IdToken.Lifetime');

        if (is_null($client)) {
            throw new AbsentClientException
            (
                sprintf
                (
                    "Client id %d does not exists.",
                    $client_id
                )
            );
        }

        $user = $this->auth_service->getCurrentUser();

        if (is_null($user)) {
            $user_id = $this->principal_service->get()->getUserId();
            Log::debug(sprintf("TokenService::createIdToken user id is %s from principal service", $user_id));
            $user = $this->auth_service->getUserById($user_id);
        }

        if(is_null($user) && !is_null($access_token)){
            $user_id = $access_token->getUserId();
            Log::debug(sprintf("TokenService::createIdToken user id is %s from access token", $user_id));
            $user = $this->auth_service->getUserById($user_id);
        }

        if (is_null($user))
            throw new AbsentCurrentUserException;

        if (!$user instanceof User)
            throw new AbsentCurrentUserException;

        // build claim set
        $epoch_now = time();

        $jti = $this->auth_service->generateJTI($client_id, $id_token_lifetime);

        $claim_set = new JWTClaimSet
        (
            $iss = new StringOrURI($issuer),
            $sub = new StringOrURI
            (
                $this->auth_service->wrapUserId
                (
                    $user->getId(),
                    $client
                )
            ),
            $aud = new StringOrURI($client_id),
            $iat = new NumericDate($epoch_now),
            $exp = new NumericDate($epoch_now + $id_token_lifetime),
            $jti = new JsonValue($jti)
        );

        // custom user info custom claims

        UserService::populateProfileClaims($claim_set, $user);
        UserService::populateAddressClaims($claim_set, $user);
        UserService::populateEmailClaims($claim_set, $user);

        if (!empty($nonce))
            $claim_set->addClaim(new JWTClaim(OAuth2Protocol::OAuth2Protocol_Nonce, new StringOrURI($nonce)));

        $id_token_response_info = $client->getIdTokenResponseInfo();
        $sig_alg = $id_token_response_info->getSigningAlgorithm();

        if (!is_null($sig_alg) && !is_null($access_token))
            $this->buildAccessTokenHashClaim($access_token, $sig_alg, $claim_set);

        if (!is_null($sig_alg) && !is_null($auth_code))
            $this->buildAuthCodeHashClaim($auth_code, $sig_alg, $claim_set);

        // auth_time claim
        $this->buildAuthTimeClaim($claim_set);

        return $this->id_token_builder->buildJWT($claim_set, $id_token_response_info, $client);
    }

    /**
     * @param AccessToken $access_token
     * @param HashFunctionAlgorithm $hashing_alg
     * @param JWTClaimSet $claim_set
     * @return JWTClaimSet
     * @throws InvalidClientCredentials
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    private function buildAccessTokenHashClaim
    (
        AccessToken $access_token,
        HashFunctionAlgorithm $hashing_alg,
        JWTClaimSet $claim_set
    )
    {
        $at = $access_token->getValue();
        $at_len = $hashing_alg->getHashKeyLen() / 2;
        $encoder = new Base64UrlRepresentation();

        if ($at_len > ByteUtil::bitLength(strlen($at)))
            throw new InvalidClientCredentials('invalid access token length!.');

        $claim_set->addClaim
        (
            new JWTClaim
            (
                OAuth2Protocol::OAuth2Protocol_AccessToken_Hash,
                new JsonValue
                (
                    $encoder->encode
                    (
                        substr
                        (
                            hash
                            (
                                $hashing_alg->getHashingAlgorithm(),
                                $at,
                                true
                            ),
                            0,
                            $at_len / 8
                        )
                    )
                )
            )
        );

        return $claim_set;
    }

    /**
     * @param AuthorizationCode $auth_code
     * @param HashFunctionAlgorithm $hashing_alg
     * @param JWTClaimSet $claim_set
     * @return JWTClaimSet
     * @throws InvalidClientCredentials
     * @throws \jwt\exceptions\ClaimAlreadyExistsException
     */
    private function buildAuthCodeHashClaim
    (
        AuthorizationCode $auth_code,
        HashFunctionAlgorithm $hashing_alg,
        JWTClaimSet $claim_set
    )
    {

        $ac = $auth_code->getValue();
        $ac_len = $hashing_alg->getHashKeyLen() / 2;
        $encoder = new Base64UrlRepresentation();

        if ($ac_len > ByteUtil::bitLength(strlen($ac)))
            throw new InvalidClientCredentials('invalid auth code length!.');

        $claim_set->addClaim
        (
            new JWTClaim
            (
                OAuth2Protocol::OAuth2Protocol_AuthCode_Hash,
                new JsonValue
                (
                    $encoder->encode
                    (
                        substr
                        (
                            hash
                            (
                                $hashing_alg->getHashingAlgorithm(),
                                $ac,
                                true
                            ),
                            0,
                            $ac_len / 8
                        )
                    )
                )
            )
        );

        return $claim_set;
    }

    private function buildAuthTimeClaim(JWTClaimSet $claim_set)
    {
        if ($this->security_context_service->get()->isAuthTimeRequired()) {
            $claim_set->addClaim
            (
                new JWTClaim
                (
                    OAuth2Protocol::OAuth2Protocol_AuthTime,
                    new JsonValue
                    (
                        $this->principal_service->get()->getAuthTime()
                    )
                )
            );
        }
    }

    /**
     * @param AuthorizationCode $auth_code
     * @return AccessToken|null
     */
    public function getAccessTokenByAuthCode(AuthorizationCode $auth_code)
    {
        $auth_code_value = Hash::compute('sha256', $auth_code->getValue());
        $db_access_token = $this->access_token_repository->getByAuthCode($auth_code_value);
        if (is_null($db_access_token)) return null;
        return $this->getAccessToken($db_access_token->getValue(), true);
    }

    /**
     * @param OAuth2PasswordlessAuthenticationRequest $request
     * @param Client|null $client
     * @return OAuth2OTP
     * @throws Exception
     */
    public function createOTPFromRequest(OAuth2PasswordlessAuthenticationRequest $request, ?Client $client):OAuth2OTP{

        $otp = $this->tx_service->transaction(function() use($request, $client){
            $otp = OTPFactory::buildFromRequest($request, $this->identifier_generator, $client);

            if(is_null($client)){
                $this->otp_repository->add($otp);
            }

            $user = $this->auth_service->getUserByUsername($otp->getUserName());
            if(!is_null($user)){
                Log::debug
                (
                    sprintf
                    (
                        "TokenService::createOTPFromRequest requested OTP for existent user %s (%s)",
                        $user->getEmail(),
                        $user->getId()
                    )
                );
                AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), "Requested OTP");
                if(!$user->isActive())
                    throw new ValidationException("User is not active.");
            }
            return $otp;
        });

        return $this->tx_service->transaction(function() use($otp){
            // create channel and value to send ( depending on connection and send params )
            OTPChannelStrategyFactory::build($otp->getConnection())->send
            (
                OTPTypeBuilderStrategyFactory::build($otp->getSend()),
                $otp
            );

            return $otp;
        });

    }

    /**
     * @param array $payload
     * @param Client|null $client
     * @return OAuth2OTP
     * @throws Exception
     */
    public function createOTPFromPayload(array $payload, ?Client $client):OAuth2OTP{

        $otp = $this->tx_service->transaction(function() use($payload, $client){

            $otp = OTPFactory::buildFromPayload($payload, $this->identifier_generator, $client);

            $user = $this->auth_service->getUserByUsername($otp->getUserName());
            if(!is_null($user)){
                Log::debug
                (
                    sprintf
                    (
                        "TokenService::createOTPFromPayload requested OTP for existent user %s (%s)",
                        $user->getEmail(),
                        $user->getId()
                    )
                );
                AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), "Requested OTP");
                if(!$user->isActive())
                    throw new ValidationException("User is not active.");
            }
            if(is_null($client)){
                $this->otp_repository->add($otp);
            }

            return $otp;
        });

        return $this->tx_service->transaction(function() use($otp){
            // create channel and value to send ( depending on connection and send params )
            OTPChannelStrategyFactory::build($otp->getConnection())->send
            (
                OTPTypeBuilderStrategyFactory::build($otp->getSend()),
                $otp
            );

            return $otp;
        });

    }


    /**
     * @param OAuth2OTP $otp
     * @param Client|null $client
     * @return AccessToken
     * @throws Exception
     */
    public function createAccessTokenFromOTP(OAuth2OTP &$otp, ?Client $client): AccessToken
    {

        try {
            $otp = $this->auth_service->loginWithOTP($otp, $client);
            // build current audience ...
            $audience = $this->scope_service->getStrAudienceByScopeNames
            (
                explode
                (
                    OAuth2Protocol::OAuth2Protocol_Scope_Delimiter,
                    $otp->getScope()
                )
            );

            $access_token = $this->identifier_generator->generate
            (
                AccessToken::createFromOTP
                (
                    $otp,
                    ! is_null($client) ? $client->getClientId() : null,
                    $audience,
                    $this->configuration_service->getConfigValue('OAuth2.AccessToken.Lifetime')
                )
            );

            return $this->tx_service->transaction(function() use($access_token, $client){
                // TODO; move to a factory
                $value = $access_token->getValue();
                $hashed_value = Hash::compute('sha256', $value);

                $access_token_db = new AccessTokenDB();
                $access_token_db->setValue($hashed_value);
                $access_token_db->setFromIp($this->ip_helper->getCurrentUserIpAddress());
                $access_token_db->setLifetime($access_token->getLifetime());
                $access_token_db->setScope($access_token->getScope());
                $access_token_db->setAudience($access_token->getAudience());
                $access_token_db->setClient($client);
                $access_token_db->setOwner($this->auth_service->getCurrentUser());

                $this->access_token_repository->add($access_token_db);

                //check if use refresh tokens...

                if
                (
                    $client->useRefreshToken() &&
                    $client->isPasswordlessEnabled() &&
                    str_contains($access_token->getScope(), OAuth2Protocol::OfflineAccess_Scope)
                ) {
                    Log::debug('TokenService::createAccessTokenFromOTP creating refresh token ...');
                    $this->createRefreshToken($access_token);
                }

                $this->storesAccessTokenOnCache($access_token);
                // stores brand new access token hash value on a set by client id...
                {
                    if (!is_null($client))
                        $this->cache_service->addMemberSet($client->getClientId() . TokenService::ClientAccessTokenPrefixList, $hashed_value);

                    $this->cache_service->incCounter
                    (
                        $client->getClientId() . TokenService::ClientAccessTokensQty,
                        TokenService::ClientAccessTokensQtyLifetime
                    );
                }

                return $access_token;
            });
        }
        catch (AuthenticationException $ex){
            throw new InvalidOTPException($ex->getMessage());
        }
    }

    /**
     * @param OAuth2OTP $otp
     * @param Client $client
     * @return bool
     * @throws Exception
     */
    public function canCreateAccessTokenFromOTP(OAuth2OTP &$otp, Client $client):bool{
        return $this->tx_service->transaction(function() use($otp, $client){
            Log::debug
            (
                sprintf
                (
                    "TokenService::canCreateAccessTokenFromOTP otp %s user %s client %s",
                    $otp->getValue(),
                    $otp->getUserName(),
                    !is_null($client) ? $client->getClientId() : 'null'
                )
            );

            $user = $this->auth_service->getUserByUsername($otp->getUserName());
            if(is_null($user))
                throw new ValidationException("Invalid OTP.");

            return $this->canCreateAccessToken($user, $client);
        });
    }

    /**
     * @param User $user
     * @param Client $client
     * @return bool
     * @throws \Exception
     */
    public function canCreateAccessToken(User $user, Client $client):bool{
        return $this->tx_service->transaction(function() use($user, $client){
            Log::debug(sprintf("TokenService::canCreateAccessToken user %s client %s", $user->getId(), $client->getClientId()));
            if(!$client->isLimitingAllowedSessionsPerUser()) return true;

            $current_access_token_qty = $this->access_token_repository->getValidCountByUserIdAndClientIdentifier
            (
                $user->getId(),
                $client->getClientId()
            );

            Log::debug(sprintf("TokenService::canCreateAccessToken current access token qty %d", $current_access_token_qty));

            if($current_access_token_qty >= $client->getMaxAllowedUserSessions()) {
                Log::debug(sprintf("TokenService::canCreateAccessToken max allowed user sessions reached %d", $client->getMaxAllowedUserSessions()));
                return false;
            }

            return true;
        });
    }
}