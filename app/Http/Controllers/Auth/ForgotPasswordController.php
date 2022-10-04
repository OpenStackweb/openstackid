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
use App\libs\Utils\EmailUtils;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use OAuth2\Repositories\IClientRepository;

/**
 * Class ForgotPasswordController
 * @package App\Http\Controllers\Auth
 */
final class ForgotPasswordController extends Controller
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
     * ForgotPasswordController constructor.
     * @param IClientRepository $client_repository
     * @param IUserService $user_service
     */
    public function __construct
    (
        IClientRepository $client_repository,
        IUserService $user_service
    )
    {
        $this->middleware('guest');
        $this->user_service = $user_service;
        $this->client_repository = $client_repository;
    }

    /**
     * @param LaravelRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showLinkRequestForm(LaravelRequest $request)
    {
        try {
            $params = [
                "redirect_uri" => '',
                "client_id"    => '',
                'email' => ''
            ];

            if($request->has("email")){
                $email = trim($request->get("email"));
                if (EmailUtils::isValidEmail($email)) {
                    $params['email'] = $email;
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
            return view('auth.passwords.email', $params);
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        return view("auth.passwords.email_error");
    }

    /**
     * Send a reset link to the given user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return back()
                    ->withInput($request->only('email', 'client_id', 'redirect_uri'))
                    ->withErrors($validator);
            }

            $this->user_service->requestPasswordReset($payload);

            $params = [
                'client_id'    => '',
                'redirect_uri' => '',
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
            }

            $params['status'] = 'Reset link sent';
            return back()->with($params);

        } catch (ValidationException $ex) {
            Log::warning($ex);
            foreach ($ex->getMessages() as $message) {
                $validator->getMessageBag()->add('validation', $message);
            }
            return back()
                ->withInput($request->only(['email', 'client_id', 'redirect_uri']))
                ->withErrors($validator);
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        return view("auth.passwords.email_error");
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255',
        ]);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse($response)
    {

    }

}