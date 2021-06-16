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
use Models\OAuth2\OAuth2OTP;
/**
 * Class OTPTypeCodeBuilderStrategy
 * @package App\Strategies\OTP
 */
final class OTPTypeCodeBuilderStrategy
implements IOTPTypeBuilderStrategy
{

    /**
     * @param OAuth2OTP $otp
     * @return string
     */
    public function generate(OAuth2OTP $otp): string
    {
        return $otp->getValue();
    }
}