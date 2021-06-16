<?php namespace App\Strategies\OTP;
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

use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\OAuth2Protocol;
/**
 * Class OTPTypeBuilderStrategyFactory
 * @package App\Strategies\OTP
 */
final class OTPTypeBuilderStrategyFactory
{
    /**
     * @param string $send
     * @return IOTPTypeBuilderStrategy
     */
    public static function build(string $send):IOTPTypeBuilderStrategy{
        switch($send){
            case OAuth2Protocol::OAuth2PasswordlessSendCode:
                return new OTPTypeCodeBuilderStrategy();
        }
        throw new InvalidOAuth2Request(sprintf("send value %s is not valid", $send));
    }
}