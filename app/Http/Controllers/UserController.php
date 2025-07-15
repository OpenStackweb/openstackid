<?php namespace App\Http\Controllers;
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

use App\Http\Controllers\OpenId\DiscoveryController;
use App\Http\Controllers\OpenId\OpenIdController;
use App\Http\Controllers\Traits\JsonResponses;
use App\Http\Utils\CountryList;
use App\libs\OAuth2\Strategies\LoginHintProcessStrategy;
use App\ModelSerializers\SerializerRegistry;
use Auth\Exceptions\AuthenticationException;
use Auth\Exceptions\UnverifiedEmailMemberException;
use App\Services\Auth\IUserService as AuthUserService;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Repositories\IClientRepository;
use OpenId\Services\IUserService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IResourceServerService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\ITokenService;
use OpenId\Services\IMementoOpenIdSerializerService;
use OpenId\Services\ITrustedSitesService;
use Services\IUserActionService;
use Sokil\IsoCodes\IsoCodesFactory;
use Strategies\DefaultLoginStrategy;
use Strategies\IConsentStrategy;
use Strategies\OAuth2ConsentStrategy;
use Strategies\OAuth2LoginStrategy;
use Strategies\OpenIdConsentStrategy;
use Strategies\OpenIdLoginStrategy;
use Utils\Services\IAuthService;
use Utils\Services\IServerConfigurationService;
use Utils\Services\IServerConfigurationService as IUtilsServerConfigurationService;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
final class UserController extends OpenIdController
{
    /**
     * @var IMementoOpenIdSerializerService
     */
    private $openid_memento_service;
    /**
     * @var IMementoOAuth2SerializerService
     */
    private $oauth2_memento_service;
    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IServerConfigurationService
     */
    private $server_configuration_service;
    /**
     * @var DiscoveryController
     */
    private $discovery;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var AuthUserService
     */
    private $auth_user_service;
    /**
     * @var IUserActionService
     */
    private $user_action_service;
    /**
     * @var DefaultLoginStrategy
     */
    private $login_strategy;
    /**
     * @var IConsentStrategy
     */
    private $consent_strategy;
    /**
     * @var IClientRepository
     */
    private $client_repository;
    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;
    /**
     * @var ITokenService
     */
    private $token_service;
    /**
     * @var IResourceServerService
     */
    private $resource_server_service;
    /**
     * @var IUtilsServerConfigurationService
     */
    private $utils_configuration_service;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @param IMementoOpenIdSerializerService $openid_memento_service
     * @param IMementoOAuth2SerializerService $oauth2_memento_service
     * @param IAuthService $auth_service
     * @param IUtilsServerConfigurationService $server_configuration_service
     * @param ITrustedSitesService $trusted_sites_service
     * @param DiscoveryController $discovery
     * @param IUserService $user_service
     * @param AuthUserService $auth_user_service
     * @param IUserActionService $user_action_service
     * @param IClientRepository $client_repository
     * @param IApiScopeRepository $scope_repository
     * @param ITokenService $token_service
     * @param IResourceServerService $resource_server_service
     * @param IUtilsServerConfigurationService $utils_configuration_service
     * @param ISecurityContextService $security_context_service
     * @param LoginHintProcessStrategy $login_hint_process_strategy
     */
    public function __construct
    (
        IMementoOpenIdSerializerService $openid_memento_service,
        IMementoOAuth2SerializerService $oauth2_memento_service,
        IAuthService $auth_service,
        IServerConfigurationService $server_configuration_service,
        ITrustedSitesService $trusted_sites_service,
        DiscoveryController $discovery,
        IUserService $user_service,
        AuthUserService $auth_user_service,
        IUserActionService $user_action_service,
        IClientRepository $client_repository,
        IApiScopeRepository $scope_repository,
        ITokenService $token_service,
        IResourceServerService $resource_server_service,
        IUtilsServerConfigurationService $utils_configuration_service,
        ISecurityContextService $security_context_service,
        LoginHintProcessStrategy $login_hint_process_strategy
    )
    {
        $this->openid_memento_service = $openid_memento_service;
        $this->oauth2_memento_service = $oauth2_memento_service;
        $this->auth_service = $auth_service;
        $this->server_configuration_service = $server_configuration_service;
        $this->trusted_sites_service = $trusted_sites_service;
        $this->discovery = $discovery;
        $this->user_service = $user_service;
        $this->auth_user_service = $auth_user_service;
        $this->user_action_service = $user_action_service;
        $this->client_repository = $client_repository;
        $this->scope_repository = $scope_repository;
        $this->token_service = $token_service;
        $this->resource_server_service = $resource_server_service;
        $this->utils_configuration_service = $utils_configuration_service;
        $this->security_context_service = $security_context_service;

        $this->middleware(function ($request, $next) use($login_hint_process_strategy){

            Log::debug(sprintf("UserController::middleware route %s %s", $request->getMethod(), $request->getRequestUri()));

            if ($this->openid_memento_service->exists()) {
                //openid stuff
                Log::debug(sprintf("UserController::middleware OIDC"));
                $this->login_strategy = new OpenIdLoginStrategy
                (
                    $this->openid_memento_service,
                    $this->user_action_service,
                    $this->auth_service,
                    $login_hint_process_strategy
                );

                $this->consent_strategy = new OpenIdConsentStrategy
                (
                    $this->openid_memento_service,
                    $this->auth_service,
                    $this->server_configuration_service,
                    $this->user_action_service
                );

            } else if ($this->oauth2_memento_service->exists()) {
                Log::debug(sprintf("UserController::middleware OAUTH2"));
                $this->login_strategy = new OAuth2LoginStrategy
                (
                    $this->auth_service,
                    $this->oauth2_memento_service,
                    $this->user_action_service,
                    $login_hint_process_strategy
                );

                $this->consent_strategy = new OAuth2ConsentStrategy
                (
                    $this->auth_service,
                    $this->oauth2_memento_service,
                    $this->scope_repository,
                    $this->client_repository
                );
            } else {
                //default stuff
                Log::debug(sprintf("UserController::middleware DEFAULT"));
                $this->login_strategy = new DefaultLoginStrategy
                (
                    $this->user_action_service,
                    $this->auth_service,
                    $login_hint_process_strategy
                );
                $this->consent_strategy = null;
            }

            return $next($request);
        });
    }

