<?php namespace OAuth2\GrantTypes;
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

use App\libs\Utils\EmailUtils;
use Exception;
use Illuminate\Support\Facades\Log;
use jwe\IJWE;
use jwk\exceptions\InvalidJWKAlgorithm;
use jwk\exceptions\JWKInvalidSpecException;
use jws\exceptions\JWSInvalidJWKException;
use jws\IJWS;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\AccessDeniedException;
use OAuth2\Exceptions\ConsentRequiredException;
use OAuth2\Exceptions\InteractionRequiredException;
use OAuth2\Exceptions\InvalidApplicationType;
use OAuth2\Exceptions\InvalidClientException;
use OAuth2\Exceptions\InvalidClientType;
use OAuth2\Exceptions\InvalidLoginHint;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\Exceptions\LockedClientException;
use OAuth2\Exceptions\LoginRequiredException;
use OAuth2\Exceptions\OAuth2GenericException;
use OAuth2\Exceptions\RecipientKeyNotFoundException;
use OAuth2\Exceptions\ScopeNotAllowedException;
use OAuth2\Exceptions\ServerKeyNotFoundException;
use OAuth2\Exceptions\UriNotAllowedException;
use OAuth2\Heuristics\ClientSigningKeyFinder;
use OAuth2\Heuristics\ServerEncryptionKeyFinder;
use OAuth2\Heuristics\ServerSigningKeyFinder;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\ITokenService;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\IUserConsentService;
use OAuth2\Strategies\IOAuth2AuthenticationStrategy;
use utils\exceptions\InvalidCompactSerializationException;
use utils\factories\BasicJWTFactory;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;
use phpseclib\Crypt\Random;

/**
 * Class InteractiveGrantType
 * @package OAuth2\GrantTypes
 */
abstract class InteractiveGrantType extends AbstractGrantType
{
    /**
     * @var ISecurityContextService
     */
    protected $security_context_service;

    /**
     * @var IAuthService
     */
    protected $auth_service;

    /**
     * @var IPrincipalService
     */
    protected $principal_service;

    /**
     * @var IOAuth2AuthenticationStrategy
     */
    protected $auth_strategy;
    /**
     * @var IApiScopeService
     */

    protected $scope_service;

    /**
     * @var IUserConsentService
     */
    protected $user_consent_service;

    /**
     * @var IMementoOAuth2SerializerService
     */
    protected $memento_service;

    /**
     * @var IServerPrivateKeyRepository
     */
    private $server_private_key_repository;

    /**
     * @var IClientJWKSetReader
     */
    private $jwk_set_reader_service;

    /**
     * InteractiveGrantType constructor.
     * @param IClientService $client_service
     * @param IClientRepository $client_repository
     * @param ITokenService $token_service
     * @param ILogService $log_service
     * @param ISecurityContextService $security_context_service
     * @param IPrincipalService $principal_service
     * @param IAuthService $auth_service
     * @param IUserConsentService $user_consent_service
     * @param IApiScopeService $scope_service
     * @param IOAuth2AuthenticationStrategy $auth_strategy
     * @param IMementoOAuth2SerializerService $memento_service
     * @param IServerPrivateKeyRepository $server_private_key_repository
     * @param IClientJWKSetReader $jwk_set_reader_service
     */
    public function __construct
    (
        IClientService                  $client_service,
        IClientRepository               $client_repository,
        ITokenService                   $token_service,
        ILogService                     $log_service,
        ISecurityContextService         $security_context_service,
        IPrincipalService               $principal_service,
        IAuthService                    $auth_service,
        IUserConsentService             $user_consent_service,
        IApiScopeService                $scope_service,
        IOAuth2AuthenticationStrategy   $auth_strategy,
        IMementoOAuth2SerializerService $memento_service,
        IServerPrivateKeyRepository     $server_private_key_repository,
        IClientJWKSetReader             $jwk_set_reader_service
    )
    {
        parent::__construct($client_service, $client_repository, $token_service, $log_service);

        $this->security_context_service      = $security_context_service;
        $this->principal_service             = $principal_service;
        $this->auth_service                  = $auth_service;
        $this->user_consent_service          = $user_consent_service;
        $this->scope_service                 = $scope_service;
        $this->auth_strategy                 = $auth_strategy;
        $this->memento_service               = $memento_service;
        $this->server_private_key_repository = $server_private_key_repository;
        $this->jwk_set_reader_service        = $jwk_set_reader_service;
    }

