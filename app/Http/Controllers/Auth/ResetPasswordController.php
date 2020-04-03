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
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use App\Services\Auth\IUserService;
use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
/**
 * Class ResetPasswordController
 * @package App\Http\Controllers\Auth
 */
final class ResetPasswordController extends Controller
{
    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @var IUserPasswordResetRequestRepository
     */
    private $user_password_reset_request_repository;

    /**
     * ResetPasswordController constructor.
     * @param IUserPasswordResetRequestRepository $user_password_reset_request_repository
     * @param IUserService $user_service
     */
    public function __construct
    (
        IUserPasswordResetRequestRepository $user_password_reset_request_repository,
        IUserService $user_service
    )
    {
        $this->middleware('guest');
        $this->user_service = $user_service;
        $this->user_password_reset_request_repository = $user_password_reset_request_repository;
    }


    /**
     * @param $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm($token)
    {
        try {
            $request = $this->user_password_reset_request_repository->getByToken($token);

            if(is_null($request))
                throw new EntityNotFoundException(sprintf("Request not found for token %s.", $token));

            if(!$request->isValid())
                throw new ValidationException("Request is void.");

            if($request->isRedeem()){
                throw new ValidationException("Request is already redeem.");
            }

            return view('auth.passwords.reset')->with(
                [
                    'token' => $token,
                    'email' => $request->getOwner()->getEmail()
                ]);
        }
        catch (EntityNotFoundException $ex){
            Log::warning($ex);
        }
        catch (ValidationException $ex){
            Log::warning($ex);
        }
        catch(\Exception $ex){
            Log::error($ex);
        }
        return view("auth.passwords.reset_error");
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
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return back()
                    ->withInput($request->only(['token', 'email']))
                    ->withErrors($validator);
            }

            $this->user_service->resetPassword($payload['token'], $payload['password']);

            return view("auth.passwords.reset_success");
        }
        catch (ValidationException $ex){
            Log::warning($ex);
            foreach ($ex->getMessages() as $message){
                $validator->getMessageBag()->add('validation', $message);
            }
            return back()
                ->withInput($request->only(['token', 'email']))
                ->withErrors($validator);
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
        return view("auth.passwords.reset_error");

    }

}