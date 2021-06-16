<?php namespace OAuth2\Responses;
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

use OAuth2\OAuth2Protocol;
use Utils\Http\HttpContentType;
/**
 * Class OAuth2PasswordlessAuthenticationResponse
 * @package OAuth2\Responses
 */
class OAuth2PasswordlessAuthenticationResponse extends OAuth2DirectResponse
{
    /**
     * OAuth2PasswordlessAuthenticationResponse constructor.
     * @param int $otp_length
     * @param int $otp_lifetime
     * @param string|null $scope
     */
    public function __construct(int $otp_length, int $otp_lifetime, ?string $scope = null)
    {
        // Successful Responses: A server receiving a valid request MUST send a
        // response with an HTTP status code of 200.
        parent::__construct(self::HttpOkResponse, HttpContentType::Json);
        $this["otp_length"] = $otp_length;
        $this["otp_lifetime"] = $otp_lifetime;
        if(!empty($scope))
            $this[OAuth2Protocol::OAuth2Protocol_Scope] = $scope;
    }
}