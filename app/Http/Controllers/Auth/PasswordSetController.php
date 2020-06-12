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
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\Repositories\IClientRepository;
/**
 * Class PasswordSetController
 * @package App\Http\Controllers\Auth
 */
final class PasswordSetController extends Controller
{
    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @var IUserRegistrationRequestRepository
     */
    private $user_registration_request_repository;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * PasswordSetController constructor.
     * @param IUserRegistrationRequestRepository $user_registration_request_repository
     * @param IClientRepository $client_repository
     * @param IUserService $user_service
     */
    public function __construct
    (
        IUserRegistrationRequestRepository $user_registration_request_repository,
        IClientRepository $client_repository,
        IUserService $user_service
    )
    {
        $this->middleware('guest');
        $this->user_service = $user_service;
        $this->user_registration_request_repository = $user_registration_request_repository;
        $this->client_repository = $client_repository;
    }

    /**
     * @param $token
     * @param LaravelRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showPasswordSetForm($token, LaravelRequest $request)
    {
        try {

            $user_registration_request = $this->user_registration_request_repository->getByHash($token);

            if(is_null($user_registration_request))
                throw new EntityNotFoundException("request not found");

            if($user_registration_request->isRedeem()) {

                // check redirect uri
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
                    $params['email']        = $user_registration_request->getEmail();

                    return view("auth.passwords.set_success", $params);
                }

                throw new ValidationException("request already redeem!");
            }

            $params = [
                "email"        => $user_registration_request->getEmail(),
                "token"        => $token,
                "redirect_uri" => '',
                "client_id"    => '',
            ];

            if($request->has("redirect_uri") && $request->has("client_id")){
                $redirect_uri = $request->get("redirect_uri");
                $client_id    = $request->get("client_id");

                $client = $this->client_repository->getClientById($client_id);
                if(is_null($client))
                    throw new ValidationException("client does not exists");

                if(!$client->isUriAllowed($redirect_uri))
                    throw new ValidationException(sprintf("redirect_uri %s is not allowed on associated client", $redirect_uri));

                $params['redirect_uri'] = $redirect_uri;
                $params['client_id']    = $client_id;
            }

            return view('auth.passwords.set', $params);
        }
        catch(EntityNotFoundException $ex){
            Log::warning($ex);
        }
        catch(ValidationException $ex){
            Log::warning($ex);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return view('auth.passwords.set_error');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'token'                    => 'required',
            'password'                 => 'required|string|min:8|confirmed',
            'g-recaptcha-response'     => 'required|recaptcha',
        ]);
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function setPassword(LaravelRequest $request)
    {
        try {
            $payload   = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return back()
                    ->withInput($request->only(['token','client_id', 'redirect_uri', 'email']))
                    ->withErrors($validator);
            }

            $user_registration_request = $this->user_service->setPassword($payload['token'], $payload['password']);
            $params = [
                'client_id'    => '',
                'redirect_uri' => '',
                'email'        => '',
            ];

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
                $params['email']        = $user_registration_request->getEmail();
            }

            Auth::login($user_registration_request->getOwner(), true);

            return view("auth.passwords.set_success", $params);
        }
        catch (EntityNotFoundException $ex){
            Log::warning($ex);
        }
        catch (ValidationException $ex){
            Log::warning($ex);
            foreach ($ex->getMessages() as $message){
                $validator->getMessageBag()->add('validation', $message);
            }
            return back()
                ->withInput($request->only(['token','client_id', 'redirect_uri', 'email']))
                ->withErrors($validator);
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }

        return view("auth.passwords.reset_error");

    }
}