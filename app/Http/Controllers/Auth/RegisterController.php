<?php namespace App\Http\Controllers\Auth;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Http\Controllers\Controller;
use App\Http\Utils\CountryList;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Exception;
/**
 * Class RegisterController
 * @package App\Http\Controllers\Auth
 */
final class RegisterController extends Controller
{
    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IMementoOAuth2SerializerService
     */
    private $memento_service;

    public function __construct
    (
        IClientRepository $client_repository,
        IUserService $user_service,
        IMementoOAuth2SerializerService $memento_service
    )
    {
        $this->user_service = $user_service;
        $this->client_repository = $client_repository;
        $this->memento_service = $memento_service;
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     */
    public function showRegistrationForm(LaravelRequest $request)
    {
        try {

            // if we already logged in ... continue flow
            if(Auth::check()){
                Log::warning("RegisterController::showRegistrationForm user already logged in, checking if we have a client id");
                if ($request->has("redirect_uri") && $request->has("client_id")) {
                    $redirect_uri = $request->get("redirect_uri");
                    $client_id = $request->get("client_id");
                    Log::debug(sprintf("RegisterController::showRegistrationForm redirect_uri %s client_id %s", $redirect_uri, $client_id));
                    $client = $this->client_repository->getClientById($client_id);
                    if (is_null($client))
                        throw new ValidationException("Client does not exists.");

                    if (!$client->isUriAllowed($redirect_uri))
                        throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client.", $redirect_uri));

                    Log::debug(sprintf("RegisterController::showRegistrationForm redirect_uri %s client_id %s redirecting", $redirect_uri, $client_id));
                    return Redirect::to($redirect_uri);
                }
                Log::debug("RegisterController::showRegistrationForm redirecting to home page");
                return Redirect::to('/');
            }

            $params = [
                "redirect_uri" => '',
                "email"        => '',
                "first_name"   => '',
                "last_name"    => '',
                "client_id"    => '',
                'countries'    => CountryList::getCountries()
            ];

            // check if we have a former oauth2 request
            if ($this->memento_service->exists()) {

                Log::debug("RegisterController::showRegistrationForm exist a oauth auth request on session");

                $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    OAuth2Message::buildFromMemento($this->memento_service->load())
                );

                if ($oauth_auth_request->isValid()) {

                    $redirect_uri = $oauth_auth_request->getRedirectUri();
                    $client_id = $oauth_auth_request->getClientId();

                    Log::debug(sprintf( "RegisterController::showRegistrationForm exist a oauth auth request is valid for client id %s", $client_id));
                    $client = $this->client_repository->getClientById($client_id);
                    if (is_null($client))
                        throw new ValidationException("client does not exists");

                    if (!$client->isUriAllowed($redirect_uri))
                        throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client", $redirect_uri));

                    $this->memento_service->serialize($oauth_auth_request->getMessage()->createMemento());
                }
            }

            // check if we have explicit params at query string
            if ($request->has("redirect_uri") && $request->has("client_id")) {
                $redirect_uri = $request->get("redirect_uri");
                $client_id = $request->get("client_id");

                $client = $this->client_repository->getClientById($client_id);
                if (is_null($client))
                    throw new ValidationException("client does not exists");

                if (!$client->isUriAllowed($redirect_uri))
                    throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client", $redirect_uri));

                $params['redirect_uri'] = $redirect_uri;
                $params['client_id'] = $client_id;
            }

            if($request->has('email')){
                $params['email'] =  $request->get("email");
            }

            if($request->has('first_name')){
                $params['first_name'] =  $request->get("first_name");
            }

            if($request->has('last_name')){
                $params['last_name'] =  $request->get("last_name");
            }

            return view('auth.register', $params);
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
        return view("auth.register_error");
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'first_name'               => 'required|string|max:100',
            'last_name'                => 'required|string|max:100',
            'country_iso_code'         => 'required|string|country_iso_alpha2_code',
            'email'                    => 'required|string|email|max:255',
            'password'                 => 'required|string|confirmed|password_policy',
            'g-recaptcha-response'     => 'required|recaptcha',
        ];

        if(!empty(Config::get("app.code_of_conduct_link", null))){
            $rules['agree_code_of_conduct'] = 'required|string|in:true';
        }

        return Validator::make($data, $rules);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(LaravelRequest $request)
    {
        $validator = null;
        try {
            $payload = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return back()
                    ->withInput($request->only(['first_name', 'last_name', 'country_iso_code','email','client_id', 'redirect_uri']))
                    ->withErrors($validator);
            }

            $user = $this->user_service->registerUser($payload);

            $params = [
                'client_id'    => '',
                'redirect_uri' => '',
            ];

            // check if we have a former oauth2 request
            if ($this->memento_service->exists()) {

                Log::debug("RegisterController::register exist a oauth auth request on session");
                $oauth_auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
                (
                    OAuth2Message::buildFromMemento($this->memento_service->load())
                );

                if ($oauth_auth_request->isValid()) {
                    $redirect_uri = $oauth_auth_request->getRedirectUri();
                    $client_id = $oauth_auth_request->getClientId();
                    Log::debug(sprintf( "RegisterController::register exist a oauth auth request is valid for client id %s", $client_id));
                    $client = $this->client_repository->getClientById($client_id);
                    if (is_null($client))
                        throw new ValidationException("client does not exists");

                    if (!$client->isUriAllowed($redirect_uri))
                        throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client", $redirect_uri));

                    $this->memento_service->serialize($oauth_auth_request->getMessage()->createMemento());

                    $params['redirect_uri'] = action('OAuth2\OAuth2ProviderController@auth');

                    Auth::login($user, false);
                }
            }
            // check redirect uri with associated client
            if($request->has("redirect_uri") && $request->has("client_id")){
                $redirect_uri = $request->get("redirect_uri");
                $client_id    = $request->get("client_id");
                $client       = $this->client_repository->getClientById($client_id);

                if(is_null($client))
                    throw new ValidationException("client does not exists");

                if(!$client->isUriAllowed($redirect_uri))
                    throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client", $redirect_uri));

                $params['client_id']    = $client_id;
                $params['redirect_uri'] = $redirect_uri;
                Auth::login($user, false);
            }

            return view("auth.register_success", $params);
        }
        catch (ValidationException $ex){
            Log::warning($ex);

            if(!is_null($validator)) {
                $validator->getMessageBag()->add('validation', sprintf
                (
                    "It looks like a user with this email address already exists." .
                    "You can either <a href=\'%s\'>sign in</a> or <a href=\'%s\'>reset your password</a> if you\'ve forgotten it.",
                    URL::action("UserController@getLogin"),
                    URL::action("Auth\ForgotPasswordController@showLinkRequestForm")
                ));
            }

            return back()
                ->withInput($request->only(['first_name', 'last_name', 'country_iso_code','email']))
                ->withErrors($validator);
        }
        catch(Exception $ex){
            Log::warning($ex);
        }
        return view("auth.register_error");
    }
}