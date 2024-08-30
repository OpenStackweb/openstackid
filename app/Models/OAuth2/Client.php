<?php namespace Models\OAuth2;
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

use App\libs\Utils\URLUtils;
use Auth\User;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use jwa\cryptographic_algorithms\ContentEncryptionAlgorithms_Registry;
use jwa\cryptographic_algorithms\DigitalSignatures_MACs_Registry;
use jwa\cryptographic_algorithms\KeyManagementAlgorithms_Registry;
use jwa\JSONWebSignatureAndEncryptionAlgorithms;
use models\exceptions\ValidationException;
use OAuth2\Exceptions\ScopeNotAllowedException;
use OAuth2\Models\IClient;
use OAuth2\Models\IClientPublicKey;
use OAuth2\Models\JWTResponseInfo;
use OAuth2\Models\TokenEndpointAuthInfo;
use OAuth2\OAuth2Protocol;
use Services\Facades\ServerConfigurationService;
use Exception;
use DateTime;
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use URL\Normalizer;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineOAuth2ClientRepository")
 * @ORM\Table(name="oauth2_client")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 * Class Client
 * @package Models\OAuth2
 */
class Client extends BaseEntity implements IClient
{
    /**
     * @ORM\Column(name="app_name", type="string")
     * @var string
     */
    private $app_name;

    /**
     * @ORM\Column(name="app_description", type="string")
     * @var string
     */
    private $app_description;

    /**
     * @ORM\Column(name="app_logo", type="string")
     * @var string
     */
    private $app_logo;

    /**
     * @ORM\Column(name="client_id", type="string")
     * @var string
     */
    private $client_id;

    /**
     * @ORM\Column(name="client_secret", type="string")
     * @var string
     */
    private $client_secret;

    /**
     * @ORM\Column(name="client_type", type="string")
     * @var string
     * enum('PUBLIC', 'CONFIDENTIAL') default 'CONFIDENTIAL'
     */
    private $client_type;

    /**
     * @ORM\Column(name="active", type="boolean")
     * @var bool
     */
    private $active;

    /**
     * @ORM\Column(name="pkce_enabled", type="boolean")
     * @var bool
     */
    private $pkce_enabled;

    /**
     * @ORM\Column(name="otp_enabled", type="boolean")
     * @var bool
     */
    private $otp_enabled;

    /**
     * @ORM\Column(name="otp_length", type="integer")
     * @var int
     */
    private $otp_length;

    /**
     * @ORM\Column(name="otp_lifetime", type="integer")
     * @var int
     */
    private $otp_lifetime;

    /**
     * @ORM\Column(name="locked", type="boolean")
     * @var bool
     */
    private $locked;

    /**
     * @ORM\Column(name="max_auth_codes_issuance_qty", type="integer")
     * @var int
     */
    private $max_auth_codes_issuance_qty;

    /**
     * @ORM\Column(name="max_access_token_issuance_qty", type="integer")
     * @var int
     */
    private $max_access_token_issuance_qty;

    /**
     * @ORM\Column(name="max_access_token_issuance_basis", type="integer")
     * @var int
     */
    private $max_access_token_issuance_basis;

    /**
     * @ORM\Column(name="max_auth_codes_issuance_basis", type="integer")
     * @var int
     */
    private $max_auth_codes_issuance_basis;

    /**
     * @ORM\Column(name="max_refresh_token_issuance_qty", type="integer")
     * @var int
     */
    private $max_refresh_token_issuance_qty;

    /**
     * @ORM\Column(name="max_refresh_token_issuance_basis", type="integer")
     * @var int
     */
    private $max_refresh_token_issuance_basis;

    /**
     * @ORM\Column(name="use_refresh_token", type="boolean")
     * @var bool
     */
    private $use_refresh_token;

    /**
     * @ORM\Column(name="rotate_refresh_token", type="boolean")
     * @var bool
     */
    private $rotate_refresh_token;

    /**
     * @ORM\Column(name="website", type="string")
     * @var string
     */
    private $website;

    /**
     * @ORM\Column(name="application_type", type="string")
     * @var string
     * enum('WEB_APPLICATION', 'JS_CLIENT', 'SERVICE', 'NATIVE') default 'WEB_APPLICATION'
     */
    private $application_type;

    /**
     * @ORM\Column(name="client_secret_expires_at", type="datetime")
     * @var DateTime
     */
    private $client_secret_expires_at;

    /**
     * @ORM\Column(name="contacts", type="string")
     * @var string
     */
    private $contacts;

    /**
     * @ORM\Column(name="logo_uri", type="string")
     * @var string
     */
    private $logo_uri;

    /**
     * @ORM\Column(name="tos_uri", type="string")
     * @var string
     */
    private $tos_uri;

    /**
     * @ORM\Column(name="post_logout_redirect_uris", type="string")
     * @var string
     */
    private $post_logout_redirect_uris;

    /**
     * @ORM\Column(name="logout_uri", type="string")
     * @var string
     */
    private $logout_uri;

    /**
     * @ORM\Column(name="logout_session_required", type="boolean")
     * @var bool
     */
    private $logout_session_required;