    public function getLogin()
    {
        return $this->login_strategy->getLogin();
    }

    public function cancelLogin()
    {
        return $this->login_strategy->cancelLogin();
    }

    use JsonResponses;

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAccount()
    {
        try {
            $email = Request::input("email", "");
            if (empty($email)) {
                throw new ValidationException("empty email.");
            }

            $user = $this->auth_service->getUserByUsername($email);

            if (is_null($user))
                throw new EntityNotFoundException();

            return $this->ok(
                [
                    'is_active' => $user->isActive(),
                    'is_verified' => $user->isEmailVerified(),
                    'pic' => $user->getPic(),
                    'full_name' => $user->getFullName(),
                    'has_password_set' => $user->hasPasswordSet(),
                ]
            );
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function emitOTP()
    {
        try {

            $username = Request::input("username", "");
            $connection = Request::input("connection", "");
            $send = Request::input("send", "");

            if (empty($username)) {
                throw new ValidationException("empty username param.");
            }

            if (empty($connection)) {
                throw new ValidationException("empty connectin param.");
            }

            if (empty($send)) {
                throw new ValidationException("empty send param.");
            }

            $user = $this->auth_service->getUserByUsername($username);

            if (!$user->isActive())
                throw new ValidationException
                (
                    sprintf
                    (
                        "Your user account is currently locked. Please <a href='mailto:%s'>contact support</a> for further assistance.",
                        Config::get("app.help_email")
                    )
                );

            $client = null;

            // check if we have a former oauth2 request
            if ($this->oauth2_memento_service->exists()) {

                Log::debug("UserController::getOTP exist a oauth auth request on session");

                $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    OAuth2Message::buildFromMemento($this->oauth2_memento_service->load())
                );

                if ($oauth_auth_request->isValid()) {

                    $client_id = $oauth_auth_request->getClientId();

                    $client = $this->client_repository->getClientById($client_id);
                    if (is_null($client))
                        throw new ValidationException("Client does not exists.");

                    $this->oauth2_memento_service->serialize($oauth_auth_request->getMessage()->createMemento());
                }
            }

            $otp = $this->token_service->createOTPFromPayload([
                OAuth2Protocol::OAuth2PasswordlessConnection => $connection,
                OAuth2Protocol::OAuth2PasswordlessSend => $send,
                OAuth2Protocol::OAuth2PasswordlessEmail => ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionEmail) ? $username : null,
                OAuth2Protocol::OAuth2PasswordlessPhoneNumber => ($connection == OAuth2Protocol::OAuth2PasswordlessConnectionSMS) ? $username : null
            ], $client);

            return $this->created([
                'otp_length' => $otp->getLength(),
                'otp_lifetime' => $otp->getLifetime(),
            ]);
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function resendVerificationEmail(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = Validator::make($payload, [
                'email' => 'required|string|email|max:255'
            ]);

            if (!$validator->passes()) {
                return $this->error412($validator->getMessageBag()->getMessages());
            }
            $this->auth_user_service->resendVerificationEmail($payload);
            return $this->ok();
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function postLogin()
    {
        $max_login_attempts_2_show_captcha = $this->server_configuration_service->getConfigValue("MaxFailed.LoginAttempts.2ShowCaptcha");
        $max_login_failed_attempts = intval($this->server_configuration_service->getConfigValue("MaxFailed.Login.Attempts"));
        $login_attempts                    = 0;
        $username                          = '';
        $user = null;

        try
        {

            $data = Request::all();

            if (isset($data['username']))
                $data['username'] = trim($data['username']);

            if (isset($data['password']))
                $data['password'] = trim($data['password']);

            $login_attempts = intval(Request::input('login_attempts'));
            // Build the validation constraint set.
            $rules = [
                'username' => 'required|email',
                'password' => 'required',
                'flow' => 'required|in:otp,password',
                'connection' => 'sometimes|string|in:sms,email',
            ];

            if ($login_attempts >= $max_login_attempts_2_show_captcha) {
                $rules['g-recaptcha-response'] = 'required|recaptcha';
            }
            // Create a new validator instance.
            $validator = Validator::make($data, $rules);

            if ($validator->passes()) {

                $username = $data['username'];
                $password = $data['password'];
                $flow     = $data['flow'];
                $remember = Request::input("remember");
                $remember = !is_null($remember);
                $connection = $data['connection'] ?? null;

                try {
                    if ($flow == "password" && $this->auth_service->login($username, $password, $remember)) {
                        return $this->login_strategy->postLogin();
                    }

                    if ($flow == "otp") {

                        $client = null;

                        // check if we have a former oauth2 request
                        if ($this->oauth2_memento_service->exists()) {

                            Log::debug("UserController::postLogin exist a oauth auth request on session");

                            $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                            (
                                OAuth2Message::buildFromMemento($this->oauth2_memento_service->load())
                            );

                            if ($oauth_auth_request->isValid()) {

                                $client_id = $oauth_auth_request->getClientId();

                                $client = $this->client_repository->getClientById($client_id);
                                if (is_null($client))
                                    throw new ValidationException("client does not exists");

                                $this->oauth2_memento_service->serialize($oauth_auth_request->getMessage()->createMemento());
                            }
                        }

                        $otpClaim = OAuth2OTP::fromParams($username, $connection, $password);
                        $this->auth_service->loginWithOTP($otpClaim, $client);
                        return $this->login_strategy->postLogin();
                    }
                } catch (AuthenticationException $ex) {
                    // failed login attempt...

                    $user = $this->auth_service->getUserByUsername($username);
                    if (!is_null($user)) {
                        $login_attempts = $user->getLoginFailedAttempt();
                    }

                    return $this->login_strategy->errorLogin
                    (
                        [
                            'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                            'max_login_failed_attempts' => $max_login_failed_attempts,
                            'login_attempts' => $login_attempts,
                            'error_message' => $ex->getMessage(),
                            'user_fullname' => !is_null($user) ? $user->getFullName() : "",
                            'user_pic' => !is_null($user) ? $user->getPic(): "",
                            'user_verified' => true,
                            'username' => $username,
                            'flow' => $flow,
                            'user_is_active' => !is_null($user) ? ($user->isActive() ? 1 : 0) : 0
                        ]
                    );
                }

            }

            // validator errors
            $response_data =    [
                'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                'max_login_failed_attempts'         => $max_login_failed_attempts,
                'login_attempts'                    => $login_attempts,
                'validator'                         => $validator,
            ];

            if (is_null($user) && isset($data['username'])) {
                $user = $this->auth_service->getUserByUsername($data['username']);
            }

            if(!is_null($user)){
                $response_data['user_fullname'] = $user->getFullName();
                $response_data['user_pic'] = $user->getPic();
                $response_data['user_verified'] = 1;
                $response_data['user_is_active'] = $user->isActive() ? 1 : 0;
            }

            return $this->login_strategy->errorLogin
            (
                $response_data
            );

        } catch (UnverifiedEmailMemberException $ex1) {
            Log::warning($ex1);

            $user = $this->auth_service->getUserByUsername($username);

            $response_data =    [
                'max_login_attempts_2_show_captcha' => $max_login_attempts_2_show_captcha,
                'max_login_failed_attempts'         => $max_login_failed_attempts,
                'login_attempts'                    => $login_attempts,
                'username'                          => $username,
                'error_message'                     => $ex1->getMessage(),
            ];

            if (is_null($user) && isset($data['username'])) {
                $user = $this->auth_service->getUserByUsername($data['username']);
            }

            if(!is_null($user)){
                $response_data['user_fullname'] = $user->getFullName();
                $response_data['user_pic'] = $user->getPic();
                $response_data['user_verified'] = 1;
                $response_data['user_is_active'] = $user->isActive() ? 1 : 0;
            }

            return $this->login_strategy->errorLogin
            (
                $response_data
            );
        } catch (AuthenticationException $ex2) {
            Log::warning($ex2);
            return Redirect::action('UserController@getLogin');
        } catch (Exception $ex) {
            Log::error($ex);
            return Redirect::action('UserController@getLogin');
        }
    }

    /**
     * @return \Illuminate\Http\Response|mixed
     */
    public function getConsent()
    {
        Log::debug("UserController::getConsent");
        if (is_null($this->consent_strategy)) {

            Log::warning(sprintf("UserController::getConsent consent strategy is null. request %s %s", Request::method(), Request::path()));
            return Response::view
            (
                'errors.400',
                [
                    'error' => "Bad Request",
                    'error_description' => "Generic Error"
                ],
                400
            );
        }
        return $this->consent_strategy->getConsent();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
     */
    public function postConsent()
    {
        try {
            $data = Request::all();
            $rules = array
            (
                'trust' => 'required|oauth2_trust_response',
            );
            // Create a new validator instance.
            $validator = Validator::make($data, $rules);
            if ($validator->passes()) {
                if (is_null($this->consent_strategy)) {

                    Log::error(sprintf("UserController::postConsent consent strategy is null. request %s %s", Request::method(), Request::path()));

                    return Response::view
                    (
                        'errors.400',
                        [
                            'error' => "Bad Request",
                            'error_description' => "Generic Error"
                        ],
                        400
                    );
                }

                return $this->consent_strategy->postConsent(Request::input("trust"));
            }
            return Redirect::action('UserController@getConsent')->withErrors($validator);
        } catch (Exception $ex) {
            Log::error($ex);
            return Redirect::action('UserController@getConsent');
        }
    }

    public function getIdentity($identifier)
    {
        try {
            $user = $this->auth_service->getUserByOpenId($identifier);
            if (is_null($user)) {
                return View::make("errors.404");
            }

            if ($this->isDiscoveryRequest()) {
                /*
                * If the Claimed Identifier was not previously discovered by the Relying Party
                * (the "openid.identity" in the request was "http://specs.openid.net/auth/2.0/identifier_select"
                * or a different Identifier, or if the OP is sending an unsolicited positive assertion),
                * the Relying Party MUST perform discovery on the Claimed Identifier in
                * the response to make sure that the OP is authorized to make assertions about the Claimed Identifier.
                */
                return $this->discovery->user($identifier);
            }

            $redirect = Session::get('backurl');
            if (!empty($redirect)) {
                Session::forget('backurl');
                Session::save();
                return Redirect::to($redirect);
            }

            $current_user = $this->auth_service->getCurrentUser();
            $another_user = false;
            if ($current_user && $current_user->getIdentifier() != $user->getIdentifier()) {
                $another_user = true;
            }

            $assets_url = $this->utils_configuration_service->getConfigValue("Assets.Url");
            $pic_url = $user->getPic();
            $pic_url = str_contains($pic_url, 'http') ? $pic_url : $assets_url . $pic_url;

            $params = [
                'show_fullname' => $user->getShowProfileFullName(),
                'username' => $user->getFullName(),
                'show_email' => $user->getShowProfileEmail(),
                'email' => $user->getEmail(),
                'identifier' => $user->getIdentifier(),
                'show_pic' => $user->getShowProfilePic(),
                'pic' => $pic_url,
                'another_user' => $another_user,
            ];

            return View::make("identity", $params);
        } catch (Exception $ex) {
            Log::error($ex);
            return View::make("errors.404");
        }
    }

    public function logout()
    {
        $user = $this->auth_service->getCurrentUser();
        //RevokeUserGrantsOnExplicitLogout::dispatch($user)->afterResponse();
        $this->auth_service->logout();
        Session::flush();
        Session::regenerate();
        return Redirect::action("UserController@getLogin");
    }

    public function getProfile()
    {
        $user = $this->auth_service->getCurrentUser();
        $sites = $user->getTrustedSites();
        $actions = $user->getLatestNActions(10);

        // init database
        $isoCodes = new IsoCodesFactory();

        // get languages database
        $languages = $isoCodes->getLanguages()->toArray();
        $lang2Code = [];
        foreach ($languages as $lang) {
            if (!empty($lang->getAlpha2()))
                $lang2Code[] = $lang;
        }

        return View::make("profile", [
            'user' => json_encode(SerializerRegistry::getInstance()->getSerializer(
                $user, SerializerRegistry::SerializerType_Private)->serialize()),
            "openid_url" => $this->server_configuration_service->getUserIdentityEndpointURL($user->getIdentifier()),
            "sites" => $sites,
            'actions' => $actions,
            'countries' => CountryList::getCountries(),
            'languages' => $lang2Code,
        ]);
    }

    public function deleteTrustedSite($id)
    {
        $this->trusted_sites_service->delete($id);
        return Redirect::action("UserController@getProfile");
    }

}