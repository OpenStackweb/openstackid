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
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
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
     * ForgotPasswordController constructor.
     * @param IUserService $user_service
     */
    public function __construct(IUserService $user_service)
    {
        $this->middleware('guest');
        $this->user_service = $user_service;
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(LaravelRequest $request)
    {
        try {
            $payload = $request->all();
            $validator = $this->validator($payload);

            if (!$validator->passes()) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors($validator);
            }

            $this->user_service->requestPasswordReset($payload);

            return $this->sendResetLinkResponse("Reset link sent");
        }
        catch (ValidationException $ex){
            Log::warning($ex);
            foreach ($ex->getMessages() as $message){
                $validator->getMessageBag()->add('validation', $message);
            }
            return back()
                ->withInput($request->only('email'))
                ->withErrors($validator);
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
        return view("auth.passwords.email_error");
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
            'email' => 'required|string|email|max:255',
        ]);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse($response)
    {
        return back()->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkFailedResponse(LaravelRequest $request, $response)
    {
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)]);
    }
}