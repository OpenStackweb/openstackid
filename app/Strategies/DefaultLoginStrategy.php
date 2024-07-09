<?php namespace Strategies;
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
use  Illuminate\Support\Facades\Request;
use App\libs\Auth\SocialLoginProviders;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use OAuth2\OAuth2Protocol;
use Utils\IPHelper;
use Services\IUserActionService;
use Utils\Services\IAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
/**
 * Class DefaultLoginStrategy
 * @package Strategies
 */
class DefaultLoginStrategy implements ILoginStrategy
{

    /**
     * @var IUserActionService
     */
    protected $user_action_service;
    /**
     * @var IAuthService
     */
    protected $auth_service;

    public function __construct(IUserActionService $user_action_service,
                                IAuthService $auth_service)
    {
        $this->user_action_service = $user_action_service;
        $this->auth_service        = $auth_service;
    }

    public function getLogin()
    {
        Log::debug(sprintf("DefaultLoginStrategy::getLogin"));

        // login hint processing
        Session::forget(['username', 'user_fullname', 'user_pic', 'user_verified']);
        $login_hint = null;
        if(Request::has(OAuth2Protocol::OAuth2Protocol_LoginHint)){
            $login_hint = Request::query(OAuth2Protocol::OAuth2Protocol_LoginHint);
            if(!EmailUtils::isValidEmail($login_hint))
                $login_hint = null;
        }

        if(!empty($login_hint)) {
            $user = $this->auth_service->getUserByUsername($login_hint);

            if(!is_null($user)) {
                Session::put('username', $user->getEmail());
                Session::put('user_fullname', $user->getFullName());
                Session::put('user_pic', $user->getPic());
                Session::put('user_verified', true);
            }
        }

        Session::save();

        if (Auth::guest())
            return View::make("auth.login", [
                'supported_providers' => SocialLoginProviders::buildSupportedProviders()
            ]);
        return Redirect::action("UserController@getProfile");
    }

    public function  postLogin(array $params = [])
    {
        $user = $this->auth_service->getCurrentUser();
        $identifier = $user->getIdentifier();
        $realm = "From Site";
        if(isset($params['provider']))
            $realm .= " using ".strtoupper($params['provider']);
        $this->user_action_service->addUserAction
        (
            $this->auth_service->getCurrentUser()->getId(),
            IPHelper::getUserIp(),
            IUserActionService::LoginAction,
            $realm
        );
        $default_url = URL::action("UserController@getIdentity", array("identifier" => $identifier));
        return Redirect::intended($default_url);
    }

    public function cancelLogin()
    {
        return Redirect::action("HomeController@index");
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function errorLogin(array $params)
    {
        $response = Redirect::action('UserController@getLogin');

        if(isset($params['error_message']))
            $response = $response->with('flash_notice', $params['error_message']);

        if(isset($params['validator'])) {
            $response = $response->withErrors($params['validator']);
            // remove validator from data bc should not be serialized on session
            unset($params['validator']);
        }

        foreach ($params as $key => $val)
            $response = $response->with($key, $val);
        return $response;
    }
}