    /**
     * @param OAuth2Request $request
     * @return OAuth2Response
     * @throws Exception
     */
    public function handle(OAuth2Request $request)
    {
        try
        {

            if (!($request instanceof OAuth2AuthorizationRequest))
            {
                throw new InvalidOAuth2Request;
            }

            Log::debug(sprintf("InteractiveGrant::handle %s", $request->__toString()));

            $client_id = $request->getClientId();

            $client    = $this->client_repository->getClientById($client_id);

            if (is_null($client)) {
                Log::warning(sprintf("InteractiveGrant::handle client_id %s does not exists!", $client_id));
                throw new InvalidClientException
                (
                    sprintf
                    (
                        "client_id %s does not exists!",
                        $client_id
                    )
                );
            }

            if (!$client->isActive() || $client->isLocked()) {
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) is locked.", $client->getApplicationName(), $client->getId()));
                throw new LockedClientException
                (
                    sprintf
                    (
                        'Client %s (%s) is locked.',
                        $client->getApplicationName(),
                        $client->getId()
                    )
                );
            }

            $this->checkClientTypeAccess($client);

            //check redirect uri
            $redirect_uri = $request->getRedirectUri();

            if (!$client->isUriAllowed($redirect_uri)) {
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) redirect_uri %s is not allowed", $client->getApplicationName(), $client_id, $redirect_uri));
                throw new UriNotAllowedException
                (
                    $redirect_uri
                );
            }

