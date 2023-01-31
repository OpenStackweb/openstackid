<?php namespace App\Models\OAuth2\Factories;
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

use Illuminate\Support\Facades\Config;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\OAuth2Protocol;
use OAuth2\Requests\OAuth2PasswordlessAuthenticationRequest;
use Utils\Services\IdentifierGenerator;

/**
 * Class OTPFactory
 * @package App\Models\OAuth2\Factories
 */
final class OTPFactory
{

    /**
     * @param OAuth2PasswordlessAuthenticationRequest $request
     * @param IdentifierGenerator $identifier_generator
     * @param Client|null $client
     * @return OAuth2OTP
     */
    static public function buildFromRequest
    (
        OAuth2PasswordlessAuthenticationRequest $request,
        IdentifierGenerator $identifier_generator,
        ?Client $client = null
    ):OAuth2OTP{

        $lifetime = Config::get("otp.lifetime", 120);
        $length = Config::get("otp.length",6);

        if(!is_null($client)){
            $lifetime = $client->getOtpLifetime();
            $length = $client->getOtpLength();
        }

        $otp = new OAuth2OTP($length, $lifetime);
        $otp->setConnection($request->getConnection());
        $otp->setSend($request->getSend());

        // if its inline the OTP lives forever
        if($otp->getConnection() === OAuth2Protocol::OAuth2PasswordlessConnectionInline){
            $lifetime = 0 ;
        }

        $otp->setLifetime($lifetime);
        $otp->setNonce($request->getNonce());
        $otp->setRedirectUrl($request->getRedirectUri());
        $otp->setScope($request->getScope());
        $otp->setEmail($request->getEmail());
        $otp->setPhoneNumber($request->getPhoneNumber());
        $identifier_generator->generate($otp);

        if(!is_null($client)){
            // check that client does not has a value
            while($client->hasOTP($otp->getValue())){
                $identifier_generator->generate($otp);
            }
            // then add it
            $client->addOTPGrant($otp);
        }

        return $otp;
    }

    /**
     * @param array $payload
     * @param IdentifierGenerator $identifier_generator
     * @param Client|null $client
     * @return OAuth2OTP
     */
    static public function buildFromPayload
    (
        array $payload,
        IdentifierGenerator $identifier_generator,
        ?Client $client = null
    ):OAuth2OTP{

        $lifetime = Config::get("otp.lifetime", 120);
        $length = Config::get("otp.length",6);

        if(!is_null($client)){
            $lifetime = $client->getOtpLifetime();
            $length = $client->getOtpLength();
        }

        $otp = new OAuth2OTP($length, $lifetime);

        $otp->setConnection($payload[OAuth2Protocol::OAuth2PasswordlessConnection]);
        $otp->setSend($payload[OAuth2Protocol::OAuth2PasswordlessSend]);
        $otp->setScope($payload[OAuth2Protocol::OAuth2Protocol_Scope] ?? null);

        // if its inline the OTP lives forever
        if($otp->getConnection() === OAuth2Protocol::OAuth2PasswordlessConnectionInline){
            $lifetime = 0 ;
        }

        $otp->setLifetime($lifetime);
        $otp->setNonce($payload[OAuth2Protocol::OAuth2Protocol_Nonce] ?? null);
        $otp->setRedirectUrl($payload[OAuth2Protocol::OAuth2Protocol_RedirectUri] ?? null);
        $otp->setEmail($payload[OAuth2Protocol::OAuth2PasswordlessEmail] ?? null);
        $otp->setPhoneNumber($payload[OAuth2Protocol::OAuth2PasswordlessPhoneNumber] ?? null);
        $identifier_generator->generate($otp);

        if(!is_null($client)){
            // check that client does not has a value
            while($client->hasOTP($otp->getValue())){
                $identifier_generator->generate($otp);
            }
            // then add it
            $client->addOTPGrant($otp);
        }

        return $otp;
    }
}