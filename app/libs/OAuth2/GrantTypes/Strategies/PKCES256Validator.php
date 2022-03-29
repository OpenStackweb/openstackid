<?php namespace OAuth2\GrantTypes\Strategies;
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

use Illuminate\Support\Facades\Log;
use OAuth2\Strategies\IPKCEValidationMethod;
/**
 * Class PKCES256Validator
 * @package OAuth2\GrantTypes\Strategies
 */
final class PKCES256Validator extends PKCEBaseValidator implements IPKCEValidationMethod
{

    public function isValid(): bool
    {
        /**
         * The code challenge should be a Base64 encoded string with URL and filename-safe characters. The trailing '='
         * characters should be removed and no line breaks, whitespace, or other additional characters should be present.
         */
        $encoded = base64_encode(hash('sha256', $this->code_verifier, true));
        Log::debug(sprintf("PKCES256Validator::isValid code_verifier %s encoded %s code challenge ", $this->code_verifier, $encoded, $this->code_challenge));
        $calculate_code_challenge = strtr(rtrim($encoded, '='), '+/', '-_');
        return $this->code_challenge === $calculate_code_challenge;
    }
}