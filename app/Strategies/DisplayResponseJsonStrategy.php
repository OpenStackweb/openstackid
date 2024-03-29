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

use App\ModelSerializers\SerializerRegistry;
use Illuminate\Contracts\Support\MessageProvider;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Support\Facades\Response;
use Utils\Services\IAuthService;
use Illuminate\Support\Facades\URL;
/**
 * Class DisplayResponseJsonStrategy
 * @package Strategies
 */
class DisplayResponseJsonStrategy implements IDisplayResponseStrategy
{

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getConsentResponse(array $data = [])
    {
        // fix scopes
        $requested_scopes                     = $data['requested_scopes'];
        $data['requested_scopes']             = [];
        foreach($requested_scopes as $scope)
        {
            $data['requested_scopes'][] = SerializerRegistry::getInstance()->getSerializer($scope)->serialize();
        }

        $data['required_params']              = array('_token', 'trust');
        $data['required_params_valid_values'] = array
        (
            'trust' => array
            (
                IAuthService::AuthorizationResponse_AllowOnce,
                IAuthService::AuthorizationResponse_DenyOnce,
            ),
            '_token' => csrf_token()
        );
        $data['optional_params'] = [];
        $data['url']             = URL::action('UserController@postConsent');
        $data['method']          = 'POST';
        return Response::json($data, 412);
    }

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getLoginResponse(array $data = [])
    {
        $data['required_params'] = array('username','password', '_token');
        $data['optional_params'] = array('remember');
        $data['url']             = URL::action('UserController@postLogin');
        $data['method']          = 'POST';

        if(!isset($data['required_params_valid_values']))
        {
            $data['required_params_valid_values'] = [];
        }

        $data['required_params_valid_values']['_token'] = csrf_token();
        return Response::json($data, 412);
    }

    /**
     * @param array $data
     * @return SymfonyResponse
     */
    public function getLoginErrorResponse(array $data = [])
    {
        if(isset($data['validator']) && $data['validator'] instanceof MessageProvider )
        {
            $validator = $data['validator'];
            unset($data['validator']);
            $data['error_message'] = [];
            $errors = $validator->getMessageBag()->getMessages();
            foreach($errors as $e)
            {
                array_push($data['error_message'],$e[0]);
            }
            // remove validator from data bc should not be serialized on json response
            unset($data['validator']);
        }
        return Response::json($data, 412);
    }
}