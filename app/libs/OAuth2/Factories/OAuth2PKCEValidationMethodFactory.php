<?php namespace OAuth2\Factories;
/**
 * Copyright 2020 OpenStack Foundation
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
use OAuth2\Exceptions\InvalidOAuth2PKCERequest;
use OAuth2\GrantTypes\Strategies\PKCEPlainValidator;
use OAuth2\GrantTypes\Strategies\PKCES256Validator;
use OAuth2\Models\AuthorizationCode;
use OAuth2\OAuth2Protocol;
use OAuth2\Requests\OAuth2AccessTokenRequestAuthCode;
use OAuth2\Strategies\IPKCEValidationMethod;
/**
 * Class OAuth2PKCEValidationMethodFactory
 * @package OAuth2\Factories
 */
final class OAuth2PKCEValidationMethodFactory
{
    /**
     * @param AuthorizationCode $auth_code
     * @param OAuth2AccessTokenRequestAuthCode $request
     * @return IPKCEValidationMethod
     * @throws InvalidOAuth2PKCERequest
     */
    static public function build(AuthorizationCode $auth_code, OAuth2AccessTokenRequestAuthCode $request)
    :IPKCEValidationMethod {

        $code_challenge = $auth_code->getCodeChallenge();
        $code_challenge_method = $auth_code->getCodeChallengeMethod();

        if(empty($code_challenge) || empty($code_challenge_method)){
            throw new InvalidOAuth2PKCERequest(sprintf("%s or %s missing", OAuth2Protocol::PKCE_CodeChallenge, OAuth2Protocol::PKCE_CodeChallengeMethod));
        }

        /**
         * code_verifier = high-entropy cryptographic random STRING using the
         * unreserved characters [A-Z] / [a-z] / [0-9] / "-" / "." / "_" / "~"
         * from Section 2.3 of [RFC3986], with a minimum length of 43 characters
         * and a maximum length of 128 characters.
         */

        $code_verifier =  $request->getCodeVerifier();
        if(empty($code_verifier))
            throw new InvalidOAuth2PKCERequest(sprintf("%s param required", OAuth2Protocol::PKCE_CodeVerifier));
        $code_verifier_len = strlen($code_verifier);
        if( $code_verifier_len < 43 || $code_verifier_len > 128)
            throw new InvalidOAuth2PKCERequest(sprintf("%s param should have at least 43 and at most 128 characters.", OAuth2Protocol::PKCE_CodeVerifier));

        switch ($code_challenge_method){
            case OAuth2Protocol::PKCE_CodeChallengeMethodPlain:
                return new PKCEPlainValidator($code_challenge, $code_verifier);
                break;
            case OAuth2Protocol::PKCE_CodeChallengeMethodSHA256:
                return new PKCES256Validator($code_challenge, $code_verifier);
                break;
            default:
                throw new InvalidOAuth2PKCERequest(sprintf("invalid %s param", OAuth2Protocol::PKCE_CodeChallengeMethod));
                break;
        }
    }
}