            //check requested scope
            $scope = $request->getScope();
            $this->log_service->debug_msg(sprintf("scope %s", $scope));
            if (empty($scope) || !$client->isScopeAllowed($scope)) {
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) scope %s is not allowed", $client->getApplicationName(), $client_id, $scope));
                throw new ScopeNotAllowedException($scope);
            }

            $authentication_response = $this->auth_service->getUserAuthenticationResponse();

            // user has cancelled login action
            if ($authentication_response == IAuthService::AuthenticationResponse_Cancel) {
                //clear saved data ...
                $this->memento_service->forget();
                $this->auth_service->clearUserAuthenticationResponse();
                $this->auth_service->clearUserAuthorizationResponse();

                if ($this->shouldPromptLogin($request)) {
                    Log::warning(sprintf("InteractiveGrant::handle client %s (%s) user cancelled login action", $client->getApplicationName(), $client_id));
                    throw new LoginRequiredException;
                }
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) user cancelled login action", $client->getApplicationName(), $client_id));
                throw new AccessDeniedException;
            }

            //check user logged
            if ($this->mustAuthenticateUser($request, $client)) {
                if (!$this->canInteractWithEndUser($request)) {
                    Log::warning(sprintf("InteractiveGrant::handle client %s (%s) user not logged", $client->getApplicationName(), $client_id));
                    throw new LoginRequiredException;
                }

                $this->memento_service->serialize($request->getMessage()->createMemento());
                Log::debug(sprintf("InteractiveGrant::handle client %s (%s) user not logged", $client->getApplicationName(), $client_id));
                return $this->auth_strategy->doLogin($request);
            }

            $approval_prompt = $request->getApprovalPrompt();
            $user = $this->auth_service->getCurrentUser();

            // check if logged user its the same as login hint
            $requested_user_id = $this->security_context_service->get()->getRequestedUserId();

            if (is_null($user)) {
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) invalid current user", $client->getApplicationName(), $client_id));
                throw new OAuth2GenericException("Invalid Current User.");
            }

            if (!is_null($requested_user_id) && $requested_user_id !== $user->getId()) {
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) invalid login hint", $client->getApplicationName(), $client_id));
                $this->auth_service->logout();
                throw new InvalidLoginHint('invalid login hint.');
            }

            if(!$this->token_service->canCreateAccessToken($user, $client)){
                Log::warning(sprintf("InteractiveGrant::handle client %s (%s) max allowed sessions reached", $client->getApplicationName(), $client_id));
                throw new OAuth2GenericException
                (
                    sprintf
                    (
                        "Only %s session(s) is allowed to use this app. Logout of any other active sessions to login on this device.",
                        $client->getMaxAllowedUserSessions()
                    )
                );
            }

            $authorization_response = $this->auth_service->getUserAuthorizationResponse();

            Log::debug(sprintf("InteractiveGrant::handle client %s (%s) authorization_response %s", $client->getApplicationName(), $client_id, $authorization_response));

            if ($authorization_response == IAuthService::AuthorizationResponse_DenyOnce) {
                if ($this->hadPromptConsent($request)) {
                    Log::warning(sprintf("InteractiveGrant::handle client %s (%s) user denied access to your application", $client->getApplicationName(), $client_id));
                    throw new ConsentRequiredException('the user denied access to your application');
                }
                throw new AccessDeniedException;
            }

            // check for former user consents
            Log::debug(sprintf("InteractiveGrant::handle trying to get former consent client %s (%s) scopes %s", $client->getApplicationName(), $client_id, $scope));
            $former_user_consent   = $user->findFirstConsentByClientAndScopes($client, $scope);
            $auto_approval         = $approval_prompt == OAuth2Protocol::OAuth2Protocol_Approval_Prompt_Auto;
            $has_former_consent    = !is_null($former_user_consent);
            $should_prompt_consent = $this->shouldPromptConsent($request);

            Log::debug
            (
                sprintf
                (
                    "InteractiveGrant::handle client %s (%s) authorization_response %s has_former_consent %b should_prompt_consent %b",
                    $client->getApplicationName(),
                    $client_id,
                    $authorization_response,
                    $has_former_consent,
                    $should_prompt_consent
                )
            );

            if((!$should_prompt_consent && $has_former_consent && $auto_approval) || $authorization_response == IAuthService::AuthorizationResponse_AllowOnce) {
                // emit response ...
                Log::debug("InteractiveGrant::handle bypassing consent");
                $this->auth_service->registerRPLogin($client_id);
                //save positive consent
                if (is_null($former_user_consent)) {
                    $this->user_consent_service->addUserConsent($user, $client, $scope);
                }
                $response = $this->buildResponse($request, $has_former_consent);
                // clear save data ...
                $this->auth_service->clearUserAuthorizationResponse();
                $this->memento_service->forget();
                return $response;
            }

            if (!$this->canInteractWithEndUser($request))
                throw new InteractionRequiredException;

            $this->memento_service->serialize($request->getMessage()->createMemento());
            Log::debug
            (
                sprintf
                (
                    "InteractiveGrant::handle doing consent client %s (%s) authorization_response %s has_former_consent %b should_prompt_consent %b",
                    $client->getApplicationName(),
                    $client_id,
                    $authorization_response,
                    $has_former_consent,
                    $should_prompt_consent
                )
            );
            return $this->auth_strategy->doConsent($request);
        }
        catch(Exception $ex)
        {
            $this->log_service->warning($ex);
            // clear save data ...
            $this->auth_service->clearUserAuthorizationResponse();
            $this->memento_service->forget();
            throw $ex;
        }
    }

    /**
     * @param string $origin
     * @param string $client_id
     * @param string $session_id
     * @return string
     */
    public function getSessionState($origin, $client_id, $session_id)
    {

        $this->log_service->debug_msg(sprintf(
            "InteractiveGrantType::getSessionState origin %s client_id %s session_id %s",
            $origin,
            $client_id,
            $session_id
        ));

        $salt    = bin2hex(Random::string(16));
        $message = "{$client_id}{$origin}{$session_id}{$salt}";
        $this->log_service->debug_msg(sprintf(
            "InteractiveGrantType::getSessionState message %s",
            $message
        ));
        $hash = hash('sha256', $message);
        $this->log_service->debug_msg(sprintf(
            "InteractiveGrantType::getSessionState hash %s",
            $hash
        ));
        $session_state = $hash. '.' . $salt;
        $this->log_service->debug_msg(sprintf(
            "InteractiveGrantType::getSessionState session_state %s",
            $session_state
        ));

        return $session_state;
    }

    /**
     * @param string $url
     * @return string
     */
    static public function getOrigin($url)
    {
        $url_parts = @parse_url($url);
        return sprintf("%s://%s%s", $url_parts['scheme'], $url_parts['host'], isset($url_parts['port']) ? ':' . $url_parts['port'] : '');
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @param  bool $has_former_consent
     * @return OAuth2Response
     */
    abstract protected function buildResponse(OAuth2AuthorizationRequest $request, $has_former_consent);

    /**
     * @param IClient $client
     * @throws InvalidApplicationType
     * @throws InvalidClientType
     * @return void
     */
    abstract protected function checkClientTypeAccess(IClient $client);

    /**
     * @param OAuth2AuthorizationRequest $request
     * @return bool
     */
    protected function canInteractWithEndUser(OAuth2AuthorizationRequest $request)
    {
        if($request instanceof OAuth2AuthenticationRequest && in_array(OAuth2Protocol::OAuth2Protocol_Prompt_None, $request->getPrompt()))
        {
            return false;
        }
        return true;
    }

    /**
     * @param OAuth2AuthenticationRequest $request
     * @param Client $client
     * @throws InteractionRequiredException
     * @throws InvalidClientException
     * @throws InvalidClientType
     * @throws InvalidCompactSerializationException
     * @throws InvalidJWKAlgorithm
     * @throws InvalidLoginHint
     * @throws JWKInvalidSpecException
     * @throws JWSInvalidJWKException
     * @throws ServerKeyNotFoundException
     * @throws \Auth\Exceptions\AuthenticationException
     * @throws \jwa\cryptographic_algorithms\exceptions\InvalidAuthenticationTagException
     * @throws \jwe\exceptions\JWEInvalidRecipientKeyException
     * @throws \jwe\exceptions\JWEUnsupportedContentEncryptionAlgorithmException
     * @throws \jwe\exceptions\JWEUnsupportedKeyManagementAlgorithmException
     * @throws \jwk\exceptions\InvalidJWKType
     * @throws \jws\exceptions\JWSInvalidPayloadException
     * @throws \jws\exceptions\JWSNotSupportedAlgorithm
     */
    protected function processUserHint(OAuth2AuthenticationRequest $request, Client $client)
    {
        $login_hint = $request->getLoginHint();
        $token_hint = $request->getIdTokenHint();
        $otp_login_hint = $request->getOTPLoginHint();

        Log::debug(sprintf("InteractiveGrant::processUserHint request %s client %s", $request->__toString(), $client->getId()));
        // process login hint
        $user = null;
        if (!empty($otp_login_hint) && !empty ($login_hint)
            && !$request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_OTP_LoginHint)) {

            Log::debug("InteractiveGrant::processUserHint processing OTP hint...");
            $otpClaim = OAuth2OTP::fromParams($login_hint, OAuth2Protocol::OAuth2PasswordlessConnectionInline, $otp_login_hint, $request->getScope(),);
            $this->auth_service->loginWithOTP($otpClaim, $client, true);
            $user = $this->auth_service->getUserByUsername($otpClaim->getUserName());
            Log::debug(sprintf("InteractiveGrant::processUserHint processing OTP hint. got user %s (%s)", $user->getEmail(), $user->getId()));
            $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_LoginHint);
            $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_OTP_LoginHint);
        } else if (!empty ($login_hint) && !$request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_LoginHint)) {
            Log::debug("InteractiveGrant::processUserHint processing Login hint...");

            if (EmailUtils::isValidEmail($login_hint)) {
                $user = $this->auth_service->getUserByUsername($login_hint);
            } else {
                $user_id = $this->auth_service->unwrapUserId($login_hint);
                $user    = $this->auth_service->getUserById($user_id);
            }
            $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_LoginHint);
        } else if(!empty($token_hint)) {
            Log::debug("InteractiveGrant::processUserHint processing Token hint...");

            $jwt = BasicJWTFactory::build($token_hint);

            if($jwt instanceof IJWE) {
                $this->log_service->debug_msg("InteractiveGrantType::processUserHint token hint is IJWE");
                // decrypt using server key
                $heuristic              = new ServerEncryptionKeyFinder($this->server_private_key_repository);
                $server_enc_private_key = $heuristic->find
                (
                    $client,
                    $client->getIdTokenResponseInfo()->getEncryptionKeyAlgorithm()
                );

                $jwt->setRecipientKey($server_enc_private_key);

                $payload = $jwt->getPlainText();
                $jwt     = BasicJWTFactory::build($payload);
            }
            if($jwt instanceof IJWS) {
                $this->log_service->debug_msg("InteractiveGrantType::processUserHint token hint is IJWS");
                // signed by client ?
                try {
                    $heuristic = new ClientSigningKeyFinder($this->jwk_set_reader_service);
                    $client_public_sig_key = $heuristic->find
                    (
                        $client,
                        $client->getIdTokenResponseInfo()->getSigningAlgorithm()
                    );

                    $jwt->setKey($client_public_sig_key);
                } catch(RecipientKeyNotFoundException $ex) {
                    // try to find the server signing key used ...
                    $this->log_service->debug_msg("InteractiveGrantType::processUserHint token hint is IJWS -> RecipientKeyNotFoundException");
                    $heuristic = new ServerSigningKeyFinder($this->server_private_key_repository);
                    $server_private_sig_key = $heuristic->find
                    (
                        $client,
                        $client->getIdTokenResponseInfo()->getSigningAlgorithm(),
                        $jwt->getJOSEHeader()->getKeyID()->getValue()
                    );
                    $jwt->setKey($server_private_sig_key);
                }

                $verified = $jwt->verify($jwt->getJOSEHeader()->getAlgorithm()->getString());

                if(!$verified)
                    throw new InvalidLoginHint('invalid id_token_hint');
            }

            $sub     = $jwt->getClaimSet()->getSubject();
            $user_id = $this->auth_service->unwrapUserId($sub->getString());
            $user    = $this->auth_service->getUserById($user_id);

            $jti = $jwt->getClaimSet()->getJWTID();
            if(is_null($jti)) throw new InvalidLoginHint('invalid jti!');

            $this->auth_service->reloadSession($jti->getValue());

        }
        if(!is_null($user))
        {
            $this->log_service->debug_msg("InteractiveGrantType::processUserHint: checking principal");
            $logged_user = $this->auth_service->getCurrentUser();
            if
            (
                !is_null($logged_user) &&
                $logged_user->getId() !== $user->getId()
            ) {
                $this->log_service->debug_msg(sprintf("InteractiveGrantType::processUserHint: logged user %s user %s", $logged_user->getId(), $user->getId()));

                $this->auth_service->logout();

                if (!$this->canInteractWithEndUser($request)) {
                    $this->log_service->debug_msg("InteractiveGrantType::processUserHint: cant interact with user");
                    throw new InteractionRequiredException;
                }
            }

            $this->security_context_service->save
            (
                $this->security_context_service->get()->setRequestedUserId
                (
                    $user->getId()
                )
            );
        }
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @return bool
     */
    protected function shouldPromptLogin(OAuth2AuthorizationRequest $request)
    {
        Log::debug(sprintf("InteractiveGrant::shouldPromptLogin %s", $request->__toString()));

        if
        (
            $request instanceof OAuth2AuthenticationRequest &&
            $request->hasParam(OAuth2Protocol::OAuth2Protocol_Prompt) &&
            !$request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_Prompt_Login) &&
            in_array(OAuth2Protocol::OAuth2Protocol_Prompt_Login, $request->getPrompt())
        )
        {
            $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_Prompt_Login);
            return true;
        }
        return false;
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @return bool
     */
    protected function shouldPromptConsent(OAuth2AuthorizationRequest $request):bool
    {
        Log::debug(sprintf("InteractiveGrant::shouldPromptConsent %s", $request->__toString()));
        if
        (
            $request instanceof OAuth2AuthenticationRequest &&
            $request->hasParam(OAuth2Protocol::OAuth2Protocol_Prompt) &&
            !$request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_Prompt_Consent) &&
            in_array(OAuth2Protocol::OAuth2Protocol_Prompt_Consent, $request->getPrompt())
        )
        {
            $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_Prompt_Consent);
            return true;
        }
        return false;
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @return bool
     */
    protected function hadPromptConsent(OAuth2AuthorizationRequest $request)
    {
        if
        (
            $request instanceof OAuth2AuthenticationRequest &&
            in_array(OAuth2Protocol::OAuth2Protocol_Prompt_Consent, $request->getPrompt())
        )
        {
            return true;
        }
        return false;
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @param IClient $client
     * @return bool
     * @throws InteractionRequiredException
     */
    protected function shouldForceReLogin(OAuth2AuthorizationRequest $request, IClient $client)
    {
        $now       = time();
        $principal = $this->principal_service->get();

        if($request instanceof OAuth2AuthenticationRequest)
        {

            $max_age         = $request->getMaxAge();
            $default_max_age = $client->getDefaultMaxAge();

            if(is_null($max_age) && $default_max_age > 0)
                $max_age = $default_max_age;

            if(!is_null($max_age) && $max_age > 0)
            {
                // must required then auth_time claim
                $this->security_context_service->save
                (
                    $this->security_context_service->get()->setAuthTimeRequired(true)
                );

                if
                (
                    !is_null($principal) &&
                    $principal->getAuthTime() > 0 &&
                    ($now - $principal->getAuthTime()) > $max_age
                )
                {
                    if (!$this->canInteractWithEndUser($request))
                    {
                        throw new InteractionRequiredException;
                    }

                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @param Client $client
     * @return bool
     * @throws LoginRequiredException
     */
    protected function mustAuthenticateUser(OAuth2AuthorizationRequest $request, Client $client)
    {

        if ($request instanceof OAuth2AuthenticationRequest) {
            try {
                $this->processUserHint($request, $client);
            } catch (JWSInvalidJWKException $ex) {
                Log::warning($ex);
                throw $ex;
            }
            catch (JWKInvalidSpecException $ex){
                Log::warning($ex);
                throw $ex;
            }
            catch (Exception $ex){
                Log::warning($ex);
                return true;
            }
        }

        if($this->shouldPromptLogin($request))
        {
            $this->auth_service->logout(false);
            return true;
        }

        if($this->shouldForceReLogin($request, $client))
        {
            $this->auth_service->logout(false);
            return true;
        }

        if (!$this->auth_service->isUserLogged())
            return true;

        return false;
    }
}