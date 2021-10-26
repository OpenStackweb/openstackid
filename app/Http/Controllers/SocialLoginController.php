<?php namespace App\Http\Controllers;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\libs\Auth\SocialLoginProviders;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use models\exceptions\ValidationException;
use Strategies\ILoginStrategy;
use Strategies\ILoginStrategyFactory;
use Utils\Services\IAuthService;
/**
 * Class SocialLoginController
 * @package App\Http\Controllers
 */
final class SocialLoginController extends Controller
{

    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var ILoginStrategy
     */
    private $login_strategy;

    /**
     * SocialLoginController constructor.
     * @param IAuthService $auth_service
     * @param IUserService $user_service
     * @param ILoginStrategyFactory $login_strategy_factory
     */
    public function __construct(
        IAuthService $auth_service,
        IUserService $user_service,
        ILoginStrategyFactory $login_strategy_factory
    ){
        $this->auth_service = $auth_service;
        $this->user_service = $user_service;
        $this->middleware(function ($request, $next) use($login_strategy_factory){
            // we do it here just to ensure that user session is loaded
            Log::debug(sprintf("SocialLoginController::middleware"));
            $this->login_strategy = $login_strategy_factory->build();
            return $next($request);
        });
    }

    /**
     * @param $provider
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($provider)
    {
        try {
            Log::debug(sprintf("SocialLoginController::redirect provider %s", $provider));
            if(!SocialLoginProviders::isSupportedProvider($provider))
                throw new ValidationException(sprintf("Provider %s is not supported.", $provider));
            return Socialite::driver($provider)->redirect();
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return view("auth.register_error");
    }

    /**
     * @param $provider
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|mixed
     */
    public function callback($provider)
    {
        try {
            Log::debug(sprintf("SocialLoginController::callback provider %s", $provider));
            // validate provider
            if(!SocialLoginProviders::isSupportedProvider($provider))
                throw new ValidationException(sprintf("Provider %s is not supported.", $provider));

            $social_user = Socialite::driver($provider)->user();
            // try to get user by primary email from our db
            Log::debug(sprintf("SocialLoginController::callback provider %s trying to get user using email %s", $provider, $social_user->getEmail()));
            $user_email = $social_user->getEmail();

            if(is_null($user_email))
            {
                Log::error
                (
                    sprintf
                    (
                        "SocialLoginController::callback user email is null provider %s user id %s user name %s user nick name %s",
                        $provider,
                        $social_user->getId(),
                        $social_user->getName(),
                        $social_user->getNickname()
                    )
                );
                throw new ValidationException("User email is null");
            }

            $user = $this->auth_service->getUserByUsername($user_email);

            if (is_null($user)) {
                Log::debug(sprintf("SocialLoginController::callback provider %s user does not exists for email %s, creating ...", $provider, $social_user->getEmail()));
                // if does not exists , registered it with email verified and active

                $user = $this->user_service->registerUser([
                    'email' => $social_user->getEmail(),
                    'full_name' => $social_user->getName(),
                    'external_pic' => $social_user->getAvatar(),
                    'external_id' => $social_user->getId(),
                    'email_verified' => true,
                    // dont send email
                    'send_email_verified_notice' => false,
                    'active' => true,
                    'external_provider' => $provider
                ]);
            }

            // do login
            Auth::login($user, true);
            // and continue the usual flow
            return $this->login_strategy->postLogin([ 'provider'=> $provider ]);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
        return view("auth.register_error");
    }

}