    /**
     * @ORM\Column(name="logout_use_iframe", type="boolean")
     * @var bool
     */
    private $logout_use_iframe;

    /**
     * @ORM\Column(name="policy_uri", type="string")
     * @var string
     */
    private $policy_uri;

    /**
     * @ORM\Column(name="jwks_uri", type="string")
     * @var string
     */
    private $jwks_uri;

    /**
     * @ORM\Column(name="default_max_age", type="integer")
     * @var int
     */
    private $default_max_age;

    /**
     * @ORM\Column(name="require_auth_time", type="boolean")
     * @var bool
     */
    private $require_auth_time;

    /**
     * @ORM\Column(name="token_endpoint_auth_method", type="string")
     * @var string
     * enum('client_secret_basic', 'client_secret_post', 'client_secret_jwt', 'private_key_jwt', 'none') default 'none'
     */
    private $token_endpoint_auth_method;

    /**
     * @ORM\Column(name="token_endpoint_auth_signing_alg", type="string")
     * @var string
     * enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none'
     */
    private $token_endpoint_auth_signing_alg;

    /**
     * @ORM\Column(name="subject_type", type="string")
     * @var string
     * enum('public', 'pairwise') default 'public'
     */
    private $subject_type;

    /**
     * @ORM\Column(name="userinfo_signed_response_alg", type="string")
     * @var string
     * enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none'
     */
    private $userinfo_signed_response_alg;

    /**
     * @ORM\Column(name="userinfo_encrypted_response_alg", type="string")
     * @var string
     * enum('RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256', 'dir', 'none') default 'none'
     */
    private $userinfo_encrypted_response_alg;

    /**
     * @ORM\Column(name="userinfo_encrypted_response_enc", type="string")
     * @var string
     * enum('A128CBC-HS256', 'A192CBC-HS384', 'A256CBC-HS512', 'none') default 'none'
     */
    private $userinfo_encrypted_response_enc;

    /**
     * @ORM\Column(name="id_token_signed_response_alg", type="string")
     * @var string
     * enum('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'none') default 'none'
     */
    private $id_token_signed_response_alg;

    /**
     * @ORM\Column(name="id_token_encrypted_response_alg", type="string")
     * @var string
     * enum('RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256', 'dir', 'none') default 'none'
     */
    private $id_token_encrypted_response_alg;

    /**
     * @ORM\Column(name="id_token_encrypted_response_enc", type="string")
     * @var string
     * enum('A128CBC-HS256', 'A192CBC-HS384', 'A256CBC-HS512', 'none') default 'none'
     */
    private $id_token_encrypted_response_enc;

    /**
     * @ORM\Column(name="redirect_uris", type="string")
     * @var string
     */
    private $redirect_uris;

    /**
     * @ORM\Column(name="allowed_origins", type="string")
     * @var string
     */
    private $allowed_origins;

