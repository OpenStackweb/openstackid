<?php namespace OAuth2\Requests;
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

/**
 * Class OAuth2AccessTokenRequestPasswordless
 * @package OAuth2\Requests
 */
final class OAuth2AccessTokenRequestPasswordless extends OAuth2TokenRequest
{
    public static $params = [
        OAuth2Protocol::OAuth2Protocol_GrantType => [
            OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless
        ],
       OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => [],
       OAuth2Protocol::OAuth2PasswordlessConnection => [
           OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
           OAuth2Protocol::OAuth2PasswordlessConnectionSMS,
       ] ,
        OAuth2Protocol::OAuth2Protocol_Scope => []
    ];

    /**
     * @var array
     */
    public static $optional_params = [
        OAuth2Protocol::OAuth2PasswordlessEmail => [
            [
                OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail
            ]
        ],
        OAuth2Protocol::OAuth2PasswordlessPhoneNumber => [
            [
                OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionSMS
            ]
        ],
    ];

    /**
     * Validates current request
     * @return bool
     */
    public function isValid()
    {
        $this->last_validation_error = '';

        // validate mandatory params

        foreach (self::$params as $mandatory_param => $values) {
            $mandatory_val = $this->getParam($mandatory_param);
            if (empty($mandatory_val)) {
                $this->last_validation_error = sprintf("%s not set", $mandatory_param);
                return false;
            }

            if (count($values) > 0 && !in_array($mandatory_val, $values)) {
                $this->last_validation_error = sprintf("%s has not a valid value (%s)", $mandatory_param, implode(",", $values));
                return false;
            }
        }

        // validate optional params
        foreach (self::$optional_params as $optional_param => $rules) {
            $optional_param_val = $this->getParam($optional_param);
            if (empty($optional_param_val) && count($rules)) continue;
            foreach ($rules as $dep_param => $dep_val) {
                $dep_param_cur_val = $this->getParam($dep_param);
                if ($dep_param_cur_val != $dep_val) continue;
                if (empty($optional_param_val)) {
                    $this->last_validation_error = sprintf("%s not set.", $optional_param);
                    return false;
                }
            }
        }

        return true;
    }

    public function getConnection(): string
    {
        return $this->getParam(OAuth2Protocol::OAuth2PasswordlessConnection);
    }

    public function getEmail(): ?string
    {
        return $this->getParam(OAuth2Protocol::OAuth2PasswordlessEmail);
    }

    public function getPhoneNumber(): ?string
    {
        return $this->getParam(OAuth2Protocol::OAuth2PasswordlessPhoneNumber);
    }

    public function getUserName(): ?string
    {
        return $this->getConnection() == OAuth2Protocol::OAuth2PasswordlessConnectionEmail ? $this->getEmail() : $this->getPhoneNumber();
    }

    public function getScopes():string{
        return $this->getParam(OAuth2Protocol::OAuth2Protocol_Scope);
    }

    public function getOTP():string{
        // all OTP are on UPPER
        return strtoupper($this->getParam(OAuth2Protocol::OAuth2Protocol_ResponseType_OTP));
    }
}