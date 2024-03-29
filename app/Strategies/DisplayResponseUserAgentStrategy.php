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

use App\libs\Auth\SocialLoginProviders;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;

/**
 * Class DisplayResponseUserAgentStrategy
 * @package Strategies
 */
class DisplayResponseUserAgentStrategy implements IDisplayResponseStrategy
{

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getConsentResponse(array $data = [])
    {
        return Response::view("oauth2.consent", $data, 200);
    }

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getLoginResponse(array $data = [])
    {
        $provider = $data["provider"] ?? null;

        if(!empty($provider)) {
            return redirect()->route('social_login', ['provider' => $provider]);
        }
        $data['supported_providers'] = SocialLoginProviders::buildSupportedProviders();
        return Response::view("auth.login", $data, 200);
    }

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getLoginErrorResponse(array $data = [])
    {
        $response =  Redirect::action('UserController@getLogin');

        if(isset($data['error_message']))
            $response = $response->with('flash_notice', $data['error_message']);

        if(isset($data['validator'])) {
            $response = $response->withErrors($data['validator']);
            // remove validator from data bc should not be serialized on session
            unset($data['validator']);
        }

        foreach ($data as $key => $val)
            $response = $response->with($key, $val);

        return $response;
    }
}