    /**
     * @ORM\Column(name="max_allowed_user_sessions", type="integer")
     * @var int
     */
    private $max_allowed_user_sessions;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", inversedBy="clients")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User")
     * @ORM\JoinColumn(name="edited_by_id", referencedColumnName="id")
     * @var User
     */
    private $edited_by;

    /**
     * @ORM\OneToOne(targetEntity="Models\OAuth2\ResourceServer", inversedBy="client", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="resource_server_id", referencedColumnName="id")
     * @var ResourceServer
     */
    private $resource_server;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\ClientPublicKey", mappedBy="owner", cascade={"persist"}, orphanRemoval=true)
     * @var ArrayCollection
     */
    private $public_keys;

    /**
    /**
     * @ORM\ManyToMany(targetEntity="Auth\User", cascade={"persist"}, inversedBy="managed_clients")
     * @ORM\JoinTable(name="oauth2_client_admin_users",
     *      joinColumns={@ORM\JoinColumn(name="oauth2_client_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *     )
     * @var ArrayCollection
     */
    private $admin_users;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\RefreshToken", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $refresh_tokens;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\AccessToken", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $access_tokens;

    /**
     * @ORM\ManyToMany(targetEntity="Models\OAuth2\ApiScope", cascade={"persist"})
     * @ORM\JoinTable(name="oauth2_client_api_scope",
     *      joinColumns={@ORM\JoinColumn(name="client_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="scope_id", referencedColumnName="id")}
     *     )
     * @var ArrayCollection
     */
    private $scopes;

    /**
     * @ORM\OneToMany(targetEntity="Models\OAuth2\OAuth2OTP", mappedBy="client", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $otp_grants;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->public_keys = new ArrayCollection();
        $this->access_tokens = new ArrayCollection();
        $this->refresh_tokens = new ArrayCollection();
        $this->admin_users = new ArrayCollection();
        $this->otp_grants = new ArrayCollection();
        $this->scopes = new ArrayCollection();
        $this->locked = false;
        $this->active = false;
        $this->use_refresh_token = false;
        $this->rotate_refresh_token = false;
        $this->token_endpoint_auth_method = OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic;
        $this->token_endpoint_auth_signing_alg = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->userinfo_signed_response_alg = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->userinfo_encrypted_response_alg = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->userinfo_encrypted_response_enc = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->id_token_encrypted_response_alg = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->id_token_encrypted_response_enc = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->id_token_signed_response_alg = JSONWebSignatureAndEncryptionAlgorithms::None;
        $this->subject_type = IClient::SubjectType_Public;
        $this->logout_session_required = false;
        $this->logout_use_iframe = false;
        $this->require_auth_time = false;
        $this->default_max_age = 0;
        $this->max_auth_codes_issuance_qty = 0;
        $this->max_auth_codes_issuance_basis = 0;
        $this->max_access_token_issuance_basis = 0;
        $this->max_access_token_issuance_qty = 0;
        $this->max_refresh_token_issuance_basis = 0;
        $this->max_refresh_token_issuance_qty = 0;
        $this->max_allowed_user_sessions = 0;
        $this->pkce_enabled = false;
        $this->otp_enabled = false;
        $this->otp_lifetime = intval(Config::get("otp.lifetime"));
        $this->otp_length = intval(Config::get("otp.length"));
    }

    public static  $valid_app_types = [
        IClient::ApplicationType_Service,
        IClient::ApplicationType_JS_Client,
        IClient::ApplicationType_Web_App,
        IClient::ApplicationType_Native
    ];

    public static $valid_subject_types = [
        IClient::SubjectType_Public,
        IClient::SubjectType_Pairwise
    ];

    public static $valid_token_endpoint_auth_methods = [
        OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
        OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretPost,
        OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretJwt,
        OAuth2Protocol::TokenEndpoint_AuthMethod_PrivateKeyJwt,
        OAuth2Protocol::TokenEndpoint_AuthMethod_None,
    ];


    /**
     * @param $application_type
     * @throws \InvalidArgumentException
     */
    public function setApplicationType(string $application_type)
    {
        if(!in_array(strtoupper($application_type), self::$valid_app_types)){
            throw new \InvalidArgumentException("Invalid application_type");
        }
        $this->application_type = strtoupper($application_type);
        $this->client_type = $this->inferClientTypeFromAppType($this->application_type);
    }

    /**
     * @return bool
     */
    public function canRequestRefreshTokens():bool{
        return
            $this->getApplicationType() == IClient::ApplicationType_Native ||
            $this->getApplicationType() == IClient::ApplicationType_Web_App ||
            // PCKE
            $this->pkce_enabled ||
            // Passwordless
            $this->otp_enabled;
    }

    /**
     * @param string $app_type
     * @return string
     */
    private function inferClientTypeFromAppType(string $app_type)
    {
        switch($app_type)
        {
            case IClient::ApplicationType_JS_Client:
                return IClient::ClientType_Public;
            break;
            default:
                return IClient::ClientType_Confidential;
            break;
        }
    }

    /**
     * @return $this
     */
    public function removeAllScopes()
    {
        $this->scopes->clear();
        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllAccessTokens(){
        $this->access_tokens->clear();
        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllRefreshTokens(){
        $this->refresh_tokens->clear();
        return $this;
    }

    public function removeAllVoidAccessTokens(): void {
        $query = $this->createQuery("delete from Models\OAuth2\AccessToken t 
        WHERE t.id in (select c.id from  Models\OAuth2\Client c where c.id = :client_id ) AND DATEADD(t.created_at, t.lifetime, 'SECOND') <= UTC_TIMESTAMP()
        ");
        $query
            ->setParameter('client_id', $this->getIdentifier())
            ->execute();
    }

    /**
     * @return bool
     */
    public function hasAccessTokens():bool {
        return $this->access_tokens->count() > 0;
    }

    public function getClientId()
    {
        return $this->client_id;
    }

    public function getClientSecret()
    {
        return $this->client_secret;
    }

    public function getClientType()
    {
        return $this->client_type;
    }

    /**
     * @return ApiScope[]
     */
    public function getClientScopes():array
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        $active_scopes = $this->scopes->matching($criteria);
        $res = [];

        foreach($active_scopes as $active_scope)
        {
            if($active_scope->hasApi() && $active_scope->getApi()->isActive())
                $res[] = $active_scope;
        }
        return $res;
    }

    public function getRedirectUris()
    {
        return explode(',',$this->redirect_uris);
    }

    public function getRawRedirectUris()
    {
        return $this->redirect_uris;
    }

    /**
     * @param string $name
     * @return ApiScope|null
     */
    public function getActiveScope(string $name):?ApiScope {

        $criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('active', true))
            ->andWhere(Criteria::expr()->eq('name', $name));
        $active_scopes = $this->scopes->matching($criteria);

        return $active_scopes->count() > 0 ? $active_scopes->first() : null;
    }

    /**
     * @param string $scope
     * @return bool
     * @throws ScopeNotAllowedException
     */
    public function isScopeAllowed(string $scope):bool
    {
        if(empty($scope)) return false;
        $res            = true;
        $desired_scopes = explode(" ", $scope);
        foreach($desired_scopes as $desired_scope){
            //check if desired scope belongs to application given scopes
            $activeScope     = $this->getActiveScope($desired_scope);
            $api             = !is_null($activeScope) ? $activeScope->getApi() : null;
            $resource_server = !is_null($api) ? $api->getResourceServer() : null;

            if(is_null($activeScope) ||(!is_null($api) && !$api->isActive()) || (!is_null($resource_server) && !$resource_server->isActive())){
                Log::debug(sprintf("Client::isScopeAllowed client %s scope %s not allowed.", $this->client_id, $desired_scope));
                throw new ScopeNotAllowedException($desired_scope);
            }
        }
        return $res;
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function isUriAllowed(string $uri):bool
    {
        $uri = URLUtils::canonicalUrl($uri);
        if(empty($uri)) return false;

        if
        (
            ($this->application_type !== IClient::ApplicationType_Native && !URLUtils::isHTTPS($uri))
            && (ServerConfigurationService::getConfigValue("SSL.Enable"))
        )
        {
            Log::debug(sprintf("Client::isUriAllowed url %s is not under ssl schema", $uri));
            return false;
        }

        $redirect_uris = explode(',',strtolower($this->redirect_uris));
        $uri = URLUtils::normalizeUrl($uri);
        foreach($redirect_uris as $redirect_uri){
            Log::debug(sprintf("Client::isUriAllowed url %s client %s redirect_uri %s", $uri, $this->client_id, $redirect_uri));
            if(str_contains($uri, $redirect_uri))
                return true;
        }

        Log::debug(sprintf("Client::isUriAllowed url %s is not allowed as return url for client %s", $uri, $this->client_id));
        return false;
    }

    public function getApplicationName()
    {
        return $this->app_name;
    }

    public function getApplicationLogo()
    {
        $app_logo = $this->app_logo;
        if(is_null($app_logo) || empty($app_logo))
            $app_logo = asset('assets/img/oauth2.default.logo.png');
        $app_logo_url = $this->logo_uri;
        if(!empty($app_logo_url))
            $app_logo = $app_logo_url;
        return $app_logo;
    }

    public function getApplicationDescription()
    {
        return $this->app_description;
    }

    public function getDeveloperEmail()
    {
        $user  = $this->user;
        $email = $user->getEmail();
        return $email;
    }

    /**
     * @return bool
     */
    public function hasUser():bool{
        return $this->getUserId() > 0;
    }

    /**
     * @return int
     */
    public function getUserId():int{
        try {
            return !is_null($this->user) ? $this->user->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return User
     */
    public function getOwner():User{
        return $this->user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isLocked()
    {
        return $this->locked;
    }

    public function isActive():bool
    {
        return $this->active;
    }

    public function isResourceServerClient():bool
    {
       return $this->hasResourceServer();
    }

    /**
     * @return int
     */
    public function getResourceServerId(): int{
        try {
            return is_null($this->resource_server) ? 0 : $this->resource_server->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasResourceServer():bool{
        return $this->getResourceServerId() > 0;
    }

    public function getResourceServer():ResourceServer
    {
       return $this->resource_server;
    }

    public function getApplicationType():string
    {
        return $this->application_type;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFriendlyApplicationType()
    {
        switch($this->application_type){
            case IClient::ApplicationType_JS_Client:
                return 'Client Side (JS)';
                break;
            case IClient::ApplicationType_Service:
                return 'Service Account';
                break;
            case IClient::ApplicationType_Web_App:
                return 'Web Server Application';
                break;
            case IClient::ApplicationType_Native:
                return 'Native Application';
                break;
        }
        throw new Exception('Invalid Application Type');
    }

    public function getClientAllowedOrigins()
    {
        return explode(',', $this->allowed_origins);
    }

    public function getRawClientAllowedOrigins()
    {
        return $this->allowed_origins;
    }

    /**
     * the origin is the triple {protocol, host, port}
     * @param $origin
     * @return bool
     */
    public function isOriginAllowed(string $origin):bool
    {
        $originWithoutPort = URLUtils::canonicalUrl($origin, false);
        if(empty($originWithoutPort)) return false;
        if(str_contains($this->allowed_origins, URLUtils::normalizeUrl($originWithoutPort) )) return true;
        $originWithPort = URLUtils::canonicalUrl($origin);
        return str_contains($this->allowed_origins, URLUtils::normalizeUrl($originWithPort));
    }

    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return DateTime
     */
    public function getClientSecretExpiration()
    {
        $exp_date = $this->client_secret_expires_at;
        if(is_null($exp_date)) return null;

        if($exp_date instanceof DateTime)
            return $exp_date;
        return new DateTime($exp_date);
    }

    /**
     * @return bool
     */
    public function isClientSecretExpired()
    {
        $now      = new DateTime();
        $exp_date = $this->getClientSecretExpiration();

        if(is_null($exp_date)) return false;
        return $exp_date < $now;
    }

    /**
     * @return string[]
     */
    public function getContacts()
    {
        return explode(',',$this->contacts);
    }

    public function getRawContacts(){
        return $this->contacts;
    }

    /**
     * @return int
     */
    public function getDefaultMaxAge()
    {
        return (int)$this->default_max_age;
    }

    /**
     * @return bool
     */
    public function requireAuthTimeClaim()
    {
        return $this->require_auth_time;
    }

    /**
     * @return string
     */
    public function getLogoUri()
    {
        return $this->logo_uri;
    }

    /**
     * @return string
     */
    public function getPolicyUri()
    {
        return $this->policy_uri;
    }

    /**
     * @return string
     */
    public function getTermOfServiceUri()
    {
        return $this->tos_uri;
    }

    /**
     * @return string[]
     */
    public function getPostLogoutUris()
    {
        return explode(',', $this->post_logout_redirect_uris);
    }

    /**
     * @return string
     */
    public function getLogoutUri()
    {
        return $this->logout_uri;
    }

    /**
     * @return JWTResponseInfo
     */
    public function getIdTokenResponseInfo()
    {
        return new JWTResponseInfo
        (
            DigitalSignatures_MACs_Registry::getInstance()->get($this->id_token_signed_response_alg),
            KeyManagementAlgorithms_Registry::getInstance()->get($this->id_token_encrypted_response_alg),
            ContentEncryptionAlgorithms_Registry::getInstance()->get($this->id_token_encrypted_response_enc)
        );
    }

    /**
     * @return JWTResponseInfo
     */
    public function getUserInfoResponseInfo()
    {
        return new JWTResponseInfo
        (
            DigitalSignatures_MACs_Registry::getInstance()->get($this->userinfo_signed_response_alg),
            KeyManagementAlgorithms_Registry::getInstance()->get($this->userinfo_encrypted_response_alg),
            ContentEncryptionAlgorithms_Registry::getInstance()->get($this->userinfo_encrypted_response_enc)
        );
    }

    /**
     * @return TokenEndpointAuthInfo
     */
    public function getTokenEndpointAuthInfo()
    {
       return new TokenEndpointAuthInfo(
           $this->token_endpoint_auth_method,
           DigitalSignatures_MACs_Registry::getInstance()->isSupported($this->token_endpoint_auth_signing_alg) ?
               DigitalSignatures_MACs_Registry::getInstance()->get($this->token_endpoint_auth_signing_alg) :
               null
       );
    }

    /**
     * @return string
     */
    public function getSubjectType()
    {
        return $this->subject_type;
    }

    /**
     * @return IClientPublicKey[]
     */
    public function getPublicKeys()
    {
       return $this->public_keys;
    }

    /**
     * @return IClientPublicKey[]
     */
    public function getPublicKeysByUse($use)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('usage', $use));

        return $this->public_keys->matching($criteria);
    }

    /**
     * @param string $kid
     * @return IClientPublicKey|null
     */
    public function getPublicKeyByIdentifier($kid)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('kid', $kid));
        $res = $this->public_keys->matching($criteria)->first();
        return !$res ? null: $res;
    }

    /**
     * @param ClientPublicKey $public_key
     * @return $this
     */
    public function addPublicKey(ClientPublicKey $public_key)
    {
        if($this->public_keys->contains($public_key)) return;
        $this->public_keys->add($public_key);
        $public_key->setOwner($this);
    }

    /**
     * @return string
     */
    public function getJWKSUri()
    {
       return $this->jwks_uri;
    }

    /**
     * @param string $use
     * @param string $alg
     * @return IClientPublicKey|null
     */
    public function getCurrentPublicKeyByUse($use, $alg)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        try {
            $query = $this->createQuery("SELECT k from Models\OAuth2\ClientPublicKey k 
        JOIN k.owner c 
        WHERE 
        c.id = :client_id AND 
        k.usage = :use AND
        k.alg = :alg AND
        k.active = 1 AND
        k.valid_from <= :now AND
        k.valid_to >= :now
        ");

            return $query
                ->setParameter('client_id', $this->getIdentifier())
                ->setParameter('use', trim($use))
                ->setParameter('alg', trim($alg))
                ->setParameter('now', $now)
                ->getSingleResult();
        }
        catch (Exception $ex){
            return null;
        }
    }


    /**
     * @param string $type
     * @param string $use
     * @param string $alg
     * @param $valid_from
     * @param $valid_to
     * @return IClientPublicKey|null
     */
    public function getCurrentPublicKeyByTypeUseAlgAndRange(string $type, string $use, string $alg, $valid_from, $valid_to)
    {
        try {
            $query = $this->createQuery("SELECT k from Models\OAuth2\ClientPublicKey k 
        JOIN k.owner c 
        WHERE 
        c.id = :client_id AND 
        k.type = :type AND
        k.usage = :use AND
        k.alg = :alg AND
        k.active = 1 AND
        k.valid_from <= :valid_from AND
        k.valid_to >= :valid_to
        ");

            return $query
                ->setParameter('client_id', $this->getIdentifier())
                ->setParameter('use', trim($use))
                ->setParameter('alg', trim($alg))
                ->setParameter('type', trim($type))
                ->setParameter('valid_from', $valid_from)
                ->setParameter('valid_to', $valid_to)
                ->getSingleResult();
        }
        catch (Exception $ex){
            return null;
        }
    }

    /**
     * @param string $post_logout_uri
     * @return bool
     */
    public function isPostLogoutUriAllowed($post_logout_uri)
    {
        if(empty($this->post_logout_redirect_uris)) return false;
        if(empty($post_logout_uri)) return false;

        if(!filter_var($post_logout_uri, FILTER_VALIDATE_URL)) return false;
        if(is_null($this->post_logout_redirect_uris)) return false;
        if(empty($this->post_logout_redirect_uris)) return false;

        $parts = @parse_url($post_logout_uri);

        if ($parts == false) {
            return false;
        }
        if($parts['scheme']!=='https')
            return false;

        $logout_without_port = $parts['scheme'].'://'.$parts['host'];

        if(str_contains($this->post_logout_redirect_uris, $logout_without_port )) return true;

        if(isset($parts['port']))
        {
            $logout_with_port    = $parts['scheme'].'://'.$parts['host'].':'.$parts['port'];
            return str_contains($this->post_logout_redirect_uris, $logout_with_port );
        }
        return false;
    }

    public function getAdminUsers(){
        return $this->admin_users;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function addAdminUser(User $user)
    {
        if($this->admin_users->contains($user)) return $this;
        $this->admin_users->add($user);
        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeAdminUser(User $user)
    {
        if(!$this->admin_users->contains($user)) return $this;
        $this->admin_users->removeElement($user);
        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllAdminUsers(){
        $this->admin_users->clear();
        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canEdit(User $user):bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $user->getId()));
        $is_admin = $this->admin_users->contains($user);
        $is_owner = intval($this->user->getId()) === intval($user->getId());
        return $is_owner || $is_admin;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canDelete(User $user):bool
    {
        return $this->isOwner($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isOwner(User $user):bool
    {
        if(!$this->hasUser()) return false;
        return intval($this->user->getId()) === intval($user->getId());
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setOwner(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param ApiScope $scope
     * @return $this
     */
    public function addScope(ApiScope $scope)
    {
        if($this->scopes->contains($scope)) return $this;
        $this->scopes->add($scope);
        return $this;
    }

    /**
     * @param ApiScope $scope
     * @return $this|void
     */
    public function removeScope(ApiScope $scope){
        if(!$this->scopes->contains($scope)) return;
        $this->scopes->removeElement($scope);
        return $this;
    }

    /**
     * @param User $editing_user
     * @return $this
     */
    public function setEditedBy(User $editing_user){
        $this->edited_by = $editing_user;
        return $this;
    }

    public function getEditedByNice()
    {
        $user = $this->edited_by;
        return is_null($user)? 'N/A':$user->getEmail();
    }

    public function getOwnerNice()
    {
        $user = $this->user;
        return is_null($user)? 'N/A':$user->getEmail();
    }

    /**
     * @return bool
     */
    public function useRefreshToken():bool
    {
        return (bool)$this->use_refresh_token;
    }

    /**
     * @return bool
     */
    public function useRotateRefreshTokenPolicy(): bool
    {
        return (bool)$this->rotate_refresh_token;
    }

    /**
     * @return bool
     */
    public function useLogoutIframe(): bool
    {
        return $this->logout_use_iframe;
    }

    /**
     * @return AccessToken[]
     */
    public function getValidAccessTokens(): array
    {
        $query = $this->createQuery("SELECT t from Models\OAuth2\AccessToken t 
        JOIN t.client c 
        WHERE c.id = :client_id AND DATEADD(t.created_at, t.lifetime, 'SECOND') >= UTC_TIMESTAMP()
        ");
        return $query
            ->setParameter('client_id', $this->getIdentifier())
            ->getResult();
    }

    /**
     * @return bool
     */
    public function getLogoutSessionRequired(): bool
    {
        return $this->logout_session_required;
    }

    /**
     * @param string $token_endpoint_auth_method
     */
    public function setTokenEndpointAuthMethod(string $token_endpoint_auth_method): void
    {
        if (!in_array($token_endpoint_auth_method, self::$valid_token_endpoint_auth_methods)) {
            throw new \InvalidArgumentException("Invalid token_endpoint_auth_method");
        }
        $this->token_endpoint_auth_method = $token_endpoint_auth_method;
    }

    /**
     * @param string $app_name
     */
    public function setAppName(string $app_name): void
    {
        $this->app_name = $app_name;
    }

    /**
     * @param string $app_description
     */
    public function setAppDescription(string $app_description): void
    {
        $this->app_description = $app_description;
    }

    /**
     * @param string $app_logo
     */
    public function setAppLogo(string $app_logo): void
    {
        $this->app_logo = $app_logo;
    }

    /**
     * @param string $client_id
     */
    public function setClientId(string $client_id): void
    {
        $this->client_id = $client_id;
    }

    /**
     * @param string $client_secret
     */
    public function setClientSecret(string $client_secret): void
    {
        $this->client_secret = $client_secret;
    }

    /**
     * @param string $client_type
     */
    public function setClientType(string $client_type): void
    {
        $this->client_type = $client_type;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * @param int $max_auth_codes_issuance_qty
     */
    public function setMaxAuthCodesIssuanceQty(int $max_auth_codes_issuance_qty): void
    {
        $this->max_auth_codes_issuance_qty = $max_auth_codes_issuance_qty;
    }

    /**
     * @param int $max_access_token_issuance_qty
     */
    public function setMaxAccessTokenIssuanceQty(int $max_access_token_issuance_qty): void
    {
        $this->max_access_token_issuance_qty = $max_access_token_issuance_qty;
    }

    /**
     * @param int $max_access_token_issuance_basis
     */
    public function setMaxAccessTokenIssuanceBasis(int $max_access_token_issuance_basis): void
    {
        $this->max_access_token_issuance_basis = $max_access_token_issuance_basis;
    }

    /**
     * @param int $max_refresh_token_issuance_qty
     */
    public function setMaxRefreshTokenIssuanceQty(int $max_refresh_token_issuance_qty): void
    {
        $this->max_refresh_token_issuance_qty = $max_refresh_token_issuance_qty;
    }

    /**
     * @param int $max_refresh_token_issuance_basis
     */
    public function setMaxRefreshTokenIssuanceBasis(int $max_refresh_token_issuance_basis): void
    {
        $this->max_refresh_token_issuance_basis = $max_refresh_token_issuance_basis;
    }

    /**
     * @param bool $use_refresh_token
     */
    public function setUseRefreshToken(bool $use_refresh_token): void
    {
        $this->use_refresh_token = $use_refresh_token;
    }

    /**
     * @param bool $rotate_refresh_token
     */
    public function setRotateRefreshToken(bool $rotate_refresh_token): void
    {
        $this->rotate_refresh_token = $rotate_refresh_token;
    }

    /**
     * @param string $website
     */
    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    /**
     * @param DateTime $client_secret_expires_at
     */
    public function setClientSecretExpiresAt(DateTime $client_secret_expires_at): void
    {
        $this->client_secret_expires_at = $client_secret_expires_at;
    }

    public function setClientSecretNoExpiration():void{
        $this->client_secret_expires_at = null;
    }

    /**
     * @param string $contacts
     */
    public function setContacts(string $contacts): void
    {
        $this->contacts = $contacts;
    }

    /**
     * @param string $logo_uri
     */
    public function setLogoUri(string $logo_uri): void
    {
        $this->logo_uri = $logo_uri;
    }

    /**
     * @param string $tos_uri
     */
    public function setTosUri(string $tos_uri): void
    {
        $this->tos_uri = $tos_uri;
    }

    /**
     * @param string $post_logout_redirect_uris
     */
    public function setPostLogoutRedirectUris(string $post_logout_redirect_uris): void
    {
        $this->post_logout_redirect_uris = $post_logout_redirect_uris;
    }

    /**
     * @param string $logout_uri
     */
    public function setLogoutUri(string $logout_uri): void
    {
        $this->logout_uri = $logout_uri;
    }

    /**
     * @param bool $logout_session_required
     */
    public function setLogoutSessionRequired(bool $logout_session_required): void
    {
        $this->logout_session_required = $logout_session_required;
    }

    /**
     * @param bool $logout_use_iframe
     */
    public function setLogoutUseIframe(bool $logout_use_iframe): void
    {
        $this->logout_use_iframe = $logout_use_iframe;
    }

    /**
     * @param string $policy_uri
     */
    public function setPolicyUri(string $policy_uri): void
    {
        $this->policy_uri = $policy_uri;
    }

    /**
     * @param string $jwks_uri
     */
    public function setJwksUri(string $jwks_uri): void
    {
        $this->jwks_uri = $jwks_uri;
    }

    /**
     * @param int $default_max_age
     */
    public function setDefaultMaxAge(int $default_max_age): void
    {
        $this->default_max_age = $default_max_age;
    }

    /**
     * @param bool $require_auth_time
     */
    public function setRequireAuthTime(bool $require_auth_time): void
    {
        $this->require_auth_time = $require_auth_time;
    }

    /**
     * @param string $token_endpoint_auth_signing_alg
     */
    public function setTokenEndpointAuthSigningAlg(string $token_endpoint_auth_signing_alg): void
    {
        $this->token_endpoint_auth_signing_alg = $token_endpoint_auth_signing_alg;
    }

    /**
     * @param string $subject_type
     */
    public function setSubjectType(string $subject_type): void
    {
        $this->subject_type = $subject_type;
    }

    /**
     * @param string $userinfo_signed_response_alg
     */
    public function setUserinfoSignedResponseAlg(string $userinfo_signed_response_alg): void
    {
        $this->userinfo_signed_response_alg = $userinfo_signed_response_alg;
    }

    /**
     * @param string $userinfo_encrypted_response_alg
     */
    public function setUserinfoEncryptedResponseAlg(string $userinfo_encrypted_response_alg): void
    {
        $this->userinfo_encrypted_response_alg = $userinfo_encrypted_response_alg;
    }

    /**
     * @param string $userinfo_encrypted_response_enc
     */
    public function setUserinfoEncryptedResponseEnc(string $userinfo_encrypted_response_enc): void
    {
        $this->userinfo_encrypted_response_enc = $userinfo_encrypted_response_enc;
    }

    /**
     * @param string $id_token_signed_response_alg
     */
    public function setIdTokenSignedResponseAlg(string $id_token_signed_response_alg): void
    {
        $this->id_token_signed_response_alg = $id_token_signed_response_alg;
    }

    /**
     * @param string $id_token_encrypted_response_alg
     */
    public function setIdTokenEncryptedResponseAlg(string $id_token_encrypted_response_alg): void
    {
        $this->id_token_encrypted_response_alg = $id_token_encrypted_response_alg;
    }

    /**
     * @param string $id_token_encrypted_response_enc
     */
    public function setIdTokenEncryptedResponseEnc(string $id_token_encrypted_response_enc): void
    {
        $this->id_token_encrypted_response_enc = $id_token_encrypted_response_enc;
    }

    /**
     * @param string $redirect_uris
     */
    public function setRedirectUris(string $redirect_uris): void
    {
        $this->redirect_uris = $redirect_uris;
    }

    /**
     * @param string $allowed_origins
     */
    public function setAllowedOrigins(string $allowed_origins): void
    {
        $this->allowed_origins = $allowed_origins;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param ResourceServer $resource_server
     */
    public function setResourceServer(ResourceServer $resource_server): void
    {
        $this->resource_server = $resource_server;
    }

    /**
     * @param ArrayCollection $public_keys
     */
    public function setPublicKeys(ArrayCollection $public_keys): void
    {
        $this->public_keys = $public_keys;
    }

    /**
     * @param ArrayCollection $access_tokens
     */
    public function setAccessTokens(ArrayCollection $access_tokens): void
    {
        $this->access_tokens = $access_tokens;
    }

    /**
     * @return ArrayCollection
     */
    public function getRefreshTokens() {
        return $this->refresh_tokens;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        if($name == 'user_id')
            return $this->getUserId();
        return $this->{$name};
    }

    public function isPKCEEnabled():bool{
        return $this->pkce_enabled;
    }

    public function enablePCKE(){
        if($this->client_type != self::ClientType_Public){
            throw new ValidationException("Only Public Clients could use PCKE.");
        }
        $this->pkce_enabled = true;
        $this->token_endpoint_auth_method = OAuth2Protocol::TokenEndpoint_AuthMethod_None;
    }

    public function disablePCKE(){
        if($this->client_type != self::ClientType_Public){
            throw new ValidationException("Only Public Clients could use PCKE.");
        }
        $this->pkce_enabled = false;
    }

    /**
     * @return bool
     */
    public function isPasswordlessEnabled(): bool
    {
        return $this->otp_enabled;
    }

    public function enablePasswordless(): void
    {
        $this->otp_enabled = true;
        $this->otp_length = intval(Config::get("otp.length"));
        $this->otp_lifetime = intval(Config::get("otp.lifetime"));
    }

    public function disablePasswordless(): void
    {
        $this->otp_enabled = false;
    }

    /**
     * @return int
     */
    public function getOtpLength(): int
    {
        $res = $this->otp_length;
        if(is_null($res)){
            $res = intval(Config::get("otp.length"));
        }
        return $res;
    }

    /**
     * @param int $otp_length
     */
    public function setOtpLength(int $otp_length): void
    {
        $this->otp_length = $otp_length;
    }

    /**
     * @return int
     */
    public function getOtpLifetime(): int
    {
        $res = $this->otp_lifetime;

        if(is_null($res)){
            $res = intval(Config::get("otp.lifetime"));
        }
        return $res;
    }

    /**
     * @param int $otp_lifetime
     */
    public function setOtpLifetime(int $otp_lifetime): void
    {
        $this->otp_lifetime = $otp_lifetime;
    }

    public function getOTPGrantsByEmailNotRedeemed(string $email){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('email', trim($email)));
        $criteria->where(Criteria::expr()->isNull("redeemed_at"));
        return $this->otp_grants->matching($criteria);
    }

    public function getOTPGrantsByPhoneNumberNotRedeemed(string $phone_number){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('phone_number', trim($phone_number)));
        $criteria->where(Criteria::expr()->isNull("redeemed_at"));
        return $this->otp_grants->matching($criteria);
    }

    public function addOTPGrant(OAuth2OTP $otp){
        if($this->otp_grants->contains($otp)) return;
        $this->otp_grants->add($otp);
        $otp->setClient($this);
    }

    public function removeOTPGrant(OAuth2OTP $otp){
        if(!$this->otp_grants->contains($otp)) return;
        $this->otp_grants->removeElement($otp);
        $otp->clearClient();
    }

    public function getOTPByValue(string $value):?OAuth2OTP{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('value', trim($value)));
        $res = $this->otp_grants->matching($criteria)->first();
        return !$res ? null : $res;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function hasOTP(string $value):bool{
        return !is_null($this->getOTPByValue($value));
    }

    /**
     * @return int
     */
    public function getMaxAllowedUserSessions(): int
    {
        return $this->max_allowed_user_sessions;
    }

    /**
     * @param int $max_allowed_user_sessions
     * @throws ValidationException
     */
    public function setMaxAllowedUserSessions(int $max_allowed_user_sessions): void
    {
        if($max_allowed_user_sessions < 0){
            throw new ValidationException("Allowed user sessions must be 0 or more.");
        }
        $this->max_allowed_user_sessions = $max_allowed_user_sessions;
    }
}