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

/**
 * Class OAuth2PasswordlessInlineAuthenticationResponse
 * @package OAuth2\Responses
 */
final class OAuth2PasswordlessInlineAuthenticationResponse
extends OAuth2PasswordlessAuthenticationResponse
{
    /**
     * @param string $value
     * @param int $otp_length
     * @param int $otp_lifetime
     * @param string|null $scope
     */
    public function __construct(string $value, int $otp_length, int $otp_lifetime, ?string $scope = null)
    {
        // Successful Responses: A server receiving a valid request MUST send a
        // response with an HTTP status code of 200.
        parent::__construct($otp_length, $otp_lifetime, $scope);
        $this["value"] = $value;
    }
}