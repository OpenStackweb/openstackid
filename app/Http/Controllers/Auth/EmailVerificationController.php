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
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
/**
 * Class EmailVerificationController
 * @package App\Http\Controllers\Auth
 */
final class EmailVerificationController extends Controller
{

    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * EmailVerificationController constructor.
     * @param IUserService $user_service
     */
    public function __construct(IUserService $user_service)
    {
        $this->user_service = $user_service;
    }

    public function showVerificationForm()
    {
        return view('auth.email_verification', ['email' => Request::input("email", "")]);
    }

    /**
     * @param string $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function verify($token)
    {
        try {
            $user = $this->user_service->verifyEmail($token);
            return view('auth.email_verification_success', ['user' => $user]);
        }
        catch (EntityNotFoundException $ex){
            Log::warning($ex);
        }
        catch (ValidationException $ex){
            Log::warning($ex);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return view('auth.email_verification_error');
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
            'email'                    => 'required|string|email|max:255',
            'g-recaptcha-response'     => 'required|recaptcha',
        ]);
    }

    public function resend(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return Redirect::action('Auth\EmailVerificationController@showVerificationForm')->withErrors($validator);
            }

            $user = $this->user_service->resendVerificationEmail($payload);

            return view("auth.email_verification_resend_success", ['user' => $user]);
        }
        catch (EntityNotFoundException $ex){
            Log::warning($ex);
        }
        catch (ValidationException $ex){
            Log::warning($ex);
            foreach ($ex->getMessages() as $message){
                $validator->getMessageBag()->add('validation', $message);
            }
            return Redirect::action('Auth\EmailVerificationController@showVerificationForm')->withErrors($validator);
        }
        catch(\Exception $ex){
            Log::error($ex);
        }
        return view("auth.email_verification_error");
    